<?php
declare(strict_types=1);

/**
 * OPAC — router for PHP's built-in web server (Railway deployment).
 *
 * Started by railway.json as:  php -S 0.0.0.0:$PORT router.php
 *
 * Static assets under /assets/ (and the offline edition) pass through to the
 * built-in server; every other URL — including direct hits into /api/ —
 * goes to the single front controller (api/index.php), mirroring the routing
 * of vercel.json (.htaccess does the same on XAMPP Apache).
 */

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');

// Shield: raw PHP sources under /api/ are never served or executed by URL.
// (Apache answers 403, Vercel redirects — here we 404.)
if ($path === '/api' || str_starts_with($path, '/api/')) {
    http_response_code(404);
    echo 'Not found';
    return true;
}

// Only public static files are served directly; everything that exists in
// the repo but is not public (database.sql, README, composer.json, .env …)
// falls through to the controller and 404s.
$public = str_starts_with($path, '/assets/') || $path === '/tcc-opac.html';
$file   = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $path);

if ($public && !str_contains($path, '..') && is_file($file)) {
    return false; // let the built-in server serve the file
}

// Everything else -> single front controller (REQUEST_URI stays original,
// exactly like the Vercel catch-all rewrite).
chdir(__DIR__ . DIRECTORY_SEPARATOR . 'api');
require __DIR__ . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'index.php';
