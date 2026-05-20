<?php
/**
 * AdHub - Settings Page (Admin & Client)
 */

$page_title   = 'Settings';
$require_auth = true;

require_once __DIR__ . '/../includes/header.php';

$msg      = '';
$msg_type = 'success';

if (is_post()) {
    // Placeholder: settings can be expanded with a settings table
    $msg = 'Settings saved successfully.';
}
?>

<?= render_sidebar_overlay() ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="adhub-content-wrapper" id="contentWrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="adhub-main">

        <?= render_page_header('Settings', 'Configure your account preferences.') ?>

        <?php if ($msg): ?>
            <?= render_alert($msg, $msg_type) ?>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-8">

                <!-- Notification settings -->
                <div class="adhub-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title"><i class="bi bi-bell me-2 text-primary"></i>Notification Preferences</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?= csrf_field() ?>
                            <div class="d-flex flex-column gap-3">
                                <?php
                                $prefs = [
                                    ['id' => 'notif_approval',  'label' => 'Approval requests',         'sub' => 'Notify when a campaign needs your review'],
                                    ['id' => 'notif_campaign',  'label' => 'Campaign status changes',    'sub' => 'Notify when campaign status is updated'],
                                    ['id' => 'notif_asset',     'label' => 'New asset uploads',          'sub' => 'Notify when new files are added to a campaign'],
                                    ['id' => 'notif_revision',  'label' => 'Revision requests',          'sub' => 'Notify when a revision is requested'],
                                ];
                                foreach ($prefs as $p):
                                ?>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <div class="fw-600 small"><?= $p['label'] ?></div>
                                        <div class="text-muted" style="font-size:.75rem;"><?= $p['sub'] ?></div>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox"
                                               id="<?= $p['id'] ?>" name="<?= $p['id'] ?>" checked>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-adhub-primary">
                                    <i class="bi bi-save me-1"></i> Save Preferences
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Display settings -->
                <div class="adhub-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title"><i class="bi bi-palette me-2 text-success"></i>Display Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <?= csrf_field() ?>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="adhub-label">Date Format</label>
                                    <select class="form-select" name="date_format">
                                        <option selected>M d, Y (Jan 15, 2026)</option>
                                        <option>d/m/Y (15/01/2026)</option>
                                        <option>Y-m-d (2026-01-15)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="adhub-label">Currency Symbol</label>
                                    <select class="form-select" name="currency">
                                        <option selected>$ USD</option>
                                        <option>€ EUR</option>
                                        <option>£ GBP</option>
                                        <option>₱ PHP</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-adhub-primary">
                                        <i class="bi bi-save me-1"></i> Save Display Settings
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($user_role === 'admin'): ?>
                <!-- System info (admin only) -->
                <div class="adhub-card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="bi bi-info-circle me-2 text-info"></i>System Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="adhub-table">
                            <tbody>
                                <tr><td class="text-muted small fw-600" style="width:40%;">PHP Version</td>
                                    <td><?= phpversion() ?></td></tr>
                                <tr><td class="text-muted small fw-600">AdHub Version</td>
                                    <td>1.0.0</td></tr>
                                <tr><td class="text-muted small fw-600">Database</td>
                                    <td>MySQL / MariaDB</td></tr>
                                <tr><td class="text-muted small fw-600">Upload Max Size</td>
                                    <td><?= format_file_size(max_upload_size()) ?></td></tr>
                                <tr><td class="text-muted small fw-600">Server Time</td>
                                    <td><?= date('Y-m-d H:i:s') ?></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <!-- Quick links sidebar -->
            <div class="col-lg-4">
                <div class="adhub-card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="bi bi-link-45deg me-2"></i>Quick Links</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item border-0 px-4">
                                <a href="/adhub/profile.php" class="d-flex align-items-center gap-2 py-2 text-decoration-none text-dark">
                                    <i class="bi bi-person-circle text-primary"></i> My Profile
                                    <i class="bi bi-arrow-right ms-auto text-muted small"></i>
                                </a>
                            </li>
                            <?php if ($user_role === 'admin'): ?>
                            <li class="list-group-item border-0 px-4">
                                <a href="/adhub/admin/clients.php" class="d-flex align-items-center gap-2 py-2 text-decoration-none text-dark">
                                    <i class="bi bi-people text-cyan"></i> Manage Clients
                                    <i class="bi bi-arrow-right ms-auto text-muted small"></i>
                                </a>
                            </li>
                            <li class="list-group-item border-0 px-4">
                                <a href="/adhub/admin/analytics.php" class="d-flex align-items-center gap-2 py-2 text-decoration-none text-dark">
                                    <i class="bi bi-graph-up text-success"></i> Analytics
                                    <i class="bi bi-arrow-right ms-auto text-muted small"></i>
                                </a>
                            </li>
                            <?php else: ?>
                            <li class="list-group-item border-0 px-4">
                                <a href="/adhub/client/approvals.php" class="d-flex align-items-center gap-2 py-2 text-decoration-none text-dark">
                                    <i class="bi bi-patch-check text-success"></i> My Approvals
                                    <i class="bi bi-arrow-right ms-auto text-muted small"></i>
                                </a>
                            </li>
                            <?php endif; ?>
                            <li class="list-group-item border-0 px-4">
                                <a href="/adhub/auth/logout.php" class="d-flex align-items-center gap-2 py-2 text-decoration-none text-danger">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                    <i class="bi bi-arrow-right ms-auto small"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <?= render_toast_container() ?>
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div>
