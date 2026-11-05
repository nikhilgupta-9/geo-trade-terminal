<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';

/** Admin "All Events" tab — every tracked event with its report count. */
require_admin(true);

try {
    $pdo = db();

    $sql = 'SELECT e.id, e.title, e.category, e.timing, e.event_date, e.countries_json, e.created_at,
                   COUNT(r.id) AS report_count
            FROM macro_events e
            LEFT JOIN macro_reports r ON r.event_id = e.id
            GROUP BY e.id
            ORDER BY e.created_at DESC';

    $stmt = $pdo->query($sql);
    $events = array_map(static function (array $row): array {
        $row['countries'] = json_decode($row['countries_json'], true) ?? [];
        $row['report_count'] = (int) $row['report_count'];
        unset($row['countries_json']);

        return $row;
    }, $stmt->fetchAll());

    json_response(['status' => 'ok', 'events' => $events]);
} catch (Throwable $e) {
    json_error('Could not load events: ' . $e->getMessage(), 500, 'EVENTS_LOAD_FAILED');
}
