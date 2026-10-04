-- CookBook user account and profile tables
-- Import after creating/selecting the cookbook database.

USE cookbook;

CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL,
    email VARCHAR(254) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'moderator', 'admin') NOT NULL DEFAULT 'user',
    full_name VARCHAR(120) NULL,
    location VARCHAR(120) NULL,
    profile_photo VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- If the users table already existed, add the new profile columns safely.
ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(120) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS location VARCHAR(120) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) NULL;
ALTER TABLE users MODIFY role ENUM('user', 'moderator', 'admin') NOT NULL DEFAULT 'user';

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    reset_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id)
        REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
