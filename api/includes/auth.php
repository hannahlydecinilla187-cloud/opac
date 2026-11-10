<?php
declare(strict_types=1);

/**
 * OPAC — admin authentication.
 *
 * Stateless, HMAC-signed cookie sessions (no PHP filesystem sessions),
 * so it works the same on XAMPP and on Vercel serverless functions.
 */

const OPAC_COOKIE   = 'opac_session';
const OPAC_TTL      = 8 * 3600; // 8 hours

function session_secret(): string
{
    $secret = (string) getenv('SESSION_SECRET');
    return $secret !== '' ? $secret : 'opac-dev-secret-change-me';
}

function sign_payload(string $payload): string
{
    return hash_hmac('sha256', $payload, session_secret());
}

function cookie_path(): string
{
    $base = base_url();
    return ($base === '' ? '' : $base) . '/';
}

function cookie_flags(): array
{
    return [
        'path'     => cookie_path(),
        'httponly' => true,
        'samesite' => 'Strict',
        'secure'   => is_https(),
    ];
}

/** Log an admin in: set the signed session cookie. */
function admin_login_session(int $adminId): void
{
    $expires = time() + OPAC_TTL;
    $payload = $adminId . '.' . $expires;
    setcookie(OPAC_COOKIE, $payload . '.' . sign_payload($payload), array_merge(cookie_flags(), ['expires' => $expires]));
}

/**
 * Verify credentials against the admins table; returns the admin row or null.
 * Transparently rehashes the stored password when PHP's default cost changes.
 */
function attempt_admin_login(string $username, string $password): ?array
{
    if ($username === '' || $password === '') {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, (string) $admin['password_hash'])) {
        if (password_needs_rehash((string) $admin['password_hash'], PASSWORD_BCRYPT)) {
            $newHash = password_hash($password, PASSWORD_BCRYPT);
            db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([$newHash, $admin['id']]);
        }
        return $admin;
    }
    return null;
}

/** Log the admin out: clear the session cookie. */
function admin_logout_session(): void
{
    setcookie(OPAC_COOKIE, '', array_merge(cookie_flags(), ['expires' => 1]));
}

/** @return array{id:int,username:string}|null */
function current_admin(): ?array
{
    static $admin = null;
    static $resolved = false;
    if ($resolved) {
        return $admin;
    }
    $resolved = true;

    $cookie = $_COOKIE[OPAC_COOKIE] ?? '';
    if (!is_string($cookie) || $cookie === '') {
        return null;
    }

    $parts = explode('.', $cookie);
    if (count($parts) !== 3) {
        return null;
    }
    [$id, $expires, $sig] = $parts;

    if (!ctype_digit($id) || !ctype_digit($expires) || time() > (int) $expires) {
        return null;
    }
    if (!hash_equals(sign_payload($id . '.' . $expires), $sig)) {
        return null;
    }

    $stmt = db()->prepare('SELECT id, username FROM admins WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $id]);

    return $admin = ($stmt->fetch() ?: null);
}

function is_admin(): bool
{
    return current_admin() !== null;
}

/** Guard for HTML admin pages: redirect to login. */
function require_admin_page(): void
{
    if (!is_admin()) {
        redirect(url('/admin/login'));
    }
}

/** Guard for JSON admin endpoints: 401 response. */
function require_admin_json(): void
{
    if (!is_admin()) {
        json_error('Unauthorized — please log in again.', 401);
    }
}

/** Basic CSRF protection: reject cross-origin POSTs when Origin is present. */
function verify_same_origin(): void
{
    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    if ($origin === '') {
        return; // older browsers / non-browser clients
    }
    $host   = (string) ($_SERVER['HTTP_HOST'] ?? '');
    $parsed = parse_url($origin);
    $originHost = (string) ($parsed['host'] ?? '');
    if (($parsed['port'] ?? null) !== null) {
        $originHost .= ':' . $parsed['port'];
    }
    if ($host === '' || strcasecmp($originHost, $host) !== 0) {
        json_error('Cross-origin request blocked.', 403);
    }
}
