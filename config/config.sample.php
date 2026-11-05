<?php

declare(strict_types=1);

/**
 * Central config. On Hostinger shared hosting this file sits OUTSIDE
 * public_html (one level up) so it is never served directly, even by
 * misconfiguration — public_html only contains index.php, ajax/*.php,
 * admin/*.php and assets/, all of which `require` this via a relative
 * path like `require __DIR__ . '/../config/config.php';`.
 *
 * Copy config.sample.php to config.php and fill in real credentials;
 * config.php itself should be in .gitignore so secrets never get committed.
 */

// --- Database (cPanel MySQL) ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456789_geotrade');   // Hostinger prefixes DB names with your account id
define('DB_USER', 'u123456789_geotrade');
define('DB_PASS', 'CHANGE_ME');
define('DB_CHARSET', 'utf8mb4');

// --- App ---
define('APP_ENV', 'production'); // 'local' enables verbose error display
define('APP_URL', 'https://yourdomain.com');
define('APP_TIMEZONE', 'Asia/Kolkata');

// --- Admin session ---
define('ADMIN_SESSION_KEY', 'geotrade_admin_id');
define('ADMIN_SESSION_LIFETIME_SECONDS', 3600 * 8);

// --- Anthropic (for AI-drafted report generation, Stage 5) ---
define('ANTHROPIC_API_KEY', 'CHANGE_ME');
define('ANTHROPIC_MODEL', 'claude-sonnet-5');

date_default_timezone_set(APP_TIMEZONE);

if (APP_ENV === 'local') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    // Still log everything server-side even with display off.
    ini_set('log_errors', '1');
}
