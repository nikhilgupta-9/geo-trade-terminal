<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Single shared PDO connection, lazily created. Every ajax/*.php and
 * admin/*.php script does `$pdo = db();` and uses prepared statements —
 * no ORM, no query builder, just PDO with exceptions on error so a bad
 * query surfaces immediately instead of silently returning false.
 */
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
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed.']));
        }
    }

    return $pdo;
}
