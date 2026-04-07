# Architecture

## Technology Stack

| Layer | Technology | Notes |
|---|---|---|
| Language | PHP 8.2 | No framework |
| Database | MySQL 8.x | PDO, prepared statements |
| Web server | Apache 2.4 | mod_rewrite, `.htaccess` routing |
| Frontend | HTML5 + CSS3 + vanilla JS | No build step, no npm |
| Fonts | Google Fonts (Bricolage Grotesque, DM Sans) | Loaded via CDN |
| Container | Docker + Docker Compose | Two services: `app` + `db` |
| Sessions | PHP native sessions | Named `letsgo_session` |
| Auth | Session-based | No JWT, no OAuth |

---

## Directory Structure

```
letsgo/
├── docker/
│   ├── mysql/
│   │   ├── init.sql          # Schema for fresh installs
│   │   └── upgrade.sql       # Migration for existing DBs
│   └── php/
│       └── Dockerfile        # PHP 8.2 + Apache image
├── docs/                     # This documentation
├── lang/
│   └── hu.json               # Hungarian UI strings (all user-visible text)
├── public/                   # Apache document root
│   ├── index.php             # Front controller (single entry point)
│   ├── .htaccess             # Rewrites all requests to index.php
│   └── assets/
│       ├── css/app.css       # Single stylesheet ("Signal" design system)
│       └── js/app.js         # Minimal vanilla JS (theme toggle, sliders)
├── src/
│   ├── bootstrap.php         # Class loader, session start, global helpers
│   ├── Core/
│   │   ├── Database.php      # PDO singleton
│   │   ├── Session.php       # Session wrapper + flash messages
│   │   ├── Lang.php          # JSON-backed i18n (dot-notation keys)
│   │   └── Router.php        # Front-controller router ({param} segments)
│   ├── Models/
│   │   ├── User.php          # Users, auth, ban/delete
│   │   ├── Group.php         # Groups, membership
│   │   ├── Invite.php        # Group join tokens (24h)
│   │   ├── AppInvite.php     # Registration invite codes (single-use)
│   │   ├── Event.php         # Programme ideas, deadlines
│   │   ├── Response.php      # 3-scale member feedback
│   │   └── Setting.php       # Key/value app settings
│   ├── Controllers/
│   │   ├── AuthController.php      # Login, register, logout, password change
│   │   ├── AdminController.php     # Admin panel (users, settings, invite codes)
│   │   ├── DashboardController.php # Main page (groups + upcoming deadlines)
│   │   ├── GroupController.php     # Group CRUD + invite generation
│   │   ├── EventController.php     # Event CRUD + status + feedback
│   │   └── InviteController.php    # Group join via token
│   └── Views/
│       ├── layout/
│       │   ├── header.php    # HTML head + nav bar
│       │   └── footer.php    # Closing tags + JS
│       ├── auth/
│       │   ├── login.php
│       │   └── register.php
│       ├── admin/
│       │   ├── settings.php
│       │   ├── invites.php
│       │   └── users.php
│       ├── dashboard/index.php
│       ├── group/
│       │   ├── show.php
│       │   └── create.php
│       ├── event/
│       │   ├── show.php
│       │   └── create.php
│       ├── invite/join.php
│       ├── profile/password.php
│       └── errors/404.php
├── storage/                  # Writable at runtime (logs)
├── docker-compose.yml
├── .env.example
├── CHANGELOG.md
└── VERSION
```

---

## Request Lifecycle

```
Browser request
    │
    ▼
Apache (.htaccess)
    │  mod_rewrite: all non-file requests → public/index.php
    ▼
public/index.php
    │  require_once src/bootstrap.php
    │    ├─ load .env
    │    ├─ require Core classes
    │    ├─ require Models
    │    ├─ require Controllers
    │    ├─ Session::start()
    │    ├─ Lang::load()
    │    └─ _maybeGenerateSetupInvite()
    │
    │  register all routes on $router
    │
    ▼
Router::dispatch()
    │  match method + URI → ControllerClass@method
    │  extract {param} segments
    ▼
Controller method(array $params)
    │  requireAuth() / requireAdmin()  ← redirect to /login if needed
    │  read $_POST / $_GET
    │  call Model static methods
    │  Session::flash() for messages
    ▼
render('view/name', [...data...])
    │  extract $data into local vars
    │  require layout/header.php
    │  require src/Views/<view>.php
    │  require layout/footer.php
    ▼
HTTP Response
```

---

## Design Principles

- **No framework.** Routing, ORM, templating, and auth are all custom-built and minimal.
- **Static methods on models.** Models are simple classes with static query methods; no instantiation or ActiveRecord pattern.
- **PDO everywhere.** All queries use prepared statements with bound parameters — no string interpolation of user input.
- **Single language file.** All user-visible strings live in `lang/hu.json`. Swap the file to translate the UI.
- **Flash messages.** One-request session messages pass feedback between controller redirects and views.
- **No build step.** CSS and JS are plain files; no webpack, no npm, no transpilation.

---

## Security Model

| Concern | Approach |
|---|---|
| SQL injection | PDO prepared statements throughout |
| XSS | All output goes through `e()` (htmlspecialchars) |
| Session fixation | `session_regenerate_id(true)` on login |
| CSRF | POST-only state-changing routes; forms are session-scoped |
| Password storage | `password_hash(PASSWORD_DEFAULT)` (bcrypt) |
| Auth gates | `requireAuth()` / `requireAdmin()` called at top of each protected method |
| Banned users | `User::verify()` returns null for banned accounts |
