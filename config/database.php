<?php

declare(strict_types=1);

/**
 * Single shared PDO connection, lazily created. Every ajax/*.php and
 * admin/*.php script does `$pdo = db();` and uses prepared statements —
 * no ORM, no query builder, just PDO with exceptions on error so a bad
 * query surfaces immediately instead of silently returning false.
 *
 * Deliberately throws RuntimeException rather than calling die()/
 * json_response() itself: this file is required from both JSON AJAX
 * endpoints and plain HTML pages (index.php), and only the caller knows
 * which response format is correct for a failure.
 */

$configPath = __DIR__ . '/config.php';

if (! file_exists($configPath)) {
    if (getenv('DB_DATABASE')) {
        define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
        define('DB_NAME', getenv('DB_DATABASE') ?: 'vectis_trade_db');
        define('DB_USER', getenv('DB_USERNAME') ?: 'root');
        define('DB_PASS', getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '');
        define('DB_CHARSET', 'utf8mb4');
        define('APP_ENV', 'local');
        define('APP_URL', 'http://127.0.0.1:8000');
        define('APP_TIMEZONE', 'Asia/Kolkata');
        define('ADMIN_SESSION_KEY', 'geotrade_admin_id');
        define('ADMIN_SESSION_LIFETIME_SECONDS', 3600 * 8);
        define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
        define('GEMINI_MODEL', 'gemini-2.5-flash');
        date_default_timezone_set(APP_TIMEZONE);
    } else {
        throw new RuntimeException(
            'config/config.php is missing. Copy config/config.sample.php to config/config.php in Hostinger File Manager and fill in your MySQL database credentials.'
        );
    }
} else {
    require_once $configPath;
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // real prepared statements, not string-interpolated by the driver
            ]);
        } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), previous: $e);
        }

        require_once __DIR__ . '/../includes/migrate.php';
        try {
            run_pending_migrations($pdo);
        } catch (Throwable $e) {
            error_log('Migration failed: ' . $e->getMessage());
            throw new RuntimeException('Database migration failed: ' . $e->getMessage(), previous: $e);
        }
    }

    return $pdo;
}
