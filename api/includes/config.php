<?php
declare(strict_types=1);

/**
 * OPAC — configuration & database bootstrap.
 *
 * Environment variables (or a local .env file):
 *   DATABASE_URL   mysql://user:pass@host:3306/dbname  (or a raw PDO DSN)
 *   SESSION_SECRET secret key used to sign admin session cookies
 *   APP_BASE       optional base path override, e.g. /OPAC
 */

// ---------------------------------------------------------------------------
// Tiny .env loader (local development only — never overrides real env vars).
// ---------------------------------------------------------------------------
$envFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $len = strlen($value);
        if ($len >= 2 && (($value[0] === '"' && $value[$len - 1] === '"') || ($value[0] === "'" && $value[$len - 1] === "'"))) {
            $value = substr($value, 1, -1);
        }
        if ($value !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
        }
    }
}

// ---------------------------------------------------------------------------
// Database (PDO / MySQL)
// ---------------------------------------------------------------------------
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $url = (string) getenv('DATABASE_URL');

    if (str_starts_with($url, 'mysql://')) {
        // URL form: mysql://user:password@host:3306/database
        $parts = parse_url($url) ?: [];
        $host = $parts['host'] ?? '127.0.0.1';
        $port = $parts['port'] ?? 3306;
        $name = ltrim($parts['path'] ?? '/opac', '/');
        $dsn  = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name);
        $user = urldecode($parts['user'] ?? 'root');
        $pass = urldecode($parts['pass'] ?? '');
    } elseif ($url !== '') {
        // Raw PDO DSN form: mysql:host=...;dbname=...;charset=utf8mb4
        $dsn  = $url;
        $user = getenv('DB_USER') ?: null;
        $pass = getenv('DB_PASS') ?: null;
    } else {
        // XAMPP / MariaDB defaults for local development.
        $dsn  = 'mysql:host=127.0.0.1;port=3306;dbname=opac;charset=utf8mb4';
        $user = 'root';
        $pass = '';
    }

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        $detail = getenv('APP_DEBUG') ? $e->getMessage() : 'Database connection failed. Check DATABASE_URL / MySQL service.';
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => $detail]);
        } else {
            echo '<!doctype html><html lang="en"><body style="font-family:system-ui;padding:3rem">'
               . '<h1>Database error</h1><p>' . htmlspecialchars($detail) . '</p></body></html>';
        }
        exit;
    }

    return $pdo;
}

// ---------------------------------------------------------------------------
// URLs
// ---------------------------------------------------------------------------
function is_https(): bool
{
    $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ($_SERVER['HTTPS'] ?? '');
    return strcasecmp((string) $proto, 'https') === 0 || strcasecmp((string) $proto, 'on') === 0;
}

/**
 * Base path of the app, e.g. "" (Vercel / domain root) or "/OPAC" (XAMPP).
 * Derived from DOCUMENT_ROOT vs. project location, or override with APP_BASE.
 */
function base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    if (($env = getenv('APP_BASE')) !== false && $env !== '') {
        return $base = rtrim($env, '/');
    }

    $docRoot = str_replace('\\', '/', rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));
    $appRoot = str_replace('\\', '/', dirname(__DIR__, 2));

    if ($docRoot !== '' && $appRoot !== '' && str_starts_with($appRoot . '/', $docRoot . '/')) {
        $base = substr($appRoot, strlen($docRoot));
    }
    return $base = ($base === '/' || $base === false) ? '' : (string) $base;
}

/** Absolute URL for an app path, e.g. url('/book/3') => "/OPAC/book/3". */
function url(string $path = '/'): string
{
    return base_url() . '/' . ltrim($path, '/');
}
