<?php

declare(strict_types=1);

/** Public report detail page — rendered client-side against /ajax/report.php?id= */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Report — GeoTrade Terminal</title>
<link rel="stylesheet" href="/assets/css/terminal.css">
</head>
<body>
  <div class="gt-topbar">
    <div class="gt-topbar__brand"><span class="dot"></span> GEOTRADE TERMINAL</div>
    <div class="gt-topbar__nav">
      <a href="/">Reports</a>
      <a href="/admin/login.php">Admin</a>
    </div>
  </div>

  <div class="gt-page">
    <div id="gt-report-detail" class="gt-loading">Loading report…</div>
  </div>

  <script src="/assets/js/public.js"></script>
</body>
</html>
