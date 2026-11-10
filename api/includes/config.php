<?php
declare(strict_types=1);

/**
 * OPAC — configuration & database bootstrap.
 *
 * Environment variables (or a local .env file):
 *   DATABASE_URL      mysql://user:pass@host:3306/dbname  (or a raw PDO DSN)
 *   MYSQL_PUBLIC_HOST / MYSQL_PUBLIC_PORT / MYSQLUSER / MYSQLPASSWORD /
 *   MYSQLDATABASE      Railway's public connection variables (see db_config)
 *   MYSQLHOST / MYSQLPORT  Railway's private variables (see db_config)
 *   SESSION_SECRET    secret key used to sign admin session cookies
 *   APP_BASE          optional base path override, e.g. /OPAC
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

/**
 * Resolve the database connection settings, in priority order:
 *
 *   1. DATABASE_URL         mysql://user:pass@host:port/db  (or a raw PDO DSN)
 *                            — also accepts Railway's MYSQL_PUBLIC_URL / MYSQL_URL
 *   2. Railway public vars  MYSQL_PUBLIC_HOST + MYSQL_PUBLIC_PORT
 *                            (+ MYSQLUSER / MYSQLPASSWORD / MYSQLDATABASE)
 *   3. Railway vars          MYSQLHOST + MYSQLPORT (+ the three above)
 *   4. XAMPP / MariaDB       127.0.0.1:3306, db "opac", user "root", no password
 *
 * Railway note: the private MYSQLHOST ("mysql.railway.internal") is reachable
 * only from inside Railway itself. When the app runs on Vercel you MUST use
 * the public connection data from the MySQL service's "Connect" tab — either
 * paste the public URL into DATABASE_URL, or set MYSQL_PUBLIC_HOST /
 * MYSQL_PUBLIC_PORT (or copy the public values into MYSQLHOST / MYSQLPORT).
 *
 * @return array{dsn: string, user: string, pass: string}
 */
function db_config(): array
{
    // 1) URL forms — DATABASE_URL, or Railway's own URL variables.
    foreach (['DATABASE_URL', 'MYSQL_PUBLIC_URL', 'MYSQL_URL'] as $var) {
        $url = (string) getenv($var);
        if ($url === '') {
            continue;
        }
        if (str_starts_with($url, 'mysql://')) {
            // URL form: mysql://user:password@host:3306/database
            $parts = parse_url($url) ?: [];
            return [
                'dsn'  => sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                    $parts['host'] ?? '127.0.0.1',
                    (int) ($parts['port'] ?? 3306),
                    ltrim($parts['path'] ?? '/opac', '/')
                ),
                'user' => urldecode($parts['user'] ?? 'root'),
                'pass' => urldecode($parts['pass'] ?? ''),
            ];
        }
        if (str_starts_with($url, 'mysql:')) {
            // Raw PDO DSN form: mysql:host=...;dbname=...;charset=utf8mb4
            return ['dsn' => $url, 'user' => getenv('DB_USER') ?: 'root', 'pass' => getenv('DB_PASS') ?: ''];
        }
    }

    // 2) & 3) Railway variables — public first (needed from outside Railway),
    //         falling back to the private set (same variable names either way).
    $host = (string) (getenv('MYSQL_PUBLIC_HOST') ?: getenv('MYSQLHOST') ?: '');
    if ($host !== '') {
        $port = (string) (getenv('MYSQL_PUBLIC_PORT') ?: getenv('MYSQLPORT') ?: '3306');
        return [
            'dsn'  => sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $host, (int) $port, (string) (getenv('MYSQLDATABASE') ?: 'railway')),
            'user' => (string) (getenv('MYSQLUSER') ?: 'root'),
            'pass' => (string) (getenv('MYSQLPASSWORD') ?: ''),
        ];
    }

    // 4) XAMPP / MariaDB defaults for local development.
    return [
        'dsn'  => 'mysql:host=127.0.0.1;port=3306;dbname=opac;charset=utf8mb4',
        'user' => 'root',
        'pass' => '',
    ];
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = db_config();

    try {
        $pdo = new PDO($cfg['dsn'], $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        $detail = getenv('APP_DEBUG')
            ? $e->getMessage()
            : 'Database connection failed. Check DATABASE_URL / MySQL service.';
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
