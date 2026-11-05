<?php

declare(strict_types=1);

/**
 * Public homepage — the report feed. This is the actual product: global/
 * national events and who they help or hurt, by industry and by named
 * company. Rendering is client-side (assets/js/public.js) against
 * /ajax/reports.php; this file is just the shell + nav.
 */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>GeoTrade Terminal — Event Impact Reports</title>
<link rel="stylesheet" href="/assets/css/terminal.css">
</head>
<body>
  <div class="gt-topbar">
    <div class="gt-topbar__brand"><span class="dot"></span> GEOTRADE TERMINAL</div>
    <div class="gt-topbar__nav">
      <a href="/" class="active">Reports</a>
      <a href="/admin/login.php">Admin</a>
    </div>
  </div>

  <div class="gt-page">
    <div class="gt-page__header">
      <h1 class="gt-page__title">Event Impact Reports</h1>
      <p class="gt-page__subtitle">Who gains, who loses, and where money is moving — by industry and by company, for every event that moves global trade.</p>
    </div>

    <div id="gt-feed" class="gt-loading">Loading reports…</div>
  </div>

  <script src="/assets/js/public.js"></script>
</body>
</html>
