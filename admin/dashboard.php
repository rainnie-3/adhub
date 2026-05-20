<?php
/**
 * AdHub - Admin Dashboard
 * Uses: get_campaign_stats(), get_campaigns(), get_all_approvals(),
 *       get_total_spend(), render_badge(), render_progress_bar(),
 *       render_stat_card(), render_page_header(), format_currency(), e()
 */

$page_title   = 'Dashboard';
$require_auth = true;
$require_role = 'admin';

require_once __DIR__ . '/../includes/header.php';

// ── Fetch all data via functions ──────────────────────────────────────────────
$campaign_stats   = get_campaign_stats($pdo);
$client_count     = count_clients($pdo);
$pending_count    = count_pending_approvals($pdo);
$total_spend      = get_total_spend($pdo);
$recent_campaigns = get_campaigns($pdo, null, null);
$recent_campaigns = array_slice($recent_campaigns, 0, 5);
$recent_approvals = get_all_approvals($pdo);
$recent_approvals = array_slice($recent_approvals, 0, 5);
?>

<?= render_sidebar_overlay() ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="adhub-content-wrapper" id="contentWrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="adhub-main">

        <!-- Page header -->
        <?= render_page_header(
            'Dashboard',
            'Welcome back, ' . e($user_name) . '! Here\'s what\'s happening.',
            '<a href="/adhub/admin/campaigns.php" class="btn btn-adhub-primary">
                <i class="bi bi-plus-lg me-1"></i> New Campaign
             </a>'
        ) ?>

        <!-- ── Stat Cards ───────────────────────────────────────────────── -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <?= render_stat_card(
                    'bi-megaphone', 'indigo',
                    number_format($campaign_stats['total']),
                    'Total Campaigns',
                    '<div class="stat-change up"><i class="bi bi-arrow-up-short"></i>+3 this month</div>'
                ) ?>
            </div>
            <div class="col-sm-6 col-xl-3">
                <?= render_stat_card(
                    'bi-play-circle', 'green',
                    number_format($campaign_stats['active']),
                    'Active Campaigns',
                    '<div class="stat-change up"><i class="bi bi-arrow-up-short"></i>Running now</div>'
                ) ?>
            </div>
            <div class="col-sm-6 col-xl-3">
                <?= render_stat_card(
                    'bi-people', 'cyan',
                    number_format($client_count),
                    'Clients',
                    '<div class="stat-change up"><i class="bi bi-arrow-up-short"></i>+1 this week</div>'
                ) ?>
            </div>
            <div class="col-sm-6 col-xl-3">
                <?= render_stat_card(
                    'bi-hourglass-split', 'amber',
                    number_format($pending_count),
                    'Pending Approvals',
                    $pending_count > 0
                        ? '<div class="stat-change down"><i class="bi bi-exclamation-circle"></i> Needs attention</div>'
                        : '<div class="stat-change up"><i class="bi bi-check-circle"></i> All clear</div>'
                ) ?>
            </div>
        </div>

        <!-- ── Two-column row ───────────────────────────────────────────── -->
        <div class="row g-3 mb-4">

            <!-- Recent Campaigns -->
            <div class="col-lg-8">
                <div class="adhub-card h-100">
                    <div class="card-header">
                        <h5 class="card-title"><i class="bi bi-megaphone me-2 text-primary"></i>Recent Campaigns</h5>
                        <a href="/adhub/admin/campaigns.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_campaigns)): ?>
                            <?= render_empty_state('inbox', 'No campaigns yet.') ?>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="adhub-table">
                                <thead>
                                    <tr>
                                        <th>Campaign</th>
                                        <th>Client</th>
                                        <th>Status</th>
                                        <th>Progress</th>
                                        <th>Budget</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_campaigns as $c): ?>
                                    <tr>
                                        <td>
                                            <a href="/adhub/admin/campaigns.php?id=<?= $c['id'] ?>"
                                               class="fw-600 text-decoration-none text-dark">
                                                <?= e($c['title']) ?>
                                            </a>
                                        </td>
                                        <td><?= e($c['client_name']) ?></td>
                                        <td><?= render_badge($c['status']) ?></td>
                                        <td style="min-width:110px;">
                                            <?= render_progress_bar((int)$c['progress']) ?>
                                        </td>
                                        <td class="fw-600"><?= format_currency((float)$c['budget']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Approvals -->
            <div class="col-lg-4">
                <div class="adhub-card h-100">
                    <div class="card-header">
                        <h5 class="card-title"><i class="bi bi-patch-check me-2 text-success"></i>Approvals</h5>
                        <a href="/adhub/admin/campaigns.php?status=review" class="btn btn-sm btn-outline-success">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_approvals)): ?>
                            <?= render_empty_state('inbox', 'No approvals yet.') ?>
                        <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($recent_approvals as $a): ?>
                            <li class="list-group-item border-0 px-4 py-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-600 small"><?= e($a['campaign_title']) ?></div>
                                        <div class="text-muted" style="font-size:.75rem;"><?= e($a['client_name']) ?></div>
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

        <!-- ── Quick stats row ──────────────────────────────────────────── -->
        <div class="row g-3">
            <div class="col-md-4">
                <div class="adhub-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="stat-icon blue"><i class="bi bi-currency-dollar"></i></div>
                            <div>
                                <div class="stat-value"><?= format_currency($total_spend) ?></div>
                                <div class="stat-label">Total Ad Spend</div>
                            </div>
                        </div>
                        <?= render_progress_bar(68) ?>
                        <div class="text-muted small mt-1">68% of monthly budget used</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="adhub-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="stat-icon green"><i class="bi bi-graph-up-arrow"></i></div>
                            <div>
                                <div class="stat-value">4.2%</div>
                                <div class="stat-label">Avg. Conversion Rate</div>
                            </div>
                        </div>
                        <?= render_progress_bar(42) ?>
                        <div class="text-muted small mt-1">Industry avg: 3.1%</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="adhub-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="stat-icon indigo"><i class="bi bi-eye"></i></div>
                            <div>
                                <div class="stat-value">128K</div>
                                <div class="stat-label">Total Impressions</div>
                            </div>
                        </div>
                        <?= render_progress_bar(85) ?>
                        <div class="text-muted small mt-1">85% of target reached</div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <?= render_toast_container() ?>
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div><!-- /.adhub-content-wrapper -->
