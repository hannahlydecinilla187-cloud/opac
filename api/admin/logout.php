<?php
declare(strict_types=1);

/**
 * OPAC — admin logout.  GET /admin/logout
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

admin_logout_session();
redirect(url('/admin/login'));
