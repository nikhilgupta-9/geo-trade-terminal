<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin(false);
$csrfToken = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin — GeoTrade Terminal</title>
<link rel="stylesheet" href="/assets/css/terminal.css">
<meta name="csrf-token" content="<?= h($csrfToken) ?>">
</head>
<body>
  <div class="gt-topbar">
    <div class="gt-topbar__brand"><span class="dot"></span> GEOTRADE TERMINAL — ADMIN</div>
    <div class="gt-topbar__nav">
      <a href="/" target="_blank">View public site</a>
      <a href="/admin/logout.php">Sign out</a>
    </div>
  </div>

  <div class="gt-page gt-page--wide">
    <div class="gt-page__header">
      <h1 class="gt-page__title">Report Review</h1>
      <p class="gt-page__subtitle">AI-drafted reports never reach the public feed without an explicit approve here — same discipline as the HS-code quarantine.</p>
    </div>

    <div class="gt-tabs">
      <div class="gt-tab active" data-tab="queue">Review Queue</div>
      <div class="gt-tab" data-tab="all-reports">All Reports</div>
      <div class="gt-tab" data-tab="all-events">All Events</div>
    </div>

    <div id="gt-admin-content" class="gt-loading">Loading…</div>
  </div>

  <script src="/assets/js/admin.js"></script>
</body>
</html>
