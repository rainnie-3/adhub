<?php
/**
 * AdHub - Client Dashboard
 * Uses: get_campaigns(), count_pending_approvals(), get_client_approvals(),
 *       render_badge(), render_progress_bar(), render_stat_card(),
 *       render_page_header(), render_empty_state(), render_sidebar_overlay(),
 *       render_toast_container(), format_date(), e()
 */

$page_title   = 'My Dashboard';
$require_auth = true;
$require_role = 'client';

require_once __DIR__ . '/../includes/header.php';

// ── Fetch data via functions ──────────────────────────────────────────────────
$my_campaigns      = get_campaigns($pdo, null, $user_id);
$active_campaigns  = array_filter($my_campaigns, fn($c) => $c['status'] === 'active');
$pending_approvals = count_pending_approvals($pdo, $user_id);
$recent_campaigns  = array_slice($my_campaigns, 0, 5);
$recent_approvals  = get_client_approvals($pdo, $user_id);
$recent_approvals  = array_slice($recent_approvals, 0, 5);
?>

<?= render_sidebar_overlay() ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="adhub-content-wrapper" id="contentWrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="adhub-main">

        <?= render_page_header(
            'My Dashboard',
            'Welcome back, ' . e($user_name) . '! Here\'s your campaign overview.'
        ) ?>

        <!-- Stat cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <?= render_stat_card('bi-megaphone', 'indigo',
                    (string)count($my_campaigns), 'My Campaigns') ?>
            </div>
            <div class="col-sm-4">
                <?= render_stat_card('bi-play-circle', 'green',
                    (string)count($active_campaigns), 'Active Now') ?>
            </div>
            <div class="col-sm-4">
                <?= render_stat_card(
                    'bi-hourglass-split', 'amber',
                    (string)$pending_approvals,
                    'Awaiting My Approval',
                    $pending_approvals > 0
                        ? '<div class="stat-change down"><i class="bi bi-exclamation-circle"></i> Action needed</div>'
                        : '<div class="stat-change up"><i class="bi bi-check-circle"></i> All clear</div>'
                ) ?>
            </div>
        </div>

        <div class="row g-3">

            <!-- Campaign progress list -->
            <div class="col-lg-8">
                <div class="adhub-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-megaphone me-2 text-primary"></i>My Campaigns
                        </h5>
                        <a href="/adhub/client/campaigns.php" class="btn btn-sm btn-outline-primary">
                            View All
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_campaigns)): ?>
                            <?= render_empty_state('inbox', 'No campaigns assigned yet.') ?>
                        <?php else: ?>
                        <?php foreach ($recent_campaigns as $c): ?>
                        <div class="px-4 py-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="fw-600"><?= e($c['title']) ?></div>
                                    <div class="text-muted small">
                                        <?= format_date($c['start_date'], 'M d', 'TBD') ?>
                                        <?= $c['end_date'] ? ' → ' . format_date($c['end_date'], 'M d, Y') : '' ?>
                                    </div>
                                </div>
                                <?= render_badge($c['status']) ?>
                            </div>
                            <?= render_progress_bar((int)$c['progress']) ?>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent approvals -->
            <div class="col-lg-4">
                <div class="adhub-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-patch-check me-2 text-success"></i>Approvals
                        </h5>
                        <a href="/adhub/client/approvals.php" class="btn btn-sm btn-outline-success">
                            View All
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_approvals)): ?>
                            <?= render_empty_state('patch-check', 'No approvals yet.') ?>
                        <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($recent_approvals as $a): ?>
                            <li class="list-group-item border-0 px-4 py-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <i class="bi <?= approval_icon($a['status']) ?> me-1
                                           <?= $a['status'] === 'approved' ? 'text-success' : ($a['status'] === 'revision' ? 'text-danger' : 'text-warning') ?>">
                                        </i>
                                        <span class="fw-600 small"><?= e($a['campaign_title']) ?></span>
                                        <?php if ($a['notes']): ?>
                                        <div class="text-muted" style="font-size:.72rem;">
                                            <?= e(truncate_str($a['notes'], 50)) ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?= render_badge($a['status']) ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <!-- Quick links -->
        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <a href="/adhub/client/campaigns.php" class="adhub-card d-flex align-items-center gap-3 p-4 text-decoration-none" style="transition:box-shadow .2s;">
                    <div class="stat-icon indigo"><i class="bi bi-megaphone"></i></div>
                    <div>
                        <div class="fw-600">View All Campaigns</div>
                        <div class="text-muted small">See progress and details for all <?= count($my_campaigns) ?> campaign<?= count($my_campaigns) !== 1 ? 's' : '' ?></div>
                    </div>
                    <i class="bi bi-arrow-right ms-auto text-muted"></i>
                </a>
            </div>
            <div class="col-md-6">
                <a href="/adhub/client/approvals.php" class="adhub-card d-flex align-items-center gap-3 p-4 text-decoration-none" style="transition:box-shadow .2s;">
                    <div class="stat-icon <?= $pending_approvals > 0 ? 'amber' : 'green' ?>">
                        <i class="bi bi-patch-check"></i>
                    </div>
                    <div>
                        <div class="fw-600">Review Approvals</div>
                        <div class="text-muted small">
                            <?= $pending_approvals > 0
                                ? "$pending_approvals item" . ($pending_approvals !== 1 ? 's' : '') . " waiting for your review"
                                : 'All approvals are up to date' ?>
                        </div>
                    </div>
                    <i class="bi bi-arrow-right ms-auto text-muted"></i>
                </a>
            </div>
        </div>

    </main>

    <?= render_toast_container() ?>
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div><!-- /.adhub-content-wrapper -->
