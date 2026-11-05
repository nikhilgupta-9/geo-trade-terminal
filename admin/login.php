<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

start_secure_session();

if (current_admin_id() !== null) {
    header('Location: /admin/index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } elseif (attempt_admin_login($email, $password)) {
        header('Location: /admin/index.php');
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}

$csrfToken = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login — GeoTrade Terminal</title>
<link rel="stylesheet" href="/assets/css/terminal.css">
</head>
<body class="gt-centered">
  <div class="gt-card gt-card--narrow">
    <h1 class="gt-card__title">GeoTrade Terminal</h1>
    <p class="gt-card__subtitle">Admin sign in</p>

    <?php if ($error): ?>
      <div class="gt-alert gt-alert--error"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" class="gt-form">
      <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
      <label class="gt-label">Email
        <input class="gt-input" type="email" name="email" required autofocus>
      </label>
      <label class="gt-label">Password
        <input class="gt-input" type="password" name="password" required>
      </label>
      <button class="gt-btn gt-btn--primary" type="submit">Sign in</button>
    </form>
  </div>
</body>
</html>
