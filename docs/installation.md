# Installation & Operations

## Requirements

| Component | Version |
|---|---|
| Docker | 20.10+ |
| Docker Compose | v2 (`docker compose`) |
| (Optional) PHP | 8.2+ for local dev without Docker |
| (Optional) MySQL | 8.0+ for local dev without Docker |

---

## Quick Start (Docker)

```bash
# 1. Clone the repository
git clone <repo-url> letsgo
cd letsgo

# 2. Copy and edit environment config (adjust DB passwords at minimum)
cp .env.example .env

# 3. Start the stack
docker compose up -d

# 4. Open the app
open http://localhost:8080
```

On first start, the database schema is created automatically from `docker/mysql/init.sql`.

---

## First Boot

When no users exist, the app auto-generates a **setup invite code**:

1. It is printed to the Docker log:
   ```bash
   docker logs letsgo_app
   ```
2. It is written to `storage/app_setup.log` inside the container.
3. It is shown directly on the **login page** as a banner.

Use this code on the `/register` page to create the first admin account.
After registration the code is consumed and the banner disappears.

---

## Environment Variables

All variables are read from the OS environment (injected by Docker Compose) or from a `.env` file in the project root (local development fallback).

| Variable | Default | Description |
|---|---|---|
| `DB_HOST` | `db` | MySQL host (Docker service name) |
| `DB_NAME` | `letsgo` | Database name |
| `DB_USER` | `letsgo` | MySQL username |
| `DB_PASS` | `letsgo_secret` | MySQL password |
| `APP_URL` | *(auto-detected)* | Public base URL, e.g. `https://letsgo.example.com`. Important behind a TLS-terminating reverse proxy. |

---

## Upgrading an Existing Database

When updating from an older schema, run the migration script:

```bash
docker exec -i letsgo_db mysql -u root -p<root_password> < docker/mysql/upgrade.sql
```

The script uses `information_schema` checks and `INSERT IGNORE` so it is **idempotent** — safe to run multiple times.

---

## Production Deployment

1. Set a strong `DB_PASS` in your `.env` or Docker Compose override.
2. Set `APP_URL` to your public HTTPS URL (needed for correct invite link generation).
3. Ensure `storage/` is writable by the web server user (for `app_setup.log`).
4. Put Apache or Nginx in front for TLS termination.
5. The `.htaccess` file routes all non-file requests to `public/index.php` — ensure `AllowOverride All` is set in Apache config.

---

## Directory Permissions

The only directory that needs to be writable at runtime is `storage/`.
All other directories should be readable by the web server but not writable.

```bash
chmod 755 storage/
```
