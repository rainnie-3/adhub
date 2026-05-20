<?php
/**
 * AdHub - Login Page
 * Uses: attempt_login(), is_logged_in(), current_role(), e()
 */

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect(current_role() === 'admin'
        ? '/adhub/admin/dashboard.php'
        : '/adhub/client/dashboard.php');
}

$error = '';

// ── Handle POST ──────────────────────────────────────────────────────────────
if (is_post()) {
    $email    = input_str($_POST, 'email');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $user = attempt_login($pdo, $email, $password);
        if ($user) {
            redirect($user['role'] === 'admin'
                ? '/adhub/admin/dashboard.php'
                : '/adhub/client/dashboard.php');
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — AdHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/adhub/assets/css/style.css">
</head>
<body class="auth-page">

    <div class="auth-card">

        <!-- Brand -->
        <div class="auth-logo">
            <span class="auth-logo-icon">◈</span>
            <span class="auth-logo-text">AdHub</span>
        </div>

        <h1 class="auth-title">Welcome back</h1>
        <p class="auth-subtitle">Sign in to your agency dashboard</p>

        <!-- Error alert -->
        <?php if ($error): ?>
        <?= render_alert($error, 'danger') ?>
        <?php endif; ?>

        <!-- Unauthorized redirect message -->
        <?php if (query_param('error') === 'unauthorized'): ?>
        <?= render_alert("You don't have permission to access that page.", 'warning') ?>
        <?php endif; ?>

        <!-- Login form -->
        <form method="POST" action="" class="needs-validation" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="adhub-label" for="email">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-envelope text-muted"></i>
                    </span>
                    <input type="email" id="email" name="email"
                           class="form-control border-start-0 ps-0"
                           placeholder="you@company.com"
                           value="<?= e($_POST['email'] ?? '') ?>"
                           required autocomplete="email">
                </div>
            </div>

            <div class="mb-4">
                <label class="adhub-label" for="password">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-lock text-muted"></i>
                    </span>
                    <input type="password" id="password" name="password"
                           class="form-control border-start-0 ps-0"
                           placeholder="••••••••"
                           required autocomplete="current-password">
                    <button type="button" class="input-group-text bg-light border-start-0"
                            id="togglePwd" title="Show/hide password">
                        <i class="bi bi-eye text-muted" id="togglePwdIcon"></i>
                    </button>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="form-check m-0">
                    <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
                    <label class="form-check-label small" for="rememberMe">Remember me</label>
                </div>
                <a href="#" class="small text-primary">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-adhub-primary w-100 py-2 mb-3">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <p class="text-center text-muted small mb-0">
            Don't have an account?
            <a href="/adhub/auth/register.php" class="text-primary fw-600">Create one</a>
        </p>

        <!-- Demo credentials -->
        <div class="mt-4 p-3 rounded-3" style="background:#f8f9fa;font-size:.78rem;">
            <div class="fw-600 mb-1 text-muted">Demo Credentials</div>
            <div>Admin: <code>admin@adhub.com</code> / <code>password</code></div>
            <div>Client: <code>client@adhub.com</code> / <code>password</code></div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/adhub/assets/js/script.js"></script>
    <script>
        // Toggle password visibility
        const pwd       = document.getElementById('password');
        const toggleBtn = document.getElementById('togglePwd');
        const icon      = document.getElementById('togglePwdIcon');
        toggleBtn?.addEventListener('click', () => {
            const show  = pwd.type === 'password';
            pwd.type    = show ? 'text' : 'password';
            icon.className = show ? 'bi bi-eye-slash text-muted' : 'bi bi-eye text-muted';
        });
    </script>
</body>
</html>
