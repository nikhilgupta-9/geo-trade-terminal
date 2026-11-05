<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

/**
 * POST { report_id, decision: approve|request_changes|reject, notes, csrf_token }
 * Mirrors the HS-code quarantine discipline: nothing an AI drafted reaches
 * the public feed without a logged-in reviewer explicitly approving it.
 * Reviewer identity is the authenticated admin session, not a free-typed
 * name field — notes are mandatory on every decision, not just rejections,
 * since "approved, here's what I checked" is itself worth keeping.
 */
$adminId = require_admin(true);
start_secure_session();
csrf_verify();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('POST required.', 405, 'METHOD_NOT_ALLOWED');
}

$input = json_body();
require_fields($input, ['report_id', 'decision', 'notes']);

$reportId = filter_var($input['report_id'], FILTER_VALIDATE_INT);
$decision = (string) $input['decision'];
$notes = trim((string) $input['notes']);

if (! $reportId) {
    json_error('Invalid report_id.', 422, 'VALIDATION_ERROR');
}

if ($notes === '') {
    json_error('Review notes are required for every decision.', 422, 'VALIDATION_ERROR');
}

$statusByDecision = [
    'approve' => 'published',
    'request_changes' => 'changes_requested',
    'reject' => 'rejected',
];

if (! array_key_exists($decision, $statusByDecision)) {
    json_error('decision must be one of: ' . implode(', ', array_keys($statusByDecision)), 422, 'VALIDATION_ERROR');
}

try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT id, status FROM macro_reports WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $reportId]);
    $report = $stmt->fetch();

    if (! $report) {
        json_error('Report not found.', 404, 'NOT_FOUND');
    }

    $newStatus = $statusByDecision[$decision];
    $publishedAtSql = $newStatus === 'published' ? 'NOW()' : 'NULL';

    $update = $pdo->prepare(
        "UPDATE macro_reports
         SET status = :status, reviewed_by = :reviewed_by, reviewed_at = NOW(), review_notes = :notes,
             published_at = {$publishedAtSql}
         WHERE id = :id"
    );
    $update->execute([
        'status' => $newStatus,
        'reviewed_by' => $adminId,
        'notes' => $notes,
        'id' => $reportId,
    ]);

    json_response(['status' => 'ok', 'report_id' => $reportId, 'new_status' => $newStatus]);
} catch (Throwable $e) {
    json_error('Could not save review decision: ' . $e->getMessage(), 500, 'REVIEW_SAVE_FAILED');
}
