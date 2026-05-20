<?php
/**
 * AdHub - Client Campaigns Page
 * Uses: get_campaigns(), render_badge(), render_progress_bar(),
 *       render_page_header(), render_empty_state(), render_sidebar_overlay(),
 *       format_date(), format_currency(), e()
 */

$page_title   = 'My Campaigns';
$require_auth = true;
$require_role = 'client';

require_once __DIR__ . '/../includes/header.php';

// ── Fetch this client's campaigns via function ────────────────────────────────
$campaigns     = get_campaigns($pdo, null, $user_id);
$active_count  = count(array_filter($campaigns, fn($c) => $c['status'] === 'active'));
$total_budget  = array_sum(array_column($campaigns, 'budget'));
$avg_progress  = count($campaigns) > 0
    ? round(array_sum(array_column($campaigns, 'progress')) / count($campaigns))
    : 0;
?>

<?= render_sidebar_overlay() ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="adhub-content-wrapper" id="contentWrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="adhub-main">

        <?= render_page_header(
            'My Campaigns',
            'All campaigns created for your account.'
        ) ?>

        <?php if (empty($campaigns)): ?>
        <div class="adhub-card">
            <div class="card-body">
                <?= render_empty_state('megaphone',
                    'No campaigns assigned to your account yet. Contact your agency to get started.') ?>
            </div>
        </div>

        <?php else: ?>

        <!-- Summary row -->
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <?= render_stat_card('bi-megaphone', 'indigo',
                    (string)count($campaigns), 'Total Campaigns') ?>
            </div>
            <div class="col-sm-4">
                <?= render_stat_card('bi-play-circle', 'green',
                    (string)$active_count, 'Active Campaigns') ?>
            </div>
            <div class="col-sm-4">
                <?= render_stat_card('bi-graph-up', 'cyan',
                    $avg_progress . '%', 'Avg. Progress') ?>
            </div>
        </div>

        <!-- Filter bar -->
        <div class="d-flex gap-2 flex-wrap align-items-center mb-3">
            <?php
            $filter = query_param('status', 'all');
            $filters = ['all' => 'All', 'active' => 'Active', 'draft' => 'Draft',
                        'review' => 'In Review', 'completed' => 'Completed', 'paused' => 'Paused'];
            foreach ($filters as $val => $label):
            ?>
            <a href="?status=<?= $val ?>"
               class="btn btn-sm <?= $filter === $val ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Campaign cards -->
        <div class="row g-3">
            <?php
            // Apply front-end filter
            $visible = $filter === 'all'
                ? $campaigns
                : array_filter($campaigns, fn($c) => $c['status'] === $filter);

            if (empty($visible)):
            ?>
            <div class="col-12">
                <div class="adhub-card">
                    <div class="card-body">
                        <?= render_empty_state('funnel', "No campaigns with status \"$filter\".") ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($visible as $c): ?>
            <div class="col-md-6 col-xl-4">
                <div class="adhub-card h-100">
                    <div class="card-body">

                        <!-- Title + status -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h6 class="fw-700 mb-0" style="font-family:var(--font-heading);">
                                <?= e($c['title']) ?>
                            </h6>
                            <?= render_badge($c['status']) ?>
                        </div>

                        <!-- Description -->
                        <?php if ($c['description']): ?>
                        <p class="text-muted small mb-3">
                            <?= e(truncate_str($c['description'], 90)) ?>
                        </p>
                        <?php endif; ?>

                        <!-- Progress -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Progress</span>
                                <span class="fw-600"><?= $c['progress'] ?>%</span>
                            </div>
                            <?= render_progress_bar((int)$c['progress']) ?>
                        </div>

                        <!-- Meta row -->
                        <div class="row g-2 text-center border-top pt-3">
                            <div class="col-4">
                                <div class="text-muted" style="font-size:.65rem;text-transform:uppercase;letter-spacing:.05em;">Budget</div>
                                <div class="fw-700 small"><?= format_currency((float)$c['budget'], '$', 0) ?></div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted" style="font-size:.65rem;text-transform:uppercase;letter-spacing:.05em;">Start</div>
                                <div class="fw-700 small"><?= format_date($c['start_date'], 'M d') ?></div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted" style="font-size:.65rem;text-transform:uppercase;letter-spacing:.05em;">End</div>
                                <div class="fw-700 small"><?= format_date($c['end_date'], 'M d') ?></div>
                            </div>
                        </div>

                        <!-- View assets link -->
                        <?php
                        $asset_count = db_count($pdo, 'campaign_assets', 'campaign_id = ?', [$c['id']]);
                        if ($asset_count > 0):
                        ?>
                        <div class="mt-3 pt-2 border-top">
                            <span class="text-muted small">
                                <i class="bi bi-paperclip me-1"></i>
                                <?= $asset_count ?> file<?= $asset_count !== 1 ? 's' : '' ?> attached
                            </span>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php endif; ?>

    </main>

    <?= render_toast_container() ?>
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div><!-- /.adhub-content-wrapper -->
