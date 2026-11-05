<?php

declare(strict_types=1);

/**
 * Public report detail page — rendered client-side against /ajax/report.php?id=
 */
try {
    require_once __DIR__ . '/config/database.php';
    db();
} catch (Throwable $e) {
    // Handled client-side if database credentials not configured
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Vectis Intelligence Dossier — GeoTrade Terminal</title>
<link rel="stylesheet" href="assets/css/terminal.css">
</head>
<body>
  <div class="gt-topbar">
    <div class="gt-topbar__brand"><span class="dot"></span> VECTIS // GEO TRADE TERMINAL</div>
    <div class="gt-topbar__nav">
      <a href="/" class="active">&larr; Main Terminal</a>
      <a href="/admin/login.php">Admin</a>
    </div>
  </div>

  <div class="gt-page gt-page--wide">
    <div id="gt-report-detail" class="gt-loading">Loading intelligence dossier…</div>
  </div>

  <script src="assets/js/public.js"></script>
</body>
</html>
