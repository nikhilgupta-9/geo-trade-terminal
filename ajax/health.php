<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

/**
 * Sanity-check endpoint for Stage 1 — confirms config.php is filled in
 * correctly and the DB connection + schema import actually worked, before
 * any real feature is built on top. Hit /ajax/health.php directly in a
 * browser after setup.
 */
try {
    $pdo = db();
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    json_response([
        'status' => 'ok',
        'db_connected' => true,
        'tables_found' => count($tables),
        'tables' => $tables,
    ]);
} catch (Throwable $e) {
    json_error('Health check failed: ' . $e->getMessage(), 500, 'HEALTH_CHECK_FAILED');
}
