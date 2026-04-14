#!/usr/bin/env bash
# Letsgo – full upgrade runner
# Usage: ./upgrade.sh [backup-dir] [upgrade-sql]
#
# Steps:
#   1. Creates a timestamped mysqldump backup of the database
#   2. Stops the full stack (docker compose down)
#   3. Starts the database container only
#   4. Waits for the database to be ready
#   5. Applies the upgrade SQL file
#   6. Starts the full stack (docker compose up -d)
#
# Arguments (both optional):
#   backup-dir   Directory for backup files  [default: ./backups]
#   upgrade-sql  SQL migration file          [default: ./docker/mysql/upgrade.sql]
#
# Reads DB credentials from .env (same as docker-compose.yml).

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

# ── Arguments ─────────────────────────────────────────────────────────────────

BACKUP_DIR="${1:-$SCRIPT_DIR/backups}"
UPGRADE_SQL="${2:-$SCRIPT_DIR/docker/mysql/upgrade.sql}"

# ── Load .env ─────────────────────────────────────────────────────────────────

ENV_FILE="$SCRIPT_DIR/.env"
EXAMPLE_FILE="$SCRIPT_DIR/.env.example"

if [ ! -f "$ENV_FILE" ]; then
    echo "✗ .env not found. Run ./setup.sh first." >&2
    exit 1
fi

set -o allexport
# shellcheck source=/dev/null
source "$ENV_FILE"
set +o allexport

# ── Sync missing keys from .env.example ──────────────────────────────────────
# Compares .env against .env.example; for every key that is in the example but
# absent from .env, prompts the user for a value and appends it to .env.

if [ -f "$EXAMPLE_FILE" ]; then

    # Collect keys that are in .env.example but missing from .env
    missing_keys=()
    while IFS= read -r line; do
        [[ -z "$line" || "$line" =~ ^# ]] && continue
        if [[ "$line" =~ ^([A-Za-z_][A-Za-z0-9_]*)= ]]; then
            key="${BASH_REMATCH[1]}"
            if ! grep -q "^${key}=" "$ENV_FILE"; then
                missing_keys+=("$key")
            fi
        fi
    done < "$EXAMPLE_FILE"

    if [ ${#missing_keys[@]} -gt 0 ]; then
        echo ""
        echo "  Your .env is missing ${#missing_keys[@]} key(s) found in .env.example."
        echo "  Press Enter to accept the default value shown in brackets,"
        echo "  or type a new value and press Enter."
        echo ""

        # Walk .env.example a second time; for missing keys show buffered
        # comments then prompt, and collect the additions.
        pending_comments=""
        additions_file="$(mktemp)"

        while IFS= read -r line; do
            # Blank line: keep in comment buffer (preserves section spacing)
            if [[ -z "$line" ]]; then
                pending_comments+=$'\n'
                continue
            fi

            # Comment line: accumulate
            if [[ "$line" =~ ^# ]]; then
                pending_comments+="$line"$'\n'
                continue
            fi

            # Key=value line
            if [[ "$line" =~ ^([A-Za-z_][A-Za-z0-9_]*)=(.*) ]]; then
                key="${BASH_REMATCH[1]}"
                default_val="${BASH_REMATCH[2]}"

                # Is this key in our missing list?
                is_missing=false
                for m in "${missing_keys[@]}"; do
                    [ "$m" = "$key" ] && is_missing=true && break
                done

                if $is_missing; then
                    # Print buffered comments to terminal for context
                    if [[ -n "$pending_comments" ]]; then
                        printf '%s' "$pending_comments"
                    fi

                    # Prompt with default
                    if [[ -n "$default_val" ]]; then
                        read -r -p "  $key [$default_val]: " user_val </dev/tty
                        final_val="${user_val:-$default_val}"
                    else
                        read -r -p "  $key: " final_val </dev/tty
                    fi

                    # Write to additions file (with comments for readability)
                    if [[ -n "$pending_comments" ]]; then
                        printf '%s' "$pending_comments" >> "$additions_file"
                    fi
                    printf '%s=%s\n' "$key" "$final_val" >> "$additions_file"
                fi

                # Reset comment buffer after every key line
                pending_comments=""
            fi
        done < "$EXAMPLE_FILE"

        # Append collected additions to .env
        if [ -s "$additions_file" ]; then
            echo "" >> "$ENV_FILE"
            cat "$additions_file" >> "$ENV_FILE"
            echo ""
            echo "  ✓ Added ${#missing_keys[@]} key(s) to .env"
            # Reload .env so new values are available for the rest of the script
            set -o allexport
            source "$ENV_FILE"
            set +o allexport
        fi
        rm -f "$additions_file"
        echo ""
    fi
fi

DB_CONTAINER="${DB_CONTAINER:-letsgo_db}"
DB_NAME="${DB_NAME:-letsgo}"
DB_USER="${DB_USER:-letsgo}"
DB_PASS="${DB_PASS:-change_me_in_production}"
DB_ROOT_PASS="${DB_ROOT_PASS:-change_root_in_production}"

COMPOSE="docker compose -f $SCRIPT_DIR/docker-compose.yml"

# ── Preflight ─────────────────────────────────────────────────────────────────

if [ ! -f "$UPGRADE_SQL" ]; then
    echo "✗ Upgrade SQL not found: $UPGRADE_SQL" >&2
    exit 1
fi

mkdir -p "$BACKUP_DIR"

TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
BACKUP_FILE="$BACKUP_DIR/${DB_NAME}_${TIMESTAMP}.sql"

echo "════════════════════════════════════════"
echo "  Letsgo upgrade – v$(cat "$SCRIPT_DIR/VERSION" 2>/dev/null || echo '?')"
echo "════════════════════════════════════════"
echo "  Container  : $DB_CONTAINER"
echo "  Database   : $DB_NAME"
echo "  SQL file   : $UPGRADE_SQL"
echo "  Backup dir : $BACKUP_DIR"
echo "════════════════════════════════════════"
echo ""

# ── Step 1: Ensure DB is running for backup ───────────────────────────────────

echo "→ [1/5] Ensuring database container is running for backup..."
$COMPOSE up -d db 2>/dev/null

echo "  Waiting for database to accept connections..."
for i in $(seq 1 30); do
    if docker exec "$DB_CONTAINER" \
        mysqladmin ping -h 127.0.0.1 -u"$DB_USER" -p"$DB_PASS" --silent 2>/dev/null; then
        echo "  Database is ready."
        break
    fi
    if [ "$i" -eq 30 ]; then
        echo "✗ Database did not become ready in time." >&2
        exit 1
    fi
    sleep 2
done

# ── Step 2: Backup ────────────────────────────────────────────────────────────

echo "→ [2/5] Backing up '${DB_NAME}' to ${BACKUP_FILE}..."
docker exec "$DB_CONTAINER" \
    mysqldump -uroot -p"${DB_ROOT_PASS}" \
    --single-transaction --routines --triggers \
    "$DB_NAME" \
    > "$BACKUP_FILE"

BACKUP_SIZE="$(du -sh "$BACKUP_FILE" 2>/dev/null | cut -f1)"
echo "  ✓ Backup saved (${BACKUP_SIZE})"

# ── Step 3: Stop stack ────────────────────────────────────────────────────────

echo "→ [3/5] Stopping stack..."
$COMPOSE down

# ── Step 4: Start DB only and run migration ───────────────────────────────────

echo "→ [4/5] Starting database for migration..."
$COMPOSE up -d db

echo "  Waiting for database..."
for i in $(seq 1 30); do
    if docker exec "$DB_CONTAINER" \
        mysqladmin ping -h 127.0.0.1 -u"$DB_USER" -p"$DB_PASS" --silent 2>/dev/null; then
        break
    fi
    if [ "$i" -eq 30 ]; then
        echo "✗ Database did not become ready after restart." >&2
        exit 1
    fi
    sleep 2
done

echo "  Applying schema upgrade..."
docker exec -i "$DB_CONTAINER" \
    mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" \
    < "$UPGRADE_SQL"
echo "  ✓ Schema migration complete."

# ── Step 5: Start full stack ──────────────────────────────────────────────────

echo "→ [5/5] Starting full stack..."
$COMPOSE up -d

echo ""
echo "✓ Upgrade complete."
echo "  Backup: $BACKUP_FILE"
echo "  App:    http://localhost:${APP_PORT:-8080}"
