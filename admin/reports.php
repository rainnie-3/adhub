<?php
/**
 * AdHub - Admin Reports Page
 * Uses: get_all_reports(), get_report_totals(), save_report(),
 *       render_badge(), render_alert(), render_page_header(),
 *       render_empty_state(), render_stat_card(), render_toast_container(),
 *       render_sidebar_overlay(), format_currency(), format_date(),
 *       input_int(), input_float(), input_str(), is_post(), e()
 */

$page_title   = 'Reports';
$require_auth = true;
$require_role = 'admin';

require_once __DIR__ . '/../includes/header.php';

$msg      = '';
$msg_type = 'success';

// Fetch campaigns for dropdown
$campaigns = db_query($pdo, "SELECT id, title FROM campaigns ORDER BY title");

// ── Handle report submission ──────────────────────────────────────────────────
if (is_post()) {
    $result   = save_report($pdo, $_POST);
    $msg      = $result['message'];
    $msg_type = $result['success'] ? 'success' : 'danger';
}

// ── Fetch data via functions ──────────────────────────────────────────────────
$reports = get_all_reports($pdo);
$totals  = get_report_totals($pdo);
?>

<?= render_sidebar_overlay() ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="adhub-content-wrapper" id="contentWrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="adhub-main">

        <?= render_page_header(
            'Reports',
            'Campaign performance data across all clients.',
            '<button class="btn btn-adhub-primary" data-bs-toggle="modal" data-bs-target="#addReportModal">
                <i class="bi bi-plus-lg me-1"></i> Add Report Entry
             </button>'
        ) ?>

        <?php if ($msg): ?>
            <?= render_alert($msg, $msg_type) ?>
        <?php endif; ?>

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
                    number_format($totals['cvt'] ?? 0), 'Conversions') ?>
            </div>
            <div class="col-sm-6 col-xl-3">
                <?= render_stat_card('bi-currency-dollar', 'amber',
                    format_currency((float)($totals['spd'] ?? 0)), 'Total Spend') ?>
            </div>
        </div>

        <!-- Reports table -->
        <div class="adhub-card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-file-bar-graph me-2 text-primary"></i>Performance Log
                </h5>
                <input type="text" class="form-control form-control-sm" style="width:200px;"
                       placeholder="Search…" data-filter-table="#reportsTable">
            </div>
            <div class="card-body p-0">
                <?php if (empty($reports)): ?>
                    <?= render_empty_state('clipboard-data', 'No report entries yet. Add your first entry above.') ?>
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
                                <td class="small text-muted"><?= format_date($r['report_date']) ?></td>
                                <td class="fw-600 small"><?= e($r['campaign_title']) ?></td>
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
                                <td class="fw-600"><?= format_currency((float)$r['spend']) ?></td>
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
</div><!-- /.adhub-content-wrapper -->

<!-- Add Report Modal -->
<div class="modal fade" id="addReportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:var(--ah-radius);">
            <form method="POST" class="needs-validation" novalidate>
                <?= csrf_field() ?>
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
                                <option value="<?= $c['id'] ?>"><?= e($c['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="adhub-label">Report Date</label>
                            <input type="date" name="report_date" class="form-control"
                                   value="<?= date('Y-m-d') ?>">
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
                            <input type="number" name="spend" class="form-control"
                                   min="0" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-adhub-primary">
                        <i class="bi bi-save me-1"></i> Save Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= render_toast_container() ?>
