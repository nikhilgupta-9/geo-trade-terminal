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
    throw new RuntimeException(
        'config/config.php is missing. Copy config/config.sample.php to config/config.php and fill in real database credentials.'
    );
}

require_once $configPath;

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
    }

    return $pdo;
}
