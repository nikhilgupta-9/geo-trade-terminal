<?php

declare(strict_types=1);

/**
 * Sends a JSON response and stops execution. Every ajax/*.php endpoint
 * ends with one of these — never a bare `echo`/`print_r`, so the
 * response shape (and status code) is consistent across endpoints.
 */
function json_response(array $data, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $statusCode = 400, string $code = 'ERROR'): never
{
    json_response(['error' => ['code' => $code, 'message' => $message]], $statusCode);
}

/** Reads and JSON-decodes the raw POST body; AJAX endpoints use this instead of $_POST for JSON requests. */
function json_body(): array
{
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw ?: '{}', true);

    return is_array($decoded) ? $decoded : [];
}

/** Require the given keys to be present (not necessarily non-empty) in an input array, or 422. */
function require_fields(array $input, array $fields): void
{
    $missing = array_filter($fields, fn (string $f) => ! array_key_exists($f, $input));

    if ($missing !== []) {
        json_error('Missing required field(s): ' . implode(', ', $missing), 422, 'VALIDATION_ERROR');
    }
}

/**
 * Casts a request value through a decimal string, never a float, before
 * it touches bcmath — same discipline as the Laravel version: trade
 * finance figures must never pick up binary-float rounding error.
 */
function to_decimal_string(mixed $value, int $decimals = 4): string
{
    if (! is_numeric($value)) {
        json_error("Expected a numeric value, got: " . var_export($value, true), 422, 'VALIDATION_ERROR');
    }

    return number_format((float) $value, $decimals, '.', '');
}

/** Basic HTML-escaping helper for anything echoed into a non-JSON page. */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** CSRF token issue/check — used by admin POST forms (not needed for read-only public AJAX GETs). */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_verify(): void
{
    $submitted = $_POST['csrf_token'] ?? (json_body()['csrf_token'] ?? null);

    if (! $submitted || ! hash_equals($_SESSION['csrf_token'] ?? '', $submitted)) {
        json_error('Invalid or expired session token. Refresh and try again.', 419, 'CSRF_MISMATCH');
    }
}
