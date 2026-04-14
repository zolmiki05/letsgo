# Changelog

All notable changes to Letsgo are documented here.
Format loosely follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [1.3.0] – 2026-04-14

### Added
- **Slot voting** — group members can vote available/unavailable on each proposed time slot; aggregate counts shown on the event detail page.
- **Comment threads** — members can post comments on events; comment authors and admins can delete their own comments; new comments dispatch email notifications via the queue.
- **Notification preferences** — per-user opt-out settings (`/profile/notifications`); seven notification types individually switchable; opt-out model (row present = disabled).
- **Group archiving** — group owners can archive/unarchive groups; archived groups are hidden from the main dashboard but accessible directly; shown in a separate "Archived" section on the dashboard.
- **Queue-based email sending** — all group notification emails now dispatch via `Queue::dispatch('send_email', …)` instead of synchronous SMTP; reduces request latency and allows retries. Transactional emails (password reset, invite, password changed) remain synchronous.
- **Rate limiting** — `RateLimiter` class (DB-backed sliding window); login endpoint: 10 attempts/5 min; forgot-password: 5 attempts/10 min.
- **CSRF protection** — `Csrf` class generates a per-session token; `Router::dispatch()` calls `Csrf::verify()` for every POST; all views updated with `<?= csrfField() ?>`.
- **English locale** — full `lang/en.json` translation; users can switch language via the nav bar toggle; preference persisted to `users.locale`.
- **PHPUnit test suite** — `composer.json` + `phpunit.xml`; unit tests for `Csrf`, `Router`, `Database`; integration tests for `Queue`; test bootstrap at `tests/bootstrap.php`.
- **`bin/worker.php`** — CLI queue worker; processes `send_email` jobs; exponential back-off retries; probabilistic job pruning; intended for cron (`* * * * *`).
- **DB connection pooling** — `PDO::ATTR_PERSISTENT` enabled; `Database::reset()` for test isolation.

### Changed
- `Group::forUser()` — accepts `$archivedOnly` flag; filters by `is_archived` column.
- `Group::members()` — now returns `username` alongside `id` and `email`.
- `User::findById()` — now includes `locale` column in the result set.
- `Mailer::renderEmail()` — made public (used by worker).
- Bootstrap locale loading — language file chosen based on logged-in user's `locale` preference; falls back to Hungarian.

### Database
- New `users.locale` column (VARCHAR 10, default `'hu'`).
- New `groups.is_archived` column (TINYINT, default `0`).
- New `rate_limits` table with UNIQUE `key_hash` index.
- New `jobs` table with `pending_run_at` composite index.
- New `comments` table (FK to `events`, `users`).
- New `slot_votes` table with UNIQUE `(slot_id, user_id)`.
- New `notification_preferences` table with UNIQUE `(user_id, type)`.

---

## [1.2.0] – 2026-04-14

### Added
- **Password reset flow** — users can request a reset link via email (`/password/reset`); time-limited token stored in `password_resets` table; new password submitted via `/password/reset/{token}`.
- **`setup.sh`** — interactive first-time setup script: copies `.env.example` → `.env` and auto-generates random DB name, DB user, DB password, and root password using `openssl rand`.
- **`upgrade.sh`** — full upgrade runner: stops the stack, creates a timestamped database backup, applies `docker/mysql/upgrade.sql`, and restarts the stack. Replaces the simpler `migrate.sh` workflow.
- **`backups/`** — git-tracked directory (contents excluded) for database backups created by `upgrade.sh`.

### Fixed
- **SMTP EPIPE / write error** — strip port suffix from the EHLO hostname; add explicit write-error handling in the SMTP socket layer to prevent broken-pipe crashes on some mail servers.

### Changed
- **Email templates reskinned** — all `src/Views/email/` templates updated to match the "Signal" design system: accent `#3451D1`, `DM Sans` + `Bricolage Grotesque` fonts (via Google Fonts), correct light-mode surface/border/text tokens, status badge colours matching `app.css`, solid CTA buttons replacing the Tailwind-indigo gradients.
- **Mailer logging** — `send()` and raw SMTP commands are now logged to the PHP error log for delivery diagnosis; helps troubleshoot mail delivery without enabling full debug output in production.

---

## [1.1.0] – 2026-04-07

### Added
- **Multiple time slots per event** — an event can now carry any number of proposed date/time options, each stored as a row in the new `event_time_slots` table.
  - Existing `event_date` / `date_text` values are migrated into the new table on first schema upgrade (idempotent).
- **HTML email notifications** — outgoing emails are sent with both a plain-text and an HTML part; HTML template uses inline styles for broad mail-client compatibility.

---

## [1.0.3] – 2026-04-07

### Added
- **Group rename** — the group owner can rename the group from an inline form in the sidebar (`POST /groups/{id}/rename`).

### Fixed
- **Event card creator alignment** — "Hozzáadta" is now a `flex-basis: 100%` span inside the meta flex container, guaranteeing identical left alignment with the date/location row above it.
- **Success flash messages** — group page now displays the actual flash message text instead of a hardcoded invite string, so rename and invite confirmations both show correctly.

---

## [1.0.2] – 2026-04-07

### Added
- **SVG favicon** (`/assets/favicon.svg`) — blue rounded-square with a white right-pointing arrow; linked via `<link rel="icon">` in the layout header.

### Fixed
- **Event card creator alignment** — "Hozzáadta" row now uses `display: flex; align-items: center` matching the meta row, so the left edge lines up correctly with the title and date fields.

---

## [1.0.1] – 2026-04-07

### Added
- **Creator attribution on event cards** — each programme idea card in the group view now shows who added it (username if set, otherwise email).
- **Event editing** — the event creator can edit all fields of a programme idea (title, description, location, date, deadlines, cost, notes) via a new `/events/{id}/edit` page.
  - The edit form pre-fills all current values, including the correct date/text mode for each date field.
  - Access is restricted to the creator; other members receive a flash error and are redirected.

### Changed
- `Event::forGroup()` now also returns `creator_username` alongside the existing `creator_email`.

---

## [1.0.0] – 2026-04-07

### Added

#### Authentication & Accounts
- **Username field** — users register with a username (3–30 chars, alphanumeric + `.` `_` `-`); displayed in the nav bar and usable as a login identifier.
- **Login by email or username** — the login field accepts either an email address or a username.
- **Password change** — logged-in users can change their password at `/profile/password`; the current password is verified before accepting the new one.
- **Banned account message** — banned users see a specific error message on login instead of the generic "invalid credentials".

#### Admin Panel
- **Settings page** (`/admin/settings`) — central admin hub with links to all admin sub-pages.
- **Open registration toggle** — admin can allow anyone to register without an invite code.
- **Group creation toggle** — admin can restrict group creation to admins only.
- **User management** (`/admin/users`) — full user list with ban/unban/delete controls.
  - Ban: prevents login without deleting data.
  - Unban: restores login access.
  - Delete: hard-delete with cascade (groups, events, responses, memberships).
  - Self-protection: admins cannot ban or delete their own account.

#### Invite Link Preservation
- When an unauthenticated user follows a group invite link (`/join?token=…`), the URL is saved to session.
- After login, the user is redirected back to the invite page instead of the dashboard.
- Prevents token loss when sharing links with users who are not yet logged in.

#### Event Dates
- **Date/text toggle** for all three date fields:
  - Event date (`event_date` DATE or `date_text` VARCHAR)
  - Signup deadline (`deadline_signup` DATE or `deadline_signup_text` VARCHAR)
  - Decision deadline (`deadline_decision` DATE or `deadline_decision_text` VARCHAR)
- Radio toggle in the create form switches between a date picker and a free-text input.
- Free-text mode allows informal expressions like "next weekend", "TBD", "sometime in July".
- Only structured DATE fields participate in the upcoming-deadline dashboard widget.

#### Infrastructure
- **`Setting` model** — key/value application settings persisted in the `settings` table.
- **`docker/mysql/upgrade.sql`** — additive migration for existing databases; uses `information_schema` checks for MySQL compatibility (no `ADD COLUMN IF NOT EXISTS`).
- **`VERSION` file** — single source of truth for the current version number.
- **`CHANGELOG.md`** — this file.
- **`docs/`** — comprehensive project documentation:
  - `index.md` — overview and table of contents
  - `installation.md` — Docker setup, first boot, env vars, production checklist
  - `architecture.md` — stack, directory structure, request lifecycle, security model
  - `database.md` — full schema reference with ERD
  - `routes.md` — all HTTP endpoints with auth levels
  - `features.md` — complete feature descriptions
  - `admin-guide.md` — admin panel usage guide

---

## [0.2.0] – 2026-04-07 *(pre-release)*

### Added
- **Admin invite code management** (`/admin/invites`) — generate single-use registration codes; view all codes with status and consuming user.
- **Username** and **is_banned** fields added to `users` table.
- **`created_at`** column added to `users` table.

---

## [0.1.0] – 2026-04-07 *(MVP)*

Initial implementation.

### Added

#### Core Infrastructure
- PHP 8.2 application with no framework — custom Router, Session, Lang, Database (PDO singleton).
- Docker Compose stack: PHP/Apache app container + MySQL 8 database container.
- `.env` file support for local development outside Docker.
- Hungarian UI via `lang/hu.json` dot-notation translation system.
- Dark/light theme toggle with `localStorage` persistence.
- Responsive CSS design system ("Signal") using CSS custom properties.

#### Authentication
- Invite-code-gated registration (single-use `AppInvite` codes).
- First-boot auto-setup: system invite code generated and displayed on login page + Docker logs.
- First user automatically becomes admin.
- Session-based login with `session_regenerate_id` on authenticate.
- Logout destroys session.

#### Groups
- Create, view, and delete groups.
- Owner is auto-added as first member on creation.
- Member list with owner badge in group sidebar.
- 24-hour group invite token generation and copy-to-clipboard UI.

#### Programme Ideas (Events)
- Create events within a group (title required; description, location, date text, deadlines, cost, notes optional).
- Event status lifecycle: `IDEA → DISCUSSING → FINAL / CANCELLED` (creator only).
- Delete event (creator only, cascade-removes responses).
- Event detail page with all metadata.

#### Feedback System
- Three-scale rating per member per event: Interest / Mood / Willingness (1–10).
- Upsert pattern: first submission inserts, subsequent edits update.
- Group averages displayed as progress bars with response count.
- Individual response list in event sidebar.

#### Dashboard
- Group grid with member counts.
- Upcoming deadlines widget: events with a structured deadline in the next 7 days, across all groups.
