<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

/**
 * Public single-report detail. GET only, ?id= required, published only.
 * Returns the full shape the detail page needs in one call: event,
 * market-direction read, industry impacts, company impacts with multi-year activity log.
 */
try {
    require_once __DIR__ . '/../config/database.php';
    $pdo = db();

    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    if (! $id) {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    }

    if (! $id) {
        json_error('Missing or invalid ?id=.', 422, 'VALIDATION_ERROR');
    }

    $stmt = $pdo->prepare(
        "SELECT r.*, e.title AS event_title, e.category, e.timing, e.severity, e.event_date,
                e.countries_json, e.summary AS event_summary, e.resources_affected_json,
                e.scenarios_json, e.sources_json AS event_sources_json
         FROM macro_reports r
         JOIN macro_events e ON e.id = r.event_id
         WHERE r.id = :id AND r.status = 'published'
         LIMIT 1"
    );
    $stmt->execute(['id' => $id]);
    $report = $stmt->fetch();

    if (! $report) {
        json_error('Report not found.', 404, 'NOT_FOUND');
    }

    $industryStmt = $pdo->prepare(
        'SELECT industry, direction, severity, rationale
         FROM report_industry_impacts WHERE report_id = :id ORDER BY sort_order ASC, id ASC'
    );
    $industryStmt->execute(['id' => $id]);
    $industries = $industryStmt->fetchAll();

    $companyStmt = $pdo->prepare(
        'SELECT company_name, ticker, country, direction, sector, exchange, market_cap, overview,
                rationale, recent_activity_json, financial_metrics_json, revenue_exposure_geo, sources_json
         FROM report_company_impacts WHERE report_id = :id ORDER BY sort_order ASC, id ASC'
    );
    $companyStmt->execute(['id' => $id]);
    $companies = array_map(static function (array $c): array {
        $c['recent_activity'] = json_decode($c['recent_activity_json'] ?? '[]', true) ?? [];
        $c['financial_metrics'] = json_decode($c['financial_metrics_json'] ?? '{}', true) ?? [];
        $c['sources'] = json_decode($c['sources_json'] ?? '[]', true) ?? [];
        unset($c['recent_activity_json'], $c['financial_metrics_json'], $c['sources_json']);

        return $c;
    }, $companyStmt->fetchAll());

    json_response([
        'status' => 'ok',
        'report' => [
            'id' => (int) $report['id'],
            'headline' => $report['headline'],
            'market_stance' => $report['market_stance'],
            'market_summary' => $report['market_summary'],
            'market_flow_note' => $report['market_flow_note'],
            'published_at' => $report['published_at'],
            'event' => [
                'id' => (int) $report['event_id'],
                'title' => $report['event_title'],
                'category' => $report['category'],
                'timing' => $report['timing'],
                'severity' => $report['severity'] ?? 'high',
                'event_date' => $report['event_date'],
                'countries' => json_decode($report['countries_json'] ?? '[]', true) ?? [],
                'summary' => $report['event_summary'],
                'resources_affected' => json_decode($report['resources_affected_json'] ?? '[]', true) ?? [],
                'scenarios' => json_decode($report['scenarios_json'] ?? '{}', true) ?? null,
                'sources' => json_decode($report['event_sources_json'] ?? '[]', true) ?? [],
            ],
            'industry_impacts' => $industries,
            'company_impacts' => $companies,
        ],
    ]);
} catch (Throwable $e) {
    json_error('Could not load report: ' . $e->getMessage(), 500, 'REPORT_LOAD_FAILED');
}
