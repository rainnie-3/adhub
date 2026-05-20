<?php
/**
 * AdHub - Admin Analytics Page
 * Uses: get_monthly_report_data(), get_top_campaigns_by_impressions(),
 *       get_status_distribution(), get_report_totals(), render_page_header(),
 *       render_stat_card(), render_empty_state(), render_sidebar_overlay(),
 *       render_toast_container(), format_currency(), e()
 */

$page_title   = 'Analytics';
$require_auth = true;
$require_role = 'admin';

require_once __DIR__ . '/../includes/header.php';

// ── Fetch all chart data via functions ────────────────────────────────────────
$monthly       = get_monthly_report_data($pdo, 6);
$top_campaigns = get_top_campaigns_by_impressions($pdo, 5);
$status_dist   = get_status_distribution($pdo);
$totals        = get_report_totals($pdo);

// Prepare JSON for JS charts
$months_json  = json_encode(array_column($monthly, 'month'));
$imp_json     = json_encode(array_map('intval', array_column($monthly, 'imp')));
$clk_json     = json_encode(array_map('intval', array_column($monthly, 'clk')));
$spd_json     = json_encode(array_map('floatval', array_column($monthly, 'spd')));
$status_labels = json_encode(array_keys($status_dist));
$status_vals   = json_encode(array_values($status_dist));

// Color map for status donut
$status_colors = [
    'active'    => '#10b981',
    'draft'     => '#9ca3af',
    'review'    => '#f59e0b',
    'completed' => '#3b82f6',
    'paused'    => '#8b5cf6',
];
?>

<?= render_sidebar_overlay() ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="adhub-content-wrapper" id="contentWrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="adhub-main">

        <?= render_page_header(
            'Analytics',
            'Performance trends and campaign insights.',
            '<span class="text-muted small">Last updated: ' . date('M d, Y') . '</span>'
        ) ?>

        <!-- Summary stat cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <?= render_stat_card('bi-eye', 'indigo',
                    number_format($totals['imp'] ?? 0), 'Total Impressions') ?>
            </div>
            <div class="col-sm-6 col-xl-3">
                <?= render_stat_card('bi-cursor', 'cyan',
                    number_format($totals['clk'] ?? 0), 'Total Clicks') ?>
            </div>
            <div class="col-sm-6 col-xl-3">
                <?= render_stat_card('bi-check2-circle', 'green',
                    number_format($totals['cvt'] ?? 0), 'Total Conversions') ?>
            </div>
            <div class="col-sm-6 col-xl-3">
                <?= render_stat_card('bi-currency-dollar', 'amber',
                    format_currency((float)($totals['spd'] ?? 0)), 'Total Spend') ?>
            </div>
        </div>

        <!-- Charts row 1 -->
        <div class="row g-3 mb-4">

            <!-- Impressions & Clicks line chart -->
            <div class="col-lg-8">
                <div class="adhub-card h-100">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-graph-up me-2 text-primary"></i>
                            Impressions &amp; Clicks (6 months)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($monthly)): ?>
                            <?= render_empty_state('bar-chart', 'No report data yet. Add entries on the Reports page.') ?>
                        <?php else: ?>
                        <canvas id="trendChart" height="260"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Campaign status donut -->
            <div class="col-lg-4">
                <div class="adhub-card h-100">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-pie-chart me-2 text-success"></i>Campaign Status
                        </h5>
                    </div>
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        <?php if (empty($status_dist)): ?>
                            <?= render_empty_state('pie-chart', 'No campaigns yet.') ?>
                        <?php else: ?>
                        <canvas id="statusChart" width="200" height="200"></canvas>
                        <div class="mt-3 d-flex flex-wrap gap-2 justify-content-center">
                            <?php foreach ($status_dist as $status => $count):
                                $color = $status_colors[$status] ?? '#ccc';
                            ?>
                            <span class="d-flex align-items-center gap-1 small">
                                <span style="width:10px;height:10px;border-radius:2px;
                                             background:<?= $color ?>;display:inline-block;"></span>
                                <?= ucfirst($status) ?> (<?= $count ?>)
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <!-- Charts row 2 -->
        <div class="row g-3 mb-4">

            <!-- Monthly spend bar chart -->
            <div class="col-lg-7">
                <div class="adhub-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-bar-chart me-2 text-warning"></i>Monthly Ad Spend
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($monthly)): ?>
                            <?= render_empty_state('bar-chart', 'No spend data yet.') ?>
                        <?php else: ?>
                        <canvas id="spendChart" height="220"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Top campaigns table -->
            <div class="col-lg-5">
                <div class="adhub-card h-100">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-trophy me-2 text-warning"></i>Top Campaigns
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($top_campaigns)): ?>
                            <?= render_empty_state('trophy', 'No data yet.') ?>
                        <?php else: ?>
                        <table class="adhub-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Campaign</th>
                                    <th>Impressions</th>
                                    <th>Spend</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_campaigns as $i => $tc): ?>
                                <tr>
                                    <td class="text-muted"><?= $i + 1 ?></td>
                                    <td class="fw-600 small"><?= e($tc['title']) ?></td>
                                    <td><?= number_format($tc['imp']) ?></td>
                                    <td><?= format_currency((float)$tc['spd']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <!-- CTR Performance summary -->
        <div class="adhub-card mb-4">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-speedometer2 me-2 text-info"></i>Performance Summary
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-4 text-center">
                    <?php
                    $imp  = (int)($totals['imp'] ?? 0);
                    $clk  = (int)($totals['clk'] ?? 0);
                    $cvt  = (int)($totals['cvt'] ?? 0);
                    $spd  = (float)($totals['spd'] ?? 0);
                    $ctr  = $imp > 0 ? round($clk / $imp * 100, 2) : 0;
                    $cvr  = $clk > 0 ? round($cvt / $clk * 100, 2) : 0;
                    $cpc  = $clk > 0 ? round($spd / $clk, 2) : 0;
                    $cpm  = $imp > 0 ? round($spd / $imp * 1000, 2) : 0;

                    $perf_metrics = [
                        ['label' => 'Click-Through Rate', 'value' => $ctr . '%',
                         'icon'  => 'bi-cursor-fill', 'color' => 'text-primary',
                         'note'  => 'Industry avg ~2%'],
                        ['label' => 'Conversion Rate', 'value' => $cvr . '%',
                         'icon'  => 'bi-check-circle-fill', 'color' => 'text-success',
                         'note'  => 'Industry avg ~3%'],
                        ['label' => 'Cost Per Click', 'value' => format_currency($cpc),
                         'icon'  => 'bi-cash-coin', 'color' => 'text-warning',
                         'note'  => 'Lower is better'],
                        ['label' => 'CPM (per 1K impressions)', 'value' => format_currency($cpm),
                         'icon'  => 'bi-eye-fill', 'color' => 'text-info',
                         'note'  => 'Cost per 1,000 views'],
                    ];
                    foreach ($perf_metrics as $m):
                    ?>
                    <div class="col-6 col-md-3">
                        <div class="p-3 rounded-3" style="background:var(--ah-bg);">
                            <i class="bi <?= $m['icon'] ?> <?= $m['color'] ?> fs-3 d-block mb-2"></i>
                            <div class="fw-700 fs-4" style="font-family:var(--font-heading);">
                                <?= $m['value'] ?>
                            </div>
                            <div class="text-muted small"><?= $m['label'] ?></div>
                            <div style="font-size:.7rem;color:var(--ah-text-light);"><?= $m['note'] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </main>

    <?= render_toast_container() ?>
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div><!-- /.adhub-content-wrapper -->

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const months  = <?= $months_json ?>;
const imp     = <?= $imp_json ?>;
const clk     = <?= $clk_json ?>;
const spd     = <?= $spd_json ?>;
const sLabels = <?= $status_labels ?>;
const sVals   = <?= $status_vals ?>;
const sColors = <?= json_encode(array_map(fn($s) => $status_colors[$s] ?? '#e5e7eb', array_keys($status_dist))) ?>;

const chartDefaults = {
    responsive: true,
    plugins: { legend: { position: 'top' } },
    scales: { y: { beginAtZero: true } }
};

// Trend line chart
if (document.getElementById('trendChart')) {
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Impressions',
                    data: imp,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79,70,229,.08)',
                    fill: true, tension: .4, pointRadius: 4
                },
                {
                    label: 'Clicks',
                    data: clk,
                    borderColor: '#06b6d4',
                    backgroundColor: 'rgba(6,182,212,.08)',
                    fill: true, tension: .4, pointRadius: 4
                }
            ]
        },
        options: chartDefaults
    });
}

// Status donut
if (document.getElementById('statusChart')) {
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: sLabels.map(s => s.charAt(0).toUpperCase() + s.slice(1)),
            datasets: [{
                data: sVals.length ? sVals : [1],
                backgroundColor: sColors.length ? sColors : ['#e5e7eb'],
                borderWidth: 0
            }]
        },
        options: { cutout: '65%', plugins: { legend: { display: false } } }
    });
}

// Spend bar chart
if (document.getElementById('spendChart')) {
    new Chart(document.getElementById('spendChart'), {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: 'Ad Spend ($)',
                data: spd,
                backgroundColor: 'rgba(79,70,229,.7)',
                borderRadius: 6
            }]
        },
        options: { ...chartDefaults, plugins: { legend: { display: false } } }
    });
}
</script>
