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

if [ -f "$SCRIPT_DIR/.env" ]; then
    set -o allexport
    # shellcheck source=/dev/null
    source "$SCRIPT_DIR/.env"
    set +o allexport
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
