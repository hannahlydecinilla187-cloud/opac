<?php
declare(strict_types=1);

/**
 * OPAC — front controller (the app's ONLY serverless function).
 *
 * On Vercel every request is rewritten here by the catch-all rule in
 * vercel.json (REQUEST_URI keeps the original path); on XAMPP .htaccess
 * does the same via mod_rewrite; the dev server mirrors it in
 * router-dev.php. All other PHP files under api/ are plain sources
 * required from here — they never become their own functions.
 *
 * Routes:
 *   /                    -> api/home.php
 *   /search              -> api/catalog.php
 *   /browse              -> api/browse.php
 *   /account             -> api/account.php
 *   /about               -> api/about.php
 *   /book/{id}           -> api/book.php        ($_GET['id'] set here)
 *   /admin               -> api/admin/dashboard.php
 *   /admin/login         -> api/admin/login.php
 *   /admin/logout        -> api/admin/logout.php
 *   /search.json         -> api/search.php      (public JSON)
 *   /admin/books.json    -> api/admin/books.php (JSON CRUD, admin only)
 *   anything else        -> 404 page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

/** Original request path with the base path stripped, e.g. "/OPAC/book/3" -> "/book/3". */
function request_path(): string
{
    // Vercel passes the pre-rewrite path in this header when it differs from
    // REQUEST_URI; locally it is absent and REQUEST_URI is already correct.
    $path = (string) ($_SERVER['HTTP_X_VERCEL_ORIGINAL_PATH'] ?? '');
    if ($path === '') {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
    }
    $base = base_url();
    if ($base !== '' && str_starts_with($path, $base . '/')) {
        $path = substr($path, strlen($base));
    }
    return '/' . trim(rawurldecode($path), '/');
}

$path = request_path();

// ------------------------------------------------------------------- routes
$routes = [
    '/'                 => '/home.php',
    '/index.php'        => '/home.php',   // direct entry hits, e.g. /api/index.php
    '/api/index.php'    => '/home.php',
    '/search'           => '/catalog.php',
    '/browse'           => '/browse.php',
    '/account'          => '/account.php',
    '/about'            => '/about.php',
    '/search.json'      => '/search.php',
    '/admin'            => '/admin/dashboard.php',
    '/admin/login'      => '/admin/login.php',
    '/admin/logout'     => '/admin/logout.php',
    '/admin/books.json' => '/admin/books.php',
];

// /book/{id} — book.php reads $_GET['id'].
if (preg_match('~^/book/([1-9][0-9]*)$~', $path, $m)) {
    $_GET['id'] = $m[1];
    require __DIR__ . '/book.php';
    return;
}

if (isset($routes[$path])) {
    require __DIR__ . $routes[$path];
    return;
}

// --------------------------------------------------------------------- 404
http_response_code(404);
require_once __DIR__ . '/includes/layout.php';
layout_header('Page not found — TAGOLOAN COMMUNITY COLLEGE Library OPAC', []);
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">
            <i class="bi bi-journal-x display-1 text-secondary"></i>
            <h1 class="h3 mt-3">Page not found</h1>
            <p class="text-muted">The page you are looking for does not exist.</p>
            <a href="<?= e(url('/')) ?>" class="btn btn-primary">
                <i class="bi bi-arrow-left me-1"></i>Back to home
            </a>
        </div>
    </div>
</div>
<?php
layout_footer();
