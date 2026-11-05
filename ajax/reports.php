<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

/**
 * Public report feed. GET only, published reports only — anything still
 * ai_drafted/in_review/changes_requested/rejected is admin-only (see
 * ajax/admin/reports.php). Optional ?category= and ?timing= filters.
 */
try {
    require_once __DIR__ . '/../config/database.php';
    $pdo = db();

    $where = ["r.status = 'published'"];
    $params = [];

    if (! empty($_GET['category'])) {
        $where[] = 'e.category = :category';
        $params['category'] = (string) $_GET['category'];
    }

    if (! empty($_GET['timing'])) {
        $where[] = 'e.timing = :timing';
        $params['timing'] = (string) $_GET['timing'];
    }

    $sql = "SELECT
                r.id, r.headline, r.market_stance, r.market_summary, r.published_at,
                e.id AS event_id, e.title AS event_title, e.category, e.timing, e.severity, e.event_date,
                e.countries_json, e.resources_affected_json, e.scenarios_json
            FROM macro_reports r
            JOIN macro_events e ON e.id = r.event_id
            WHERE " . implode(' AND ', $where) . '
            ORDER BY r.published_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $reports = array_map(static function (array $row): array {
        return [
            'id' => (int) $row['id'],
            'headline' => $row['headline'],
            'market_stance' => $row['market_stance'],
            'market_summary' => $row['market_summary'] ?? '',
            'published_at' => $row['published_at'],
            'event' => [
                'id' => (int) $row['event_id'],
                'title' => $row['event_title'],
                'category' => $row['category'],
                'timing' => $row['timing'],
                'severity' => $row['severity'] ?? 'high',
                'event_date' => $row['event_date'],
                'countries' => json_decode($row['countries_json'] ?? '[]', true) ?? [],
                'resources_affected' => json_decode($row['resources_affected_json'] ?? '[]', true) ?? [],
                'scenarios' => json_decode($row['scenarios_json'] ?? '{}', true) ?? null,
            ],
        ];
    }, $rows);

    json_response(['status' => 'ok', 'reports' => $reports]);
} catch (Throwable $e) {
    json_error('Could not load reports: ' . $e->getMessage(), 500, 'REPORTS_LOAD_FAILED');
}
