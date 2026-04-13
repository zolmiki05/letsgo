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
