#!/usr/bin/env bash
# Letsgo – first-time environment setup
# Usage: ./setup.sh
#
# Copies .env.example → .env and generates random DB credentials
# (database name, user, password, root password) using openssl.
#
# Safe to re-run: prompts before overwriting an existing .env.

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ENV_FILE="$SCRIPT_DIR/.env"
ENV_EXAMPLE="$SCRIPT_DIR/.env.example"

# ── Helpers ───────────────────────────────────────────────────────────────────

# Portable sed -i (GNU on Linux, BSD on macOS)
sed_inplace() {
    if sed --version 2>/dev/null | grep -q GNU; then
        sed -i "$@"
    else
        sed -i '' "$@"
    fi
}

gen_hex() { openssl rand -hex "${1:-12}"; }

# ── Preflight ─────────────────────────────────────────────────────────────────

if [ ! -f "$ENV_EXAMPLE" ]; then
    echo "✗ .env.example not found in $SCRIPT_DIR – cannot continue." >&2
    exit 1
fi

if ! command -v openssl &>/dev/null; then
    echo "✗ openssl is required but not found in PATH." >&2
    exit 1
fi

if [ -f "$ENV_FILE" ]; then
    echo "⚠  $ENV_FILE already exists."
    read -rp "   Overwrite with freshly generated credentials? [y/N] " confirm
    case "$confirm" in
        [yY][eE][sS]|[yY]) echo "" ;;
        *) echo "Aborted. Existing .env was not changed."; exit 0 ;;
    esac
fi

# ── Generate credentials ──────────────────────────────────────────────────────

SUFFIX="$(gen_hex 4)"          # 8-char hex, used as shared suffix for name+user
DB_NAME="letsgo_${SUFFIX}"
DB_USER="letsgo_${SUFFIX}"
DB_PASS="$(gen_hex 16)"        # 32-char hex password
DB_ROOT_PASS="$(gen_hex 16)"   # 32-char hex root password

# ── Copy and patch .env ───────────────────────────────────────────────────────

cp "$ENV_EXAMPLE" "$ENV_FILE"

sed_inplace "s|^DB_NAME=.*|DB_NAME=${DB_NAME}|"             "$ENV_FILE"
sed_inplace "s|^DB_USER=.*|DB_USER=${DB_USER}|"             "$ENV_FILE"
sed_inplace "s|^DB_PASS=.*|DB_PASS=${DB_PASS}|"             "$ENV_FILE"
sed_inplace "s|^DB_ROOT_PASS=.*|DB_ROOT_PASS=${DB_ROOT_PASS}|" "$ENV_FILE"

# ── Summary ───────────────────────────────────────────────────────────────────

echo "✓ .env created with generated credentials:"
echo ""
echo "   DB_NAME      = ${DB_NAME}"
echo "   DB_USER      = ${DB_USER}"
echo "   DB_PASS      = ${DB_PASS}"
echo "   DB_ROOT_PASS = ${DB_ROOT_PASS}"
echo ""
echo "  To configure mail, open .env and fill in MAIL_HOST, MAIL_USER, etc."
echo "  To change the app port, set APP_PORT (default: 8080)."
echo ""
echo "  Start the stack:"
echo "    docker compose up -d"
