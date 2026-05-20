<?php
/**
 * AdHub - Admin Analytics Page
 * Visual performance insights with charts.
 */

$page_title   = 'Analytics';
$require_auth = true;
$require_role = 'admin';

require_once __DIR__ . '/../includes/header.php';

// ── Aggregate data for charts ─────────────────────────────────────────────────

// Campaign status distribution
$status_dist = $pdo->query(
    "SELECT status, COUNT(*) AS total FROM campaigns GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

// Monthly spend (last 6 months)
$monthly = $pdo->query(
    "SELECT DATE_FORMAT(report_date,'%b %Y') AS month,
            SUM(impressions) AS imp, SUM(clicks) AS clk, SUM(spend) AS spd
     FROM reports
     WHERE report_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(report_date,'%Y-%m')
     ORDER BY report_date"
)->fetchAll();

// Top campaigns by impressions
$top_campaigns = $pdo->query(
    "SELECT c.title, SUM(r.impressions) AS imp, SUM(r.clicks) AS clk, SUM(r.spend) AS spd
     FROM reports r JOIN campaigns c ON r.campaign_id = c.id
     GROUP BY c.id, c.title
     ORDER BY imp DESC
     LIMIT 5"
)->fetchAll();

// JSON for JS charts
$months_json  = json_encode(array_column($monthly, 'month'));
$imp_json     = json_encode(array_column($monthly, 'imp'));
$clk_json     = json_encode(array_column($monthly, 'clk'));
$spd_json     = json_encode(array_column($monthly, 'spd'));

$status_labels = json_encode(array_keys($status_dist));
$status_vals   = json_encode(array_values($status_dist));
?>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="adhub-content-wrapper" id="contentWrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="adhub-main">

        <div class="page-header">
            <div>
                <h1 class="page-title">Analytics</h1>
                <p class="page-subtitle">Performance trends and campaign insights.</p>
            </div>
            <div class="text-muted small">Last updated: <?= date('M d, Y') ?></div>
        </div>

        <!-- Charts row -->
        <div class="row g-3 mb-4">

            <!-- Impressions & Clicks trend -->
            <div class="col-lg-8">
                <div class="adhub-card h-100">
                    <div class="card-header">
                        <h5 class="card-title"><i class="bi bi-graph-up me-2 text-primary"></i>Impressions & Clicks (6 months)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="trendChart" height="260"></canvas>
                    </div>
                </div>
            </div>

            <!-- Campaign status donut -->
            <div class="col-lg-4">
                <div class="adhub-card h-100">
                    <div class="card-header">
                        <h5 class="card-title"><i class="bi bi-pie-chart me-2 text-success"></i>Campaign Status</h5>
                    </div>
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        <canvas id="statusChart" width="200" height="200"></canvas>
                        <div class="mt-3 d-flex flex-wrap gap-2 justify-content-center">
                            <?php
                            $status_colors = [
                                'active'    => '#10b981',
                                'draft'     => '#9ca3af',
                                'review'    => '#f59e0b',
                                'completed' => '#3b82f6',
                                'paused'    => '#8b5cf6',
                            ];
                            foreach ($status_dist as $status => $count):
                                $color = $status_colors[$status] ?? '#ccc';
                            ?>
                            <span class="d-flex align-items-center gap-1 small">
                                <span style="width:10px;height:10px;border-radius:2px;background:<?= $color ?>;display:inline-block;"></span>
                                <?= ucfirst($status) ?> (<?= $count ?>)
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ad Spend bar chart -->
        <div class="row g-3 mb-4">
            <div class="col-lg-7">
                <div class="adhub-card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="bi bi-bar-chart me-2 text-warning"></i>Monthly Ad Spend</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="spendChart" height="220"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top campaigns table -->
            <div class="col-lg-5">
                <div class="adhub-card h-100">
                    <div class="card-header">
                        <h5 class="card-title"><i class="bi bi-trophy me-2 text-warning"></i>Top Campaigns</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($top_campaigns)): ?>
                        <div class="text-center py-4 text-muted small">No data yet.</div>
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
                                    <td class="fw-600 small"><?= htmlspecialchars($tc['title']) ?></td>
                                    <td><?= number_format($tc['imp']) ?></td>
                                    <td>$<?= number_format($tc['spd'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div><!-- /.adhub-content-wrapper --></div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const months  = <?= $months_json ?>;
const imp     = <?= $imp_json ?>;
const clk     = <?= $clk_json ?>;
const spd     = <?= $spd_json ?>;
const sLabels = <?= $status_labels ?>;
const sVals   = <?= $status_vals ?>;

// ── Trend chart (line) ────────────────────────────────────────────────────────
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: months.length ? months : ['No data'],
        datasets: [
            {
                label: 'Impressions',
                data: imp,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79,70,229,.08)',
                fill: true,
                tension: .4,
                pointRadius: 4,
            },
            {
                label: 'Clicks',
                data: clk,
                borderColor: '#06b6d4',
                backgroundColor: 'rgba(6,182,212,.08)',
                fill: true,
                tension: .4,
                pointRadius: 4,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true } }
    }
});

// ── Status donut ──────────────────────────────────────────────────────────────
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: sLabels.map(s => s.charAt(0).toUpperCase() + s.slice(1)),
        datasets: [{
            data: sVals.length ? sVals : [1],
            backgroundColor: sLabels.length
                ? sLabels.map(s => ({active:'#10b981',draft:'#9ca3af',review:'#f59e0b',completed:'#3b82f6',paused:'#8b5cf6'}[s] || '#e5e7eb'))
                : ['#e5e7eb'],
            borderWidth: 0,
        }]
    },
    options: {
        cutout: '65%',
        plugins: { legend: { display: false } }
    }
});

// ── Spend bar chart ───────────────────────────────────────────────────────────
new Chart(document.getElementById('spendChart'), {
    type: 'bar',
    data: {
        labels: months.length ? months : ['No data'],
        datasets: [{
            label: 'Ad Spend ($)',
            data: spd,
            backgroundColor: 'rgba(79,70,229,.7)',
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>
