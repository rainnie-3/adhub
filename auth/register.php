<?php
/**
 * AdHub - Registration Page
 * Uses: register_user(), validate_registration(), is_logged_in(),
 *       current_role(), redirect(), render_alert(), is_post(),
 *       input_str(), csrf_field(), e()
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

$errors  = [];
$success = '';

// ── Handle POST ──────────────────────────────────────────────────────────────
if (is_post()) {
    $data = [
        'name'     => input_str($_POST, 'name'),
        'email'    => input_str($_POST, 'email'),
        'password' => $_POST['password'] ?? '',
        'confirm'  => $_POST['confirm']  ?? '',
    ];
    $company = input_str($_POST, 'company');

    // Validate form fields
    $validation = validate_registration($data);

    if (!$validation['valid']) {
        $errors = $validation['errors'];
    } else {
        // Attempt registration
        $result = register_user($pdo, $data['name'], $data['email'], $data['password'], $company);

        if ($result['success']) {
            $success = 'Account created! You can now <a href="/adhub/auth/login.php" class="alert-link">sign in</a>.';
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — AdHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/adhub/assets/css/style.css">
</head>
<body class="auth-page">

    <div class="auth-card" style="max-width:480px;">

        <!-- Brand -->
        <div class="auth-logo">
            <span class="auth-logo-icon">◈</span>
            <span class="auth-logo-text">AdHub</span>
        </div>

        <h1 class="auth-title">Create an account</h1>
        <p class="auth-subtitle">Start managing your campaigns with AdHub</p>

        <!-- Validation errors -->
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger mb-3">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Please fix the following:</strong>
            <ul class="mb-0 mt-1 ps-3">
                <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Success message -->
        <?php if ($success): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-check-circle-fill"></i>
            <div><?= $success ?></div>
        </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <!-- Registration form -->
        <form method="POST" action="" class="needs-validation" novalidate>
            <?= csrf_field() ?>

            <div class="row g-3 mb-3">
                <div class="col-12">
                    <label class="adhub-label" for="name">Full Name *</label>
                    <input type="text" id="name" name="name" class="form-control"
                           placeholder="Jane Smith"
                           value="<?= e($_POST['name'] ?? '') ?>" required>
                </div>

                <div class="col-12">
                    <label class="adhub-label" for="email">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="jane@company.com"
                           value="<?= e($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="col-12">
                    <label class="adhub-label" for="company">Company / Organization</label>
                    <input type="text" id="company" name="company" class="form-control"
                           placeholder="Acme Corp (optional)"
                           value="<?= e($_POST['company'] ?? '') ?>">
                </div>

                <div class="col-sm-6">
                    <label class="adhub-label" for="password">Password *</label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Min. 8 characters" required minlength="8">
                </div>

                <div class="col-sm-6">
                    <label class="adhub-label" for="confirm">Confirm Password *</label>
                    <input type="password" id="confirm" name="confirm" class="form-control"
                           placeholder="Repeat password" required>
                </div>
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="terms" required>
                <label class="form-check-label small" for="terms">
                    I agree to the
                    <a href="#" class="text-primary">Terms of Service</a> and
                    <a href="#" class="text-primary">Privacy Policy</a>
                </label>
            </div>

            <button type="submit" class="btn btn-adhub-primary w-100 py-2 mb-3">
                <i class="bi bi-person-plus me-2"></i>Create Account
            </button>
        </form>
        <?php endif; ?>

        <p class="text-center text-muted small mb-0">
            Already have an account?
            <a href="/adhub/auth/login.php" class="text-primary fw-600">Sign in</a>
        </p>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/adhub/assets/js/script.js"></script>
</body>
</html>
