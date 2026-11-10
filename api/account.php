<?php
declare(strict_types=1);

/**
 * OPAC — My Account.
 *
 * GET  /account (guest)  -> sign-in form
 * GET  /account (admin)  -> account overview (dashboard link, logout)
 * POST /account          -> authenticate (JSON body {username, password}) -> JSON
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verify_same_origin();
    $data     = json_input();
    $username = trim((string) ($data['username'] ?? ''));
    $password = (string) ($data['password'] ?? '');

    if ($username === '' || $password === '') {
        json_error('Please enter both username and password.');
    }

    $admin = attempt_admin_login($username, $password);
    if ($admin === null) {
        json_error('Invalid username or password.', 401);
    }

    admin_login_session((int) $admin['id']);
    json_out(['ok' => true, 'redirect' => url('/account')]);
}

$admin = current_admin();

layout_header('My Account — Online Public Access Catalog', ['active' => 'account']);

if ($admin === null) {
    // Guest — show the sign-in form.
    $loginOpts = ['title' => 'My Account', 'subtitle' => 'Sign in to manage the catalog'];
    include __DIR__ . '/includes/login-form.php';
    layout_footer();
    exit;
}

// Signed in — account overview.
$stmt = db()->prepare('SELECT created_at FROM admins WHERE id = ? LIMIT 1');
$stmt->execute([(int) $admin['id']]);
$memberSince = $stmt->fetchColumn();
$memberSince = $memberSince
    ? (new DateTimeImmutable($memberSince))->format('F j, Y')
    : '';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">

            <div class="card border-0 shadow-sm mb-4" style="border-radius: 1rem;">
                <div class="card-body p-4 p-md-5 text-center">
                    <i class="bi bi-person-circle display-3 text-primary"></i>
                    <h1 class="h4 mt-3 mb-1"><?= e($admin['username']) ?></h1>
                    <p class="text-muted mb-1">
                        <i class="bi bi-shield-check me-1"></i>Administrator
                    </p>
                    <?php if ($memberSince !== ''): ?>
                        <p class="text-muted small mb-0">
                            <i class="bi bi-calendar3 me-1"></i>Member since <?= e($memberSince) ?>
                        </p>
                    <?php endif; ?>
                    <hr class="my-4">
                    <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                        <a href="<?= e(url('/admin')) ?>" class="btn btn-primary">
                            <i class="bi bi-speedometer2 me-1"></i>Go to dashboard
                        </a>
                        <a href="<?= e(url('/admin/logout')) ?>" class="btn btn-outline-danger js-logout">
                            <i class="bi bi-box-arrow-right me-1"></i>Log out
                        </a>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 1rem;">
                <div class="card-body p-4">
                    <h2 class="h6 text-uppercase text-muted mb-3">Quick links</h2>
                    <div class="list-group list-group-flush">
                        <a href="<?= e(url('/admin')) ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                            <i class="bi bi-journal-plus text-primary"></i>
                            Add, edit or remove books
                        </a>
                        <a href="<?= e(url('/browse')) ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                            <i class="bi bi-journals text-primary"></i>
                            Browse the public catalog
                        </a>
                        <a href="<?= e(url('/search')) ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                            <i class="bi bi-search text-primary"></i>
                            Search the catalog
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<?php
layout_footer();
