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
<title>Review Report — GeoTrade Terminal Admin</title>
<link rel="stylesheet" href="/assets/css/terminal.css">
<meta name="csrf-token" content="<?= h($csrfToken) ?>">
</head>
<body>
  <div class="gt-topbar">
    <div class="gt-topbar__brand"><span class="dot"></span> GEOTRADE TERMINAL — ADMIN</div>
    <div class="gt-topbar__nav">
      <a href="/admin/index.php">Review Queue</a>
      <a href="/admin/logout.php">Sign out</a>
    </div>
  </div>

  <div class="gt-page">
    <div id="gt-admin-report-detail" class="gt-loading">Loading…</div>
  </div>

  <script src="/assets/js/admin.js"></script>
</body>
</html>
