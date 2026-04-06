# Letsgo

> **What should the next group activity be?**

Letsgo is a simple, invite-only web application for friend groups. Collect programme ideas, give detailed feedback, and collaboratively decide what to do next.

**Primary development happens on GitLab. The GitHub repository is a mirror only.**

---

## Features (MVP)

- Session-based user registration and login
- Create groups and manage members
- Invite new members via time-limited links (24 h)
- Create programme ideas with rich optional fields (location, date, deadlines, cost, notes)
- Three-scale feedback system (interest / current mood / willingness), 1–10 per scale
- Programme status management (Idea → Discussing → Final / Cancelled)
- Upcoming deadlines highlighted on the dashboard
- All UI text served from a JSON language file (currently: Hungarian)

---

## Tech stack

| Layer      | Technology                  |
|------------|-----------------------------|
| Backend    | PHP 8.2 (plain, no framework) |
| Database   | MySQL 8.0                   |
| Frontend   | HTML5 + CSS3 + minimal JS   |
| Auth       | PHP sessions (`$_SESSION`)  |
| Container  | Docker + Docker Compose     |

---

## Project structure

```
letsgo/
├── docker/
│   ├── php/
│   │   └── Dockerfile          # PHP 8.2 Apache image
│   └── mysql/
│       └── init.sql            # Database schema
├── lang/
│   └── hu.json                 # All user-visible strings (Hungarian)
├── public/                     # Apache document root
│   ├── index.php               # Front controller
│   ├── .htaccess               # mod_rewrite rules
│   └── assets/
│       ├── css/app.css
│       └── js/app.js
├── src/
│   ├── bootstrap.php           # Class loader + helper functions
│   ├── Core/
│   │   ├── Database.php        # PDO singleton
│   │   ├── Lang.php            # JSON translation loader
│   │   ├── Router.php          # Front-controller router
│   │   └── Session.php         # Session + flash messages
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── EventController.php
│   │   ├── GroupController.php
│   │   └── InviteController.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Group.php
│   │   ├── Invite.php
│   │   ├── Event.php
│   │   └── Response.php
│   └── Views/
│       ├── layout/
│       │   ├── header.php
│       │   └── footer.php
│       ├── auth/
│       ├── dashboard/
│       ├── event/
│       ├── group/
│       ├── invite/
│       └── errors/
├── docker-compose.yml
└── README.md
```

---

## Getting started

### Requirements

- [Docker](https://docs.docker.com/get-docker/) and Docker Compose

### Run

```bash
docker compose up --build
```

The application is available at **http://localhost:8080**.

On first start MySQL runs `docker/mysql/init.sql` automatically and creates the schema.

### Stop

```bash
docker compose down
```

To also delete the database volume:

```bash
docker compose down -v
```

---

## Architecture notes

### Routing

All HTTP requests hit `public/index.php` (the front controller) via an Apache `mod_rewrite` rule. The `Router` class matches request method + URI against registered patterns and dispatches to the appropriate `Controller@method`. Named URL segments (`/groups/{id}`) are extracted as parameters.

### Language / i18n

All user-visible strings are stored in `lang/hu.json`. The `Lang::t('dot.notation.key')` helper performs a lookup at runtime. To add a new language, copy `hu.json`, translate the values, and change the file loaded in `src/bootstrap.php`.

### Data access

Each model is a static class that receives a PDO instance from the `Database` singleton. All queries use prepared statements with bound parameters.

### Permissions summary

| Action                    | Who can do it              |
|---------------------------|----------------------------|
| Create a group            | Any logged-in user         |
| Delete a group            | Group admin (owner) only   |
| Generate an invite link   | Group admin (owner) only   |
| Join via invite           | Any logged-in user         |
| Create a programme idea   | Any group member           |
| Change programme status   | Creator of the idea only   |
| Submit / update feedback  | Any group member           |

---

## Localisation

To create a new locale:

1. Copy `lang/hu.json` to e.g. `lang/en.json`
2. Translate all values (keys must remain identical)
3. In `src/bootstrap.php`, change `Lang::load(ROOT . '/lang/hu.json')` to point to the new file

No code changes beyond the loader call are needed.

---

## Development

The primary repository is on **GitLab**. GitHub is a read-only mirror.

All feature work happens on the `development` branch. Commits are made when the code reaches a defined milestone (MVP feature complete).
