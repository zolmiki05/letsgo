-- Letsgo database schema
-- Character set: utf8mb4 for full Unicode support

CREATE DATABASE IF NOT EXISTS letsgo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE letsgo;

-- Users --------------------------------------------------------------------
CREATE TABLE users (
    id            INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(255)     NOT NULL UNIQUE,
    username      VARCHAR(50)      NULL UNIQUE,
    password_hash VARCHAR(255)     NOT NULL,
    is_admin      TINYINT(1)       NOT NULL DEFAULT 0,
    is_banned     TINYINT(1)       NOT NULL DEFAULT 0,
    created_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- App-level invite codes (required for registration) -----------------------
-- created_by NULL means system-generated on first boot
CREATE TABLE app_invites (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token      VARCHAR(64)  NOT NULL UNIQUE,
    created_by INT UNSIGNED NULL,
    used_by    INT UNSIGNED NULL,
    created_at DATETIME     NOT NULL,
    used_at    DATETIME     NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (used_by)    REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Groups -------------------------------------------------------------------
CREATE TABLE `groups` (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    owner_id   INT UNSIGNED NOT NULL,
    created_at DATETIME     NOT NULL,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Group members ------------------------------------------------------------
CREATE TABLE group_members (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id  INT UNSIGNED NOT NULL,
    user_id   INT UNSIGNED NOT NULL,
    joined_at DATETIME     NOT NULL,
    UNIQUE KEY uq_member (group_id, user_id),
    FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invite tokens (group join links) -----------------------------------------
CREATE TABLE invites (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id   INT UNSIGNED NOT NULL,
    token      VARCHAR(64)  NOT NULL UNIQUE,
    created_at DATETIME     NOT NULL,
    expires_at DATETIME     NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    FOREIGN KEY (group_id)   REFERENCES `groups`(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events (programme ideas) ------------------------------------------------
CREATE TABLE events (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id                INT UNSIGNED NOT NULL,
    creator_id              INT UNSIGNED NOT NULL,
    title                   VARCHAR(255) NOT NULL,
    description             TEXT,
    location                VARCHAR(255),
    event_date              DATE,
    date_text               VARCHAR(255),
    deadline_signup         DATE,
    deadline_signup_text    VARCHAR(100),
    deadline_decision       DATE,
    deadline_decision_text  VARCHAR(100),
    cost                    VARCHAR(100),
    notes                   TEXT,
    status                  ENUM('IDEA','DISCUSSING','FINAL','CANCELLED') NOT NULL DEFAULT 'IDEA',
    created_at              DATETIME NOT NULL,
    updated_at              DATETIME NOT NULL,
    FOREIGN KEY (group_id)   REFERENCES `groups`(id) ON DELETE CASCADE,
    FOREIGN KEY (creator_id) REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- App settings (key-value) ------------------------------------------------
CREATE TABLE settings (
    `key`   VARCHAR(100) NOT NULL PRIMARY KEY,
    value   VARCHAR(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (`key`, value) VALUES
    ('registration_open',      '0'),
    ('users_can_create_groups','1');

-- Password reset tokens -----------------------------------------------------
CREATE TABLE password_resets (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    token      VARCHAR(64)  NOT NULL UNIQUE,
    expires_at DATETIME     NOT NULL,
    created_at DATETIME     NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event time slots (multiple proposed dates per event) ----------------------
CREATE TABLE event_time_slots (
    id          INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    event_id    INT UNSIGNED  NOT NULL,
    slot_date   DATE          NULL,
    slot_text   VARCHAR(255)  NULL,
    sort_order  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Responses (per-user feedback on an event) --------------------------------
CREATE TABLE responses (
    id                INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    event_id          INT UNSIGNED   NOT NULL,
    user_id           INT UNSIGNED   NOT NULL,
    interest_level    TINYINT UNSIGNED NOT NULL,
    mood_level        TINYINT UNSIGNED NOT NULL,
    willingness_level TINYINT UNSIGNED NOT NULL,
    created_at        DATETIME NOT NULL,
    updated_at        DATETIME NOT NULL,
    UNIQUE KEY uq_response (event_id, user_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
