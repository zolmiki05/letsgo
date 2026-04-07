# Database Schema

**Engine:** InnoDB · **Charset:** utf8mb4\_unicode\_ci · **DB name:** `letsgo`

---

## Tables Overview

| Table | Purpose |
|---|---|
| `users` | Registered accounts |
| `app_invites` | Single-use registration codes |
| `groups` | Programme groups |
| `group_members` | Group membership junction |
| `invites` | 24-hour group join links |
| `events` | Programme ideas within a group |
| `responses` | Per-user 3-scale feedback on events |
| `settings` | Application-wide key/value configuration |

---

## `users`

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | Auto-increment |
| `email` | VARCHAR(255) UNIQUE | Login identifier |
| `username` | VARCHAR(50) UNIQUE NULL | Display name; also accepted as login identifier |
| `password_hash` | VARCHAR(255) | bcrypt via `password_hash(PASSWORD_DEFAULT)` |
| `is_admin` | TINYINT(1) | `1` = admin, `0` = regular user |
| `is_banned` | TINYINT(1) | `1` = login refused, `0` = active |
| `created_at` | DATETIME | Set on insert via `DEFAULT CURRENT_TIMESTAMP` |

**Notes:**
- The first user ever created gets `is_admin = 1` automatically.
- Banned users cannot log in; `User::verify()` returns `null` for them.
- Deleting a user cascades to: owned groups → events, group_members, invites, responses.

---

## `app_invites`

Single-use codes required to register a new account (when open registration is disabled).

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `token` | VARCHAR(64) UNIQUE | 12-char uppercase hex, e.g. `A1B2C3D4E5F6` |
| `created_by` | INT UNSIGNED NULL FK→users | `NULL` = system-generated (first boot) |
| `used_by` | INT UNSIGNED NULL FK→users | `NULL` = not yet consumed |
| `created_at` | DATETIME | |
| `used_at` | DATETIME NULL | Set when the code is consumed |

**FK behavior:** `ON DELETE SET NULL` for both `created_by` and `used_by`.

---

## `groups`

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `name` | VARCHAR(255) | Display name |
| `owner_id` | INT UNSIGNED FK→users | Creator; has delete/invite rights |
| `created_at` | DATETIME | |

**FK behavior:** `owner_id ON DELETE CASCADE` (group deleted when owner is deleted).

---

## `group_members`

Junction table: one row per (group, user) pair.

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `group_id` | INT UNSIGNED FK→groups | `ON DELETE CASCADE` |
| `user_id` | INT UNSIGNED FK→users | `ON DELETE CASCADE` |
| `joined_at` | DATETIME | |

**Unique constraint:** `uq_member (group_id, user_id)` — prevents duplicate membership.  
`INSERT IGNORE` is used when adding members, making the operation idempotent.

---

## `invites`

Time-limited group join tokens (24-hour expiry).

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `group_id` | INT UNSIGNED FK→groups | `ON DELETE CASCADE` |
| `token` | VARCHAR(64) UNIQUE | 32-char lowercase hex |
| `created_at` | DATETIME | |
| `expires_at` | DATETIME | Always `created_at + 24 hours` |
| `created_by` | INT UNSIGNED FK→users | Group owner who generated the link |

**Notes:**
- Multiple tokens can exist per group over time.
- Only the most recent non-expired token is shown in the UI (`Invite::latestForGroup()`).
- Any number of users can use the same token within the 24h window.

---

## `events`

Programme ideas proposed within a group.

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `group_id` | INT UNSIGNED FK→groups | `ON DELETE CASCADE` |
| `creator_id` | INT UNSIGNED FK→users | `ON DELETE CASCADE` |
| `title` | VARCHAR(255) | Required |
| `description` | TEXT NULL | Optional |
| `location` | VARCHAR(255) NULL | Optional |
| `event_date` | DATE NULL | Structured date (used in calendar display) |
| `date_text` | VARCHAR(255) NULL | Free-text date ("next weekend", "TBD") |
| `deadline_signup` | DATE NULL | Structured signup deadline (used in 7-day widget) |
| `deadline_signup_text` | VARCHAR(100) NULL | Free-text signup deadline |
| `deadline_decision` | DATE NULL | Structured decision deadline (used in 7-day widget) |
| `deadline_decision_text` | VARCHAR(100) NULL | Free-text decision deadline |
| `cost` | VARCHAR(100) NULL | Estimated cost per person |
| `notes` | TEXT NULL | Additional conditions or notes |
| `status` | ENUM | `IDEA` \| `DISCUSSING` \| `FINAL` \| `CANCELLED` |
| `created_at` | DATETIME | |
| `updated_at` | DATETIME | Refreshed on every status change |

**Date field pairs:**  
Each date has a structured (DATE) column and a free-text (VARCHAR) alternative.
Only one of the two should be non-null per field. Only the DATE columns participate
in the upcoming-deadline calculation.

**Status lifecycle:**
```
IDEA → DISCUSSING → FINAL
                  ↘ CANCELLED
```

---

## `responses`

Per-user three-scale feedback on a programme idea.

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `event_id` | INT UNSIGNED FK→events | `ON DELETE CASCADE` |
| `user_id` | INT UNSIGNED FK→users | `ON DELETE CASCADE` |
| `interest_level` | TINYINT UNSIGNED | 1–10: general interest in this type of activity |
| `mood_level` | TINYINT UNSIGNED | 1–10: how much the user feels like it right now |
| `willingness_level` | TINYINT UNSIGNED | 1–10: actual willingness to go |
| `created_at` | DATETIME | |
| `updated_at` | DATETIME | Refreshed on each edit |

**Unique constraint:** `uq_response (event_id, user_id)` — one response per user per event.

---

## `settings`

Application-wide key/value configuration.

| Column | Type | Notes |
|---|---|---|
| `key` | VARCHAR(100) PK | Setting identifier |
| `value` | VARCHAR(255) | Always stored as string |

**Current settings:**

| Key | Default | Meaning |
|---|---|---|
| `registration_open` | `0` | `1` = anyone can register; `0` = invite code required |
| `users_can_create_groups` | `1` | `0` = only admins can create groups |

---

## Entity Relationship Diagram (text)

```
users ──< app_invites   (created_by, used_by)
users ──< groups        (owner_id)
users ──< group_members (user_id)
users ──< invites       (created_by)
users ──< events        (creator_id)
users ──< responses     (user_id)

groups ──< group_members (group_id)
groups ──< invites       (group_id)
groups ──< events        (group_id)

events ──< responses     (event_id)
```

---

## Migrations

| File | Purpose |
|---|---|
| `docker/mysql/init.sql` | Full schema for fresh installs (auto-run by Docker) |
| `docker/mysql/upgrade.sql` | Additive migration for existing databases |

Run the upgrade script manually:
```bash
docker exec -i letsgo_db mysql -u root -p<password> < docker/mysql/upgrade.sql
```
