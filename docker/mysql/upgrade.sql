-- Letsgo upgrade migration
-- Compatible with MySQL 5.7+ and 8.x (no MariaDB-only syntax).
-- Safe to run multiple times.

USE letsgo;

-- ── Helper: add column only if it does not already exist ─────────────────────

SET @db = DATABASE();

-- users.username
SET @col = 'username';
SELECT COUNT(*) INTO @exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = @col;
SET @sql = IF(@exists = 0,
  'ALTER TABLE users ADD COLUMN username VARCHAR(50) NULL UNIQUE AFTER email',
  'SELECT "users.username already exists"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- users.is_banned
SET @col = 'is_banned';
SELECT COUNT(*) INTO @exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = @col;
SET @sql = IF(@exists = 0,
  'ALTER TABLE users ADD COLUMN is_banned TINYINT(1) NOT NULL DEFAULT 0 AFTER is_admin',
  'SELECT "users.is_banned already exists"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- users.created_at
SET @col = 'created_at';
SELECT COUNT(*) INTO @exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = @col;
SET @sql = IF(@exists = 0,
  'ALTER TABLE users ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER is_banned',
  'SELECT "users.created_at already exists"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- events.event_date
SET @col = 'event_date';
SELECT COUNT(*) INTO @exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'events' AND COLUMN_NAME = @col;
SET @sql = IF(@exists = 0,
  'ALTER TABLE events ADD COLUMN event_date DATE NULL AFTER location',
  'SELECT "events.event_date already exists"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- events.deadline_signup_text
SET @col = 'deadline_signup_text';
SELECT COUNT(*) INTO @exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'events' AND COLUMN_NAME = @col;
SET @sql = IF(@exists = 0,
  'ALTER TABLE events ADD COLUMN deadline_signup_text VARCHAR(100) NULL AFTER deadline_signup',
  'SELECT "events.deadline_signup_text already exists"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- events.deadline_decision_text
SET @col = 'deadline_decision_text';
SELECT COUNT(*) INTO @exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'events' AND COLUMN_NAME = @col;
SET @sql = IF(@exists = 0,
  'ALTER TABLE events ADD COLUMN deadline_decision_text VARCHAR(100) NULL AFTER deadline_decision',
  'SELECT "events.deadline_decision_text already exists"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ── password_resets table ────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS password_resets (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    token      VARCHAR(64)  NOT NULL UNIQUE,
    expires_at DATETIME     NOT NULL,
    created_at DATETIME     NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── event_time_slots table ───────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS event_time_slots (
    id          INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    event_id    INT UNSIGNED  NOT NULL,
    slot_date   DATE          NULL,
    slot_text   VARCHAR(255)  NULL,
    sort_order  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate existing event_date / date_text into event_time_slots (one-time, idempotent)
INSERT INTO event_time_slots (event_id, slot_date, slot_text, sort_order)
SELECT id, event_date, date_text, 0
FROM events
WHERE (event_date IS NOT NULL OR date_text IS NOT NULL)
  AND id NOT IN (SELECT DISTINCT event_id FROM event_time_slots);

-- ── Settings table ────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS settings (
    `key`   VARCHAR(100) NOT NULL PRIMARY KEY,
    value   VARCHAR(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (`key`, value) VALUES
    ('registration_open',      '0'),
    ('users_can_create_groups','1');

-- ── users.locale ─────────────────────────────────────────────────────────────

SET @col = 'locale';
SELECT COUNT(*) INTO @exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = @col;
SET @sql = IF(@exists = 0,
  'ALTER TABLE users ADD COLUMN locale VARCHAR(10) NOT NULL DEFAULT ''hu'' AFTER created_at',
  'SELECT "users.locale already exists"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ── groups.is_archived ────────────────────────────────────────────────────────

SET @col = 'is_archived';
SELECT COUNT(*) INTO @exists FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'groups' AND COLUMN_NAME = @col;
SET @sql = IF(@exists = 0,
  'ALTER TABLE `groups` ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0 AFTER owner_id',
  'SELECT "groups.is_archived already exists"');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ── rate_limits table ─────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS rate_limits (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key_hash   CHAR(64)     NOT NULL UNIQUE,  -- sha256(endpoint:ip)
    hits       INT UNSIGNED NOT NULL DEFAULT 1,
    window_end DATETIME     NOT NULL,
    INDEX idx_window_end (window_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── jobs table ────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS jobs (
    id         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    type       VARCHAR(64)   NOT NULL,
    payload    MEDIUMTEXT    NOT NULL,
    status     ENUM('pending','processing','done','failed') NOT NULL DEFAULT 'pending',
    attempts   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    run_at     DATETIME      NOT NULL,
    created_at DATETIME      NOT NULL,
    INDEX idx_pending_run_at (status, run_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── comments table ────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS comments (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id   INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    body       TEXT         NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    INDEX idx_event_id (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── slot_votes table ──────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS slot_votes (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slot_id   INT UNSIGNED NOT NULL,
    user_id   INT UNSIGNED NOT NULL,
    available TINYINT(1)   NOT NULL DEFAULT 1,
    UNIQUE KEY uq_slot_user (slot_id, user_id),
    FOREIGN KEY (slot_id) REFERENCES event_time_slots(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)            ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── notification_preferences table ───────────────────────────────────────────
-- Opt-out model: presence of a row means the notification type is DISABLED.

CREATE TABLE IF NOT EXISTS notification_preferences (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type    VARCHAR(64)  NOT NULL,
    UNIQUE KEY uq_user_type (user_id, type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
