<?php

declare(strict_types=1);

/**
 * Temporary root landing page. There is no real dashboard yet (that's
 * Stage 6) — this exists only so the domain doesn't 403/404 and so you
 * can confirm the DB connection worked, right after a deploy. Delete or
 * replace once the real Bloomberg-style terminal UI lands.
 */
$dbStatus = 'unknown';
$dbDetail = '';

try {
    require_once __DIR__ . '/config/database.php';
    db();
    $dbStatus = 'connected';
} catch (Throwable $e) {
    $dbStatus = 'failed';
    $dbDetail = $e->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>GeoTrade Terminal — Setup Status</title>
<style>
  body { margin: 0; background: #080B10; color: #E7ECF3; font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
  .card { max-width: 520px; padding: 28px; border: 1px solid #1E2633; background: #0F141C; border-radius: 4px; }
  h1 { font-size: 18px; margin: 0 0 4px 0; }
  p.sub { color: #8B98AC; font-size: 13px; margin: 0 0 20px 0; }
  .row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #1E2633; font-size: 13px; }
  .ok { color: #22C55E; } .fail { color: #EF4444; }
  code { background: #080B10; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
  a { color: #00D2FF; }
</style>
</head>
<body>
  <div class="card">
    <h1>GeoTrade Terminal</h1>
    <p class="sub">Plain-PHP build — Stage 1 of 6. This page is a placeholder, not the product.</p>
    <div class="row">
      <span>Database connection</span>
      <span class="<?= $dbStatus === 'connected' ? 'ok' : 'fail' ?>"><?= htmlspecialchars($dbStatus) ?></span>
    </div>
    <?php if ($dbDetail !== ''): ?>
      <p class="sub" style="color:#EF4444;margin-top:12px;">Error: <?= htmlspecialchars($dbDetail) ?></p>
      <p class="sub">Check that <code>config/config.php</code> exists with real credentials — see <code>README.md</code>.</p>
    <?php endif; ?>
    <p class="sub" style="margin-top:20px;">Full status: <a href="/ajax/health.php">/ajax/health.php</a></p>
  </div>
</body>
</html>
