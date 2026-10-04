<?php
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

function cookbook_db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host = getenv('COOKBOOK_DB_HOST') ?: 'localhost';
        $database = getenv('COOKBOOK_DB_NAME') ?: 'cookbook';
        $username = getenv('COOKBOOK_DB_USER') ?: 'root';
        $password = getenv('COOKBOOK_DB_PASS') ?: '';
        $pdo = new PDO(
            "mysql:host={$host};dbname={$database};charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(80) NOT NULL,
            email VARCHAR(254) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
            full_name VARCHAR(120) NULL,
            location VARCHAR(120) NULL,
            profile_photo VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(120) NULL');
        $pdo->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS location VARCHAR(120) NULL');
        $pdo->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) NULL');
        $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
            reset_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token_hash CHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id)
                REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec('ALTER TABLE recipes ADD COLUMN IF NOT EXISTS owner_id INT UNSIGNED NULL');
        $pdo->exec('ALTER TABLE recipes ADD COLUMN IF NOT EXISTS photo VARCHAR(255) NULL');
        $pdo->exec("CREATE TABLE IF NOT EXISTS recipe_media (
            media_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            recipe_id INT NOT NULL,
            media_path VARCHAR(255) NOT NULL,
            media_type ENUM('image', 'video') NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (recipe_id),
            CONSTRAINT fk_recipe_media_recipe FOREIGN KEY (recipe_id)
                REFERENCES recipes(recipe_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS favorites (
            favorite_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            recipe_id INT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_favorites_user_recipe (user_id, recipe_id),
            CONSTRAINT fk_favorites_user FOREIGN KEY (user_id)
                REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_favorites_recipe FOREIGN KEY (recipe_id)
                REFERENCES recipes(recipe_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS recipe_ratings (
            rating_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            recipe_id INT NOT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_recipe_ratings_user_recipe (user_id, recipe_id),
            CONSTRAINT fk_recipe_ratings_user FOREIGN KEY (user_id)
                REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_recipe_ratings_recipe FOREIGN KEY (recipe_id)
                REFERENCES recipes(recipe_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS recipe_comments (
            comment_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            recipe_id INT NOT NULL,
            comment_text TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_recipe_comments_recipe (recipe_id),
            CONSTRAINT fk_recipe_comments_user FOREIGN KEY (user_id)
                REFERENCES users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_recipe_comments_recipe FOREIGN KEY (recipe_id)
                REFERENCES recipes(recipe_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_collections (
            collection_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            owner_id INT UNSIGNED NOT NULL,
            name VARCHAR(100) NOT NULL,
            description VARCHAR(500) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_user_collections_owner_name (owner_id, name),
            CONSTRAINT fk_user_collections_owner FOREIGN KEY (owner_id)
                REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_collection_recipes (
            collection_id INT UNSIGNED NOT NULL,
            recipe_id INT NOT NULL,
            added_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (collection_id, recipe_id),
            CONSTRAINT fk_user_collection_recipes_collection FOREIGN KEY (collection_id)
                REFERENCES user_collections(collection_id) ON DELETE CASCADE,
            CONSTRAINT fk_user_collection_recipes_recipe FOREIGN KEY (recipe_id)
                REFERENCES recipes(recipe_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    return $pdo;
}

function cookbook_current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function cookbook_require_login(): void
{
    if (!cookbook_current_user()) {
        header('Location: login.php');
        exit;
    }

    header('Cache-Control: no-store, private');
    header('Pragma: no-cache');
}

function cookbook_require_role(string $role): void
{
    cookbook_require_login();

    if ((cookbook_current_user()['role'] ?? '') !== $role) {
        http_response_code(403);
        exit('Access denied. This page requires ' . htmlspecialchars($role, ENT_QUOTES, 'UTF-8') . ' access.');
    }
}

function cookbook_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function cookbook_csrf_is_valid(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}
