<?php
declare(strict_types=1);

/**
 * OPAC — reusable sign-in form (used by /admin/login and /account).
 *
 * Set before including:
 *   $loginOpts = ['title' => ..., 'subtitle' => ...]
 *
 * The form POSTs JSON to the CURRENT url, so the including file must handle
 * POST the same way login.php does and return {ok, redirect}.
 */

$loginOpts = $loginOpts ?? [];
$loginTitle = (string) ($loginOpts['title'] ?? 'Sign in');
$loginSubtitle = (string) ($loginOpts['subtitle'] ?? 'Sign in to manage the catalog');
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card border-0 shadow-sm" style="border-radius: 1rem;">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-person-badge display-5 text-primary"></i>
                        <h1 class="h4 mt-2 mb-1"><?= e($loginTitle) ?></h1>
                        <p class="text-muted small mb-0"><?= e($loginSubtitle) ?></p>
                    </div>

                    <form id="login-form" novalidate>
                        <div class="mb-3">
                            <label class="form-label" for="username">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="username" name="username"
                                       autocomplete="username" required autofocus>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="password">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key"></i></span>
                                <input type="password" class="form-control" id="password" name="password"
                                       autocomplete="current-password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2" id="login-btn">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Sign in
                        </button>
                    </form>
                </div>
            </div>
            <p class="text-center text-muted small mt-3 mb-0">
                <a href="<?= e(url('/')) ?>" class="text-decoration-none">
                    <i class="bi bi-arrow-left me-1"></i>Back to home
                </a>
            </p>
        </div>
    </div>
</div>
<script>
    document.getElementById('login-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('login-btn');
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;

        btn.disabled = true;
        try {
            const res = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password }),
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.ok) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Welcome back!',
                    text: 'Signing you in…',
                    timer: 800,
                    showConfirmButton: false,
                });
                window.location.assign(data.redirect);
                return;
            }
            Swal.fire({
                icon: 'error',
                title: 'Login failed',
                text: data.message || 'Invalid username or password.',
            });
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Network error',
                text: 'Could not reach the server. Please try again.',
            });
        } finally {
            btn.disabled = false;
        }
    });
</script>
