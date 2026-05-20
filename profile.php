<?php
/**
 * AdHub - Profile Page (Admin & Client)
 * View and update account info and password.
 */

$page_title   = 'My Profile';
$require_auth = true;

require_once __DIR__ . '/../includes/header.php';

$msg      = '';
$msg_type = 'success';

// Fetch full user record
$profile = get_user($pdo, $user_id);

// ── Handle profile update ─────────────────────────────────────────────────────
if (is_post()) {
    $action = input_str($_POST, 'form_action');

    if ($action === 'update_profile') {
        $name    = input_str($_POST, 'name');
        $company = input_str($_POST, 'company');

        if (empty($name)) {
            $msg = 'Name cannot be empty.'; $msg_type = 'danger';
        } else {
            db_update($pdo, 'users', ['name' => $name, 'company' => $company], $user_id);
            $_SESSION['user_name'] = $name;
            $user_name = $name;
            $profile   = get_user($pdo, $user_id);
            $msg       = 'Profile updated successfully.';
        }

    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $profile['password'])) {
            $msg = 'Current password is incorrect.'; $msg_type = 'danger';
        } elseif (strlen($new) < 8) {
            $msg = 'New password must be at least 8 characters.'; $msg_type = 'danger';
        } elseif ($new !== $confirm) {
            $msg = 'New passwords do not match.'; $msg_type = 'danger';
        } else {
            db_update($pdo, 'users', ['password' => password_hash($new, PASSWORD_DEFAULT)], $user_id);
            $msg = 'Password changed successfully.';
        }
    }
}

$base = $user_role === 'admin' ? '/adhub/admin' : '/adhub/client';
?>

<?= render_sidebar_overlay() ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="adhub-content-wrapper" id="contentWrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="adhub-main">

        <?= render_page_header('My Profile', 'Manage your account information.') ?>

        <?php if ($msg): ?>
            <?= render_alert($msg, $msg_type) ?>
        <?php endif; ?>

        <div class="row g-4">

            <!-- Profile card -->
            <div class="col-lg-4">
                <div class="adhub-card text-center p-4">
                    <div class="adhub-avatar mx-auto mb-3"
                         style="width:80px;height:80px;font-size:2rem;">
                        <?= avatar_initial($profile['name']) ?>
                    </div>
                    <h5 class="fw-700 mb-1" style="font-family:var(--font-heading);">
                        <?= e($profile['name']) ?>
                    </h5>
                    <div class="text-muted small mb-2"><?= e($profile['email']) ?></div>
                    <span class="adhub-badge <?= $user_role === 'admin' ? 'badge-completed' : 'badge-active' ?>">
                        <?= ucfirst($user_role) ?>
                    </span>
                    <?php if ($profile['company']): ?>
                    <div class="mt-3 pt-3 border-top text-muted small">
                        <i class="bi bi-building me-1"></i><?= e($profile['company']) ?>
                    </div>
                    <?php endif; ?>
                    <div class="mt-2 text-muted small">
                        <i class="bi bi-calendar me-1"></i>Joined <?= format_date($profile['created_at']) ?>
                    </div>
                </div>
            </div>

            <!-- Edit forms -->
            <div class="col-lg-8">

                <!-- Update profile info -->
                <div class="adhub-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title"><i class="bi bi-person me-2 text-primary"></i>Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <?= csrf_field() ?>
                            <input type="hidden" name="form_action" value="update_profile">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="adhub-label">Full Name *</label>
                                    <input type="text" name="name" class="form-control"
                                           value="<?= e($profile['name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="adhub-label">Email</label>
                                    <input type="email" class="form-control"
                                           value="<?= e($profile['email']) ?>" disabled>
                                    <div class="form-text">Email cannot be changed.</div>
                                </div>
                                <div class="col-12">
                                    <label class="adhub-label">Company / Organization</label>
                                    <input type="text" name="company" class="form-control"
                                           value="<?= e($profile['company'] ?? '') ?>"
                                           placeholder="Your company name">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-adhub-primary">
                                        <i class="bi bi-save me-1"></i> Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change password -->
                <div class="adhub-card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="bi bi-shield-lock me-2 text-warning"></i>Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <?= csrf_field() ?>
                            <input type="hidden" name="form_action" value="change_password">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="adhub-label">Current Password *</label>
                                    <input type="password" name="current_password"
                                           class="form-control" required placeholder="Enter current password">
                                </div>
                                <div class="col-md-6">
                                    <label class="adhub-label">New Password *</label>
                                    <input type="password" name="new_password"
                                           class="form-control" required minlength="8"
                                           placeholder="Min. 8 characters">
                                </div>
                                <div class="col-md-6">
                                    <label class="adhub-label">Confirm New Password *</label>
                                    <input type="password" name="confirm_password"
                                           class="form-control" required placeholder="Repeat new password">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bi bi-key me-1"></i> Update Password
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>

    </main>

    <?= render_toast_container() ?>
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div>
