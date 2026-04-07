# Letsgo

> **What should the next group activity be?**

Letsgo is a web application for friend groups, teams, and communities to collect programme ideas, rate them as a group, and collaboratively decide what to do next.

**Version:** 1.0.0 · **Primary repo:** GitLab · **GitHub:** read-only mirror

---

## Features

- **Accounts** — invite-code-gated or open registration; login by email or username
- **Groups** — create groups, invite members via 24-hour links, manage membership
- **Programme ideas** — propose events with title, description, location, date, deadlines, cost, notes
- **Flexible dates** — each date field accepts either a date picker or free text ("next weekend", "TBD")
- **Three-scale feedback** — rate each idea on Interest / Mood / Willingness (1–10); group averages shown as progress bars
- **Status lifecycle** — Idea → Discussing → Final / Cancelled (creator-managed)
- **Upcoming deadlines** — dashboard widget for events with deadlines in the next 7 days
- **Admin panel** — user management (ban/unban/delete), registration toggle, group-creation toggle, invite code management
- **Password change** — logged-in users can update their password
- **Invite link persistence** — unauthenticated users who follow a group invite link are redirected back after login
- **Dark/light theme** — toggle with `localStorage` persistence
- **Hungarian UI** — all strings in `lang/hu.json`; easily translatable

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2 (no framework) |
| Database | MySQL 8.x |
| Frontend | HTML5 + CSS3 + vanilla JS |
| Auth | PHP sessions |
| Container | Docker + Docker Compose |

---

## Quick Start

```bash
# Clone
git clone <repo-url> letsgo && cd letsgo

# Configure (adjust DB passwords at minimum)
cp .env.example .env

# Start
docker compose up -d

# Open
open http://localhost:8080
```

On first start the **setup invite code** appears on the login page — use it to create the first admin account.

To stop:
```bash
docker compose down          # keep data
docker compose down -v       # also delete DB volume
```

---

## Upgrading an Existing Database

```bash
docker exec -i letsgo_db mysql -u root -p<password> < docker/mysql/upgrade.sql
```

The script is idempotent (safe to run multiple times).

---

## Project Structure

```
letsgo/
├── docker/
│   ├── mysql/
│   │   ├── init.sql        # Schema (fresh install)
│   │   └── upgrade.sql     # Migration (existing DB)
│   └── php/Dockerfile      # PHP 8.2 + Apache
├── docs/                   # Full documentation (wiki-ready)
├── lang/hu.json            # All UI strings (Hungarian)
├── public/                 # Apache document root
│   ├── index.php           # Front controller
│   ├── .htaccess           # mod_rewrite rules
│   └── assets/css & js
├── src/
│   ├── bootstrap.php       # Loader + global helpers
│   ├── Core/               # Database, Session, Lang, Router
│   ├── Models/             # User, Group, Invite, AppInvite, Event, Response, Setting
│   ├── Controllers/        # Auth, Admin, Dashboard, Group, Event, Invite
│   └── Views/              # PHP templates (layout + per-feature)
├── storage/                # Writable at runtime (setup log)
├── CHANGELOG.md
├── VERSION
└── docker-compose.yml
```

---

## Documentation

Full documentation lives in the [`docs/`](docs/) directory:

| Doc | Contents |
|---|---|
| [Installation](docs/installation.md) | Docker setup, env vars, production checklist |
| [Architecture](docs/architecture.md) | Stack, request lifecycle, security model |
| [Database](docs/database.md) | Schema reference, ERD, migrations |
| [Routes](docs/routes.md) | All endpoints and access levels |
| [Features](docs/features.md) | Detailed feature descriptions |
| [Admin Guide](docs/admin-guide.md) | Admin panel walkthrough |

---

## Localisation

1. Copy `lang/hu.json` → `lang/en.json` (or any locale)
2. Translate the values (keys must stay identical)
3. Change `Lang::load(ROOT . '/lang/hu.json')` in `src/bootstrap.php` to the new file

---

## Permissions Summary

| Action | Who |
|---|---|
| Create a group | Any user (unless admin-restricted) |
| Delete a group | Group owner |
| Generate invite link | Group owner |
| Join a group | Any authenticated user with a valid token |
| Create a programme idea | Any group member |
| Change status / delete idea | Creator only |
| Submit / update feedback | Any group member |
| Admin panel | Admin accounts only |
| Ban / delete users | Admin only |

---

## Development

Primary repository is on **GitLab**; GitHub is a read-only mirror.
Feature work happens on the `development` branch.
