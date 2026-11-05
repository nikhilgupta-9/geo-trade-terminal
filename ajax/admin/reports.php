<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

/**
 * Admin report list — any status. ?status=ai_drafted|in_review|changes_requested|approved|published|rejected
 * Omit ?status= for everything (the "All Reports" admin tab); the review
 * queue tab passes status explicitly per-column.
 */
require_admin(true);

try {
    $pdo = db();

    $where = [];
    $params = [];

    if (! empty($_GET['status'])) {
        $where[] = 'r.status = :status';
        $params['status'] = (string) $_GET['status'];
    }

    $sql = 'SELECT r.id, r.headline, r.status, r.market_stance, r.ai_drafted_at, r.reviewed_at, r.published_at,
                   e.title AS event_title, e.category, e.timing
            FROM macro_reports r
            JOIN macro_events e ON e.id = r.event_id'
        . ($where !== [] ? ' WHERE ' . implode(' AND ', $where) : '')
        . ' ORDER BY r.ai_drafted_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    json_response(['status' => 'ok', 'reports' => $stmt->fetchAll()]);
} catch (Throwable $e) {
    json_error('Could not load reports: ' . $e->getMessage(), 500, 'REPORTS_LOAD_FAILED');
}
