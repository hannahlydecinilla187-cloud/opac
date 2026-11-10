<?php
declare(strict_types=1);

/**
 * OPAC — shared HTML layout (Bootstrap 5 + Bootstrap Icons + SweetAlert2 via CDN).
 */

/** @param array{active?:string,scripts?:array<int,string>} $opts */
function layout_header(string $title = 'TAGOLOAN COMMUNITY COLLEGE Library OPAC', array $opts = []): void
{
    $active = (string) ($opts['active'] ?? '');
    $admin  = current_admin();

    $navItems = [
        ['key' => 'home',    'href' => url('/'),        'icon' => 'house-door',    'label' => 'Home'],
        ['key' => 'search',  'href' => url('/search'),  'icon' => 'search',        'label' => 'Search Catalog'],
        ['key' => 'browse',  'href' => url('/browse'),  'icon' => 'journals',      'label' => 'Browse Books'],
        ['key' => 'account', 'href' => url('/account'), 'icon' => 'person-circle', 'label' => 'My Account'],
    ];

    // "About" is only shown to visitors — admins get a leaner menu.
    if ($admin === null) {
        $navItems[] = ['key' => 'about', 'href' => url('/about'), 'icon' => 'info-circle', 'label' => 'About'];
    }
    ?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(url('/assets/img/favicon.svg')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(url('/assets/css/styles.css')) ?>" rel="stylesheet">
    <script>window.APP_BASE = <?= json_encode(base_url()) ?>;</script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-opac sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 py-1" href="<?= e(url('/')) ?>">
            <i class="bi bi-journals fs-3 lh-1"></i>
            <span class="brand-text">
                <span class="brand-line">TAGOLOAN COMMUNITY COLLEGE</span>
                <span class="brand-line brand-line-2">Library OPAC</span>
            </span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php foreach ($navItems as $item): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $active === $item['key'] ? 'active' : '' ?>"
                           href="<?= e($item['href']) ?>">
                            <i class="bi bi-<?= e($item['icon']) ?> me-1"></i><?= e($item['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <ul class="navbar-nav mb-2 mb-lg-0">
                <?php if ($admin): ?>
                    <li class="nav-item">
                        <a class="nav-link js-logout d-flex align-items-center gap-2 py-1" href="<?= e(url('/admin/logout')) ?>">
                            <i class="bi bi-box-arrow-right fs-5 lh-1"></i>
                            <span class="nav-text">
                                <span class="nav-line">Logout</span>
                                <span class="nav-line nav-line-2">(<?= e($admin['username']) ?>)</span>
                            </span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="flex-grow-1">
    <?php
}

/** @param array{scripts?:array<int,string>} $opts */
function layout_footer(array $opts = []): void
{
    ?>
</main>
<footer class="site-footer text-center text-muted small py-4 mt-auto">
    TAGOLOAN COMMUNITY COLLEGE &middot; Library OPAC &middot; built with PHP, MySQL, Bootstrap &amp; SweetAlert2
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
    // Shared SweetAlert helpers (available to all pages).
    window.toast = Swal.mixin({
        toast: true, position: 'top-end', showConfirmButton: false, timer: 2500,
        timerProgressBar: true, didOpen: (t) => {
            t.addEventListener('mouseenter', Swal.stopTimer);
            t.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    // Logout links: confirm with Yes / No before ending the session.
    document.addEventListener('click', function (e) {
        const link = e.target.closest('a.js-logout');
        if (!link) return;
        e.preventDefault();
        Swal.fire({
            icon: 'question',
            title: 'Log out?',
            text: 'You will be signed out of your account.',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, log out',
            cancelButtonText: 'No',
            focusCancel: true,
        }).then(function (result) {
            if (result.isConfirmed) {
                window.location.assign(link.getAttribute('href'));
            }
        });
    });
</script>
<?php foreach (($opts['scripts'] ?? []) as $script): ?>
    <script src="<?= e(url((string) $script)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
    <?php
}
