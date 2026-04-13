#!/usr/bin/env bash
# Letsgo – database migration runner
# Usage: ./migrate.sh
#
# Reads DB credentials from .env (falls back to docker-compose defaults).
# Pipes docker/mysql/upgrade.sql into the letsgo_db container.

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

# Load .env if present
if [ -f "$SCRIPT_DIR/.env" ]; then
    set -o allexport
    source "$SCRIPT_DIR/.env"
    set +o allexport
fi

DB_CONTAINER="${DB_CONTAINER:-letsgo_db}"
DB_NAME="${DB_NAME:-letsgo}"
DB_USER="${DB_USER:-letsgo}"
DB_PASS="${DB_PASS:-change_me_in_production}"
UPGRADE_SQL="$SCRIPT_DIR/docker/mysql/upgrade.sql"

echo "→ Migrating database '${DB_NAME}' in container '${DB_CONTAINER}'..."

docker exec -i "$DB_CONTAINER" \
    mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" \
    < "$UPGRADE_SQL"

echo "✓ Migration complete."
