<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

/**
 * Minimal session-based admin auth — no Laravel Sanctum/Breeze, just
 * PHP's native session plus a password_hash()'d row in `admin_users`.
 * Every admin/*.php page and admin-only ajax/*.php endpoint starts with
 * require_admin().
 */
function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => ADMIN_SESSION_LIFETIME_SECONDS,
            'path' => '/',
            'secure' => APP_ENV !== 'local', // HTTPS-only cookie in production
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function current_admin_id(): ?int
{
    start_secure_session();

    return $_SESSION[ADMIN_SESSION_KEY] ?? null;
}

/** Call at the top of any admin-only script. Redirects (HTML pages) or 401s (AJAX JSON endpoints). */
function require_admin(bool $isJsonEndpoint = false): int
{
    $adminId = current_admin_id();

    if ($adminId === null) {
        if ($isJsonEndpoint) {
            json_error('Not authenticated.', 401, 'UNAUTHENTICATED');
        }

        header('Location: /admin/login.php');
        exit;
    }

    return $adminId;
}

function attempt_admin_login(string $email, string $password): bool
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, password_hash FROM admin_users WHERE email = :email AND is_active = 1 LIMIT 1');
    $stmt->execute(['email' => $email]);
    $row = $stmt->fetch();

    if (! $row || ! password_verify($password, $row['password_hash'])) {
        return false;
    }

    start_secure_session();
    session_regenerate_id(true); // prevent session fixation on privilege change
    $_SESSION[ADMIN_SESSION_KEY] = (int) $row['id'];

    $pdo->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = :id')->execute(['id' => $row['id']]);

    return true;
}

function admin_logout(): void
{
    start_secure_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
