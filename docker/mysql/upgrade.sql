-- Letsgo upgrade migration
-- Run this on existing databases to apply the new features.
-- Safe to run multiple times (uses IF NOT EXISTS / IGNORE).

USE letsgo;

-- 1. Users: username + is_banned
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS username   VARCHAR(50)  NULL UNIQUE AFTER email,
    ADD COLUMN IF NOT EXISTS is_banned  TINYINT(1)   NOT NULL DEFAULT 0 AFTER is_admin,
    ADD COLUMN IF NOT EXISTS created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER is_banned;

-- 2. Events: event_date + text alternatives for deadlines
ALTER TABLE events
    ADD COLUMN IF NOT EXISTS event_date             DATE         NULL AFTER location,
    ADD COLUMN IF NOT EXISTS deadline_signup_text   VARCHAR(100) NULL AFTER deadline_signup,
    ADD COLUMN IF NOT EXISTS deadline_decision_text VARCHAR(100) NULL AFTER deadline_decision;

-- 3. Settings table
CREATE TABLE IF NOT EXISTS settings (
    `key`   VARCHAR(100) NOT NULL PRIMARY KEY,
    value   VARCHAR(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO settings (`key`, value) VALUES
    ('registration_open',      '0'),
    ('users_can_create_groups','1');
