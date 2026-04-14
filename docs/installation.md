# Installation & Operations

## Requirements

| Component | Version |
|---|---|
| Docker | 20.10+ |
| Docker Compose | v2 (`docker compose`) |
| openssl | any (used by `setup.sh`) |
| (Optional) PHP | 8.2+ for local dev without Docker |
| (Optional) MySQL | 8.0+ for local dev without Docker |

---

## Quick Start (Docker)

```bash
# 1. Clone the repository
git clone <repo-url> letsgo
cd letsgo

# 2. Generate .env with random credentials
bash setup.sh

# 3. Start the stack
docker compose up -d

# 4. Open the app
open http://localhost:8080
```

On first start, the database schema is created automatically from `docker/mysql/init.sql`.

---

## `setup.sh` — First-Time Environment Setup

`setup.sh` automates the creation of `.env` from `.env.example` and fills in random
credentials so you never accidentally run with default/shared passwords.

```
Usage: ./setup.sh
```

What it does:
- Copies `.env.example` → `.env` (prompts before overwriting an existing file)
- Generates a random 8-char hex suffix shared between the DB name and DB user
- Generates a 32-char hex `DB_PASS` and `DB_ROOT_PASS` using `openssl rand`

After running, open `.env` to configure optional variables:
- `MAIL_HOST`, `MAIL_PORT`, `MAIL_USER`, `MAIL_PASS`, `MAIL_FROM_ADDRESS` — SMTP settings for email notifications and password reset
- `APP_PORT` — host port for the app container (default `8080`)
- `APP_URL` — public base URL, required behind a reverse proxy or custom domain

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
| `APP_PORT` | `8080` | Host port mapped to the app container |
| `APP_URL` | *(auto-detected)* | Public base URL. Required behind a TLS-terminating reverse proxy. |
| `DB_HOST` | `db` | MySQL host (Docker service name) |
| `DB_NAME` | `letsgo` | Database name |
| `DB_USER` | `letsgo` | MySQL username |
| `DB_PASS` | *(none)* | MySQL user password |
| `DB_ROOT_PASS` | *(none)* | MySQL root password (used by `upgrade.sh` for backups) |
| `MAIL_HOST` | *(empty — mail disabled)* | SMTP server hostname |
| `MAIL_PORT` | `587` | SMTP port |
| `MAIL_ENCRYPTION` | `tls` | `tls` (STARTTLS), `ssl`, or empty |
| `MAIL_USER` | *(empty)* | SMTP username |
| `MAIL_PASS` | *(empty)* | SMTP password |
| `MAIL_FROM_ADDRESS` | *(empty)* | Sender address |
| `MAIL_FROM_NAME` | `Letsgo` | Sender display name |

---

## Upgrading an Existing Installation

Use `upgrade.sh` for a safe, fully automated upgrade:

```
Usage: ./upgrade.sh [backup-dir] [upgrade-sql]

  backup-dir   Where to store the dump   (default: ./backups)
  upgrade-sql  SQL migration file        (default: ./docker/mysql/upgrade.sql)
```

The script performs these steps in order:

| Step | Action |
|---|---|
| 1 | Ensures the DB container is running |
| 2 | Creates a timestamped `mysqldump` backup in `<backup-dir>` |
| 3 | Stops the full stack (`docker compose down`) |
| 4 | Starts the DB container only, waits for readiness |
| 5 | Applies the upgrade SQL file |
| 6 | Starts the full stack (`docker compose up -d`) |

Backup file naming: `<db_name>_YYYYMMDD_HHMMSS.sql`

The `docker/mysql/upgrade.sql` migration script uses `information_schema` checks and
`CREATE TABLE IF NOT EXISTS` / `INSERT IGNORE`, making it **idempotent** — safe to run
multiple times without side effects.

### Manual migration (lightweight, no backup or restart)

`migrate.sh` pipes the upgrade SQL directly into the running container without stopping
the stack. Use only when the stack is already up and you do not need a backup:

```bash
bash migrate.sh
```

---

## Production Deployment

1. Run `bash setup.sh` to generate strong random credentials in `.env`.
2. Set `APP_URL` to your public HTTPS URL (needed for correct invite link and password-reset link generation).
3. Configure `MAIL_*` variables — password reset requires a working SMTP connection.
4. Ensure `storage/` is writable by the web server user (for `app_setup.log`).
5. Put Apache or Nginx in front for TLS termination.
6. The `.htaccess` file routes all non-file requests to `public/index.php` — ensure `AllowOverride All` is set in Apache config.
7. Keep `backups/` outside the web root, or ensure it is not served by the web server.

---

## Directory Permissions

The only directory that needs to be writable at runtime is `storage/`.
All other directories should be readable by the web server but not writable.

```bash
chmod 755 storage/
```
