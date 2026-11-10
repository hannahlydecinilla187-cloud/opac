<?php
declare(strict_types=1);

/**
 * OPAC — admin login.
 *
 * GET  /admin/login   -> render login form (HTML)
 * POST /admin/login   -> authenticate (JSON body {username, password}) -> JSON
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

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
    json_out(['ok' => true, 'redirect' => url('/admin')]);
}

if (is_admin()) {
    redirect(url('/admin'));
}

layout_header('Admin login — Online Public Access Catalog', ['active' => 'account']);
$loginOpts = ['title' => 'Admin login', 'subtitle' => 'Sign in to manage the catalog'];
include __DIR__ . '/../includes/login-form.php';
layout_footer();
