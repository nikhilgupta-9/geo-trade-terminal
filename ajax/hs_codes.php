<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

try {
    require_once __DIR__ . '/../config/database.php';
    $pdo = db();

    $q = trim((string) ($_GET['q'] ?? ''));

    $sql = "SELECT h.id, h.code, h.chapter, h.description,
                   COALESCE(d.bcd_rate, 10.00) as bcd_rate,
                   COALESCE(d.sws_rate, 10.00) as sws_rate,
                   COALESCE(d.igst_rate, 18.00) as igst_rate,
                   COALESCE(d.aidc_rate, 0.00) as aidc_rate
            FROM hs_codes h
            LEFT JOIN duty_rates d ON d.hs_code_id = h.id AND d.status = 'active'";

    if ($q !== '') {
        $sql .= " WHERE h.code LIKE :q OR h.description LIKE :q OR h.chapter = :chapter";
        $stmt = $pdo->prepare($sql . " ORDER BY h.code ASC LIMIT 20");
        $stmt->execute([
            'q' => "%{$q}%",
            'chapter' => is_numeric($q) ? (int) $q : 0,
        ]);
    } else {
        $stmt = $pdo->query($sql . " ORDER BY h.code ASC LIMIT 20");
    }

    $results = $stmt->fetchAll();

    json_response([
        'status' => 'ok',
        'count' => count($results),
        'data' => $results,
    ]);
} catch (Throwable $e) {
    json_error('HS Code search failed: ' . $e->getMessage(), 500, 'SEARCH_FAILED');
}
