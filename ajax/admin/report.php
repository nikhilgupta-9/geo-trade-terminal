<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

/** Admin single-report detail — any status, includes review fields the public endpoint hides. */
require_admin(true);

try {
    $pdo = db();

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (! $id) {
        json_error('Missing or invalid ?id=.', 422, 'VALIDATION_ERROR');
    }

    $stmt = $pdo->prepare(
        'SELECT r.*, e.title AS event_title, e.category, e.timing, e.event_date,
                e.countries_json, e.summary AS event_summary, e.sources_json AS event_sources_json,
                a.name AS reviewed_by_name
         FROM macro_reports r
         JOIN macro_events e ON e.id = r.event_id
         LEFT JOIN admin_users a ON a.id = r.reviewed_by
         WHERE r.id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $report = $stmt->fetch();

    if (! $report) {
        json_error('Report not found.', 404, 'NOT_FOUND');
    }

    $industryStmt = $pdo->prepare(
        'SELECT id, industry, direction, severity, rationale
         FROM report_industry_impacts WHERE report_id = :id ORDER BY sort_order ASC, id ASC'
    );
    $industryStmt->execute(['id' => $id]);

    $companyStmt = $pdo->prepare(
        'SELECT id, company_name, ticker, country, direction, rationale, recent_activity_json, sources_json
         FROM report_company_impacts WHERE report_id = :id ORDER BY sort_order ASC, id ASC'
    );
    $companyStmt->execute(['id' => $id]);
    $companies = array_map(static function (array $c): array {
        $c['recent_activity'] = json_decode($c['recent_activity_json'] ?? '[]', true) ?? [];
        $c['sources'] = json_decode($c['sources_json'] ?? '[]', true) ?? [];
        unset($c['recent_activity_json'], $c['sources_json']);

        return $c;
    }, $companyStmt->fetchAll());

    json_response([
        'status' => 'ok',
        'report' => [
            'id' => (int) $report['id'],
            'headline' => $report['headline'],
            'status' => $report['status'],
            'market_stance' => $report['market_stance'],
            'market_summary' => $report['market_summary'],
            'market_flow_note' => $report['market_flow_note'],
            'ai_drafted_at' => $report['ai_drafted_at'],
            'reviewed_by_name' => $report['reviewed_by_name'],
            'reviewed_at' => $report['reviewed_at'],
            'review_notes' => $report['review_notes'],
            'published_at' => $report['published_at'],
            'event' => [
                'id' => (int) $report['event_id'],
                'title' => $report['event_title'],
                'category' => $report['category'],
                'timing' => $report['timing'],
                'event_date' => $report['event_date'],
                'countries' => json_decode($report['countries_json'], true) ?? [],
                'summary' => $report['event_summary'],
                'sources' => json_decode($report['event_sources_json'] ?? '[]', true) ?? [],
            ],
            'industry_impacts' => $industryStmt->fetchAll(),
            'company_impacts' => $companies,
        ],
    ]);
} catch (Throwable $e) {
    json_error('Could not load report: ' . $e->getMessage(), 500, 'REPORT_LOAD_FAILED');
}
