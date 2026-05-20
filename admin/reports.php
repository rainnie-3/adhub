<?php
/**
 * AdHub - Admin Reports Page
 * View and add campaign performance reports.
 */

$page_title   = 'Reports';
$require_auth = true;
$require_role = 'admin';

require_once __DIR__ . '/../includes/header.php';

$msg      = '';
$msg_type = 'success';

// Fetch campaigns for dropdown
$campaigns = $pdo->query("SELECT id, title FROM campaigns ORDER BY title")->fetchAll();

// ── Handle report submission ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaign_id  = intval($_POST['campaign_id'] ?? 0);
    $impressions  = intval($_POST['impressions'] ?? 0);
    $clicks       = intval($_POST['clicks']      ?? 0);
    $conversions  = intval($_POST['conversions'] ?? 0);
    $spend        = floatval($_POST['spend']     ?? 0);
    $report_date  = $_POST['report_date']        ?? date('Y-m-d');

    if ($campaign_id < 1) {
        $msg = 'Please select a campaign.'; $msg_type = 'danger';
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO reports (campaign_id, impressions, clicks, conversions, spend, report_date)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$campaign_id, $impressions, $clicks, $conversions, $spend, $report_date]);
        $msg = 'Report saved successfully.';
    }
}

// ── Fetch all reports ─────────────────────────────────────────────────────────
$reports = $pdo->query(
    "SELECT r.*, c.title AS campaign_title,
            CASE WHEN r.impressions > 0 THEN ROUND(r.clicks / r.impressions * 100, 2) ELSE 0 END AS ctr,
            CASE WHEN r.clicks > 0 THEN ROUND(r.conversions / r.clicks * 100, 2) ELSE 0 END AS cvr
     FROM reports r
     JOIN campaigns c ON r.campaign_id = c.id
     ORDER BY r.report_date DESC"
)->fetchAll();

// ── Totals ────────────────────────────────────────────────────────────────────
$totals = $pdo->query(
    "SELECT SUM(impressions) AS imp, SUM(clicks) AS clk, SUM(conversions) AS cvt, SUM(spend) AS spd FROM reports"
)->fetch();
?>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="adhub-content-wrapper" id="contentWrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="adhub-main">

        <div class="page-header">
            <div>
                <h1 class="page-title">Reports</h1>
                <p class="page-subtitle">Campaign performance data across all clients.</p>
            </div>
            <button class="btn btn-adhub-primary" data-bs-toggle="modal" data-bs-target="#addReportModal">
                <i class="bi bi-plus-lg me-1"></i> Add Report Entry
            </button>
        </div>

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible alert-auto-dismiss d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-<?= $msg_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill"></i>
            <div><?= htmlspecialchars($msg) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Summary totals -->
        <div class="row g-3 mb-4">
            <?php
            $sum_cards = [
                ['label' => 'Total Impressions', 'value' => number_format($totals['imp'] ?? 0), 'icon' => 'bi-eye',         'color' => 'indigo'],
                ['label' => 'Total Clicks',       'value' => number_format($totals['clk'] ?? 0), 'icon' => 'bi-cursor',      'color' => 'cyan'],
                ['label' => 'Conversions',         'value' => number_format($totals['cvt'] ?? 0), 'icon' => 'bi-check2-circle', 'color' => 'green'],
                ['label' => 'Total Spend',         'value' => '$' . number_format($totals['spd'] ?? 0, 2), 'icon' => 'bi-currency-dollar', 'color' => 'amber'],
            ];
            foreach ($sum_cards as $card): ?>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon <?= $card['color'] ?>"><i class="bi <?= $card['icon'] ?>"></i></div>
                    <div>
                        <div class="stat-value"><?= $card['value'] ?></div>
                        <div class="stat-label"><?= $card['label'] ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Reports table -->
        <div class="adhub-card">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-file-bar-graph me-2 text-primary"></i>Performance Log</h5>
                <input type="text" class="form-control form-control-sm" style="width:200px;"
                       placeholder="Search…" data-filter-table="#reportsTable">
            </div>
            <div class="card-body p-0">
                <?php if (empty($reports)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-clipboard-data fs-1 d-block mb-3 opacity-25"></i>
                    <p>No report entries yet. Add your first entry above.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="adhub-table" id="reportsTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Campaign</th>
                                <th>Impressions</th>
                                <th>Clicks</th>
                                <th>CTR</th>
                                <th>Conversions</th>
                                <th>CVR</th>
                                <th>Spend</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $r): ?>
                            <tr>
                                <td class="small text-muted"><?= date('M d, Y', strtotime($r['report_date'])) ?></td>
                                <td class="fw-600 small"><?= htmlspecialchars($r['campaign_title']) ?></td>
                                <td><?= number_format($r['impressions']) ?></td>
                                <td><?= number_format($r['clicks']) ?></td>
                                <td>
                                    <span class="adhub-badge <?= $r['ctr'] >= 2 ? 'badge-active' : 'badge-review' ?>">
                                        <?= $r['ctr'] ?>%
                                    </span>
                                </td>
                                <td><?= number_format($r['conversions']) ?></td>
                                <td>
                                    <span class="adhub-badge <?= $r['cvr'] >= 3 ? 'badge-active' : 'badge-draft' ?>">
                                        <?= $r['cvr'] ?>%
                                    </span>
                                </td>
                                <td class="fw-600">$<?= number_format($r['spend'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div><!-- /.adhub-content-wrapper --></div>

<!-- Add Report Modal -->
<div class="modal fade" id="addReportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:var(--ah-radius);">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-700">Add Report Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="adhub-label">Campaign *</label>
                            <select name="campaign_id" class="form-select" required>
                                <option value="">— Select —</option>
                                <?php foreach ($campaigns as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="adhub-label">Report Date</label>
                            <input type="date" name="report_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-6">
                            <label class="adhub-label">Impressions</label>
                            <input type="number" name="impressions" class="form-control" min="0" placeholder="0">
                        </div>
                        <div class="col-6">
                            <label class="adhub-label">Clicks</label>
                            <input type="number" name="clicks" class="form-control" min="0" placeholder="0">
                        </div>
                        <div class="col-6">
                            <label class="adhub-label">Conversions</label>
                            <input type="number" name="conversions" class="form-control" min="0" placeholder="0">
                        </div>
                        <div class="col-6">
                            <label class="adhub-label">Spend ($)</label>
                            <input type="number" name="spend" class="form-control" min="0" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-adhub-primary"><i class="bi bi-save me-1"></i> Save Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>
