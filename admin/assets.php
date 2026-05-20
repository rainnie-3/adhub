<?php
/**
 * AdHub - Admin Asset Management (v2)
 * Fixed: upload zone click + drag-and-drop fully working.
 */

$page_title   = 'Assets';
$require_auth = true;
$require_role = 'admin';

require_once __DIR__ . '/../includes/header.php';

$msg        = '';
$msg_type   = 'success';
$upload_dir = __DIR__ . '/../uploads/';

$campaigns = db_query($pdo, "SELECT id, title FROM campaigns ORDER BY title");

// ── Handle upload ─────────────────────────────────────────────────────────────
if (is_post() && isset($_FILES['asset_file']) && $_FILES['asset_file']['error'] !== UPLOAD_ERR_NO_FILE) {
    $campaign_id = input_int($_POST, 'campaign_id');
    $result      = upload_asset($pdo, $_FILES['asset_file'], $campaign_id, $user_id, $upload_dir);
    $msg         = $result['message'];
    $msg_type    = $result['success'] ? 'success' : 'danger';
}

// ── Handle delete ─────────────────────────────────────────────────────────────
if (query_param('action') === 'delete') {
    $del_id  = input_int($_GET, 'id');
    $deleted = delete_asset($pdo, $del_id, $upload_dir);
    $msg     = $deleted ? 'Asset deleted.' : 'Asset not found.';
    $msg_type = $deleted ? 'success' : 'danger';
}

$assets = get_all_assets($pdo);
?>

<?= render_sidebar_overlay() ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="adhub-content-wrapper" id="contentWrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="adhub-main">

        <?= render_page_header(
            'Asset Manager',
            'Upload and manage campaign files.',
            '<button class="btn btn-adhub-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="bi bi-cloud-upload me-1"></i> Upload Asset
             </button>'
        ) ?>

        <?php if ($msg): ?>
            <?= render_alert($msg, $msg_type) ?>
        <?php endif; ?>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <?= render_stat_card('bi-files', 'indigo', (string)count($assets), 'Total Files') ?>
            </div>
            <div class="col-sm-4">
                <?= render_stat_card('bi-hdd', 'cyan',
                    format_file_size(array_sum(array_column($assets, 'file_size'))),
                    'Total Storage Used') ?>
            </div>
            <div class="col-sm-4">
                <?= render_stat_card('bi-folder2-open', 'green',
                    (string)count(array_unique(array_column($assets, 'campaign_id'))),
                    'Campaigns with Assets') ?>
            </div>
        </div>

        <!-- Assets table -->
        <div class="adhub-card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-folder2-open me-2 text-warning"></i>Uploaded Files
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary"><?= count($assets) ?> files</span>
                    <div class="input-group input-group-sm" style="width:200px;">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search…"
                               data-filter-table="#assetsTable">
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($assets)): ?>
                    <?= render_empty_state('folder-x', 'No files uploaded yet. Click "Upload Asset" to get started.') ?>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="adhub-table" id="assetsTable">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Campaign</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Uploaded By</th>
                                <th>Date</th>
                                <th style="width:110px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assets as $a): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi <?= file_type_icon($a['file_type']) ?> fs-5"></i>
                                        <span class="fw-600 small"><?= e($a['original_name']) ?></span>
                                    </div>
                                </td>
                                <td class="small text-muted"><?= e($a['campaign_title']) ?></td>
                                <td>
                                    <span class="adhub-badge badge-draft" style="font-size:.65rem;">
                                        <?= strtoupper(pathinfo($a['original_name'], PATHINFO_EXTENSION)) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= format_file_size((int)$a['file_size']) ?></td>
                                <td class="small"><?= e($a['uploader']) ?></td>
                                <td class="small text-muted"><?= format_date($a['uploaded_at']) ?></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="/adhub/uploads/<?= e($a['filename']) ?>"
                                           target="_blank"
                                           class="action-btn view" title="View file">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="/adhub/uploads/<?= e($a['filename']) ?>"
                                           download="<?= e($a['original_name']) ?>"
                                           class="action-btn download" title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <a href="#" class="action-btn delete"
                                           data-confirm-delete
                                           data-action="/adhub/admin/assets.php?action=delete&id=<?= $a['id'] ?>"
                                           data-label="<?= e($a['original_name']) ?>"
                                           title="Delete">
                                            <i class="bi bi-trash3"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <?= render_toast_container() ?>
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div>

<!-- ── Upload Modal ───────────────────────────────────────────────────────── -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="" enctype="multipart/form-data"
                  id="uploadForm" class="needs-validation" novalidate>
                <?= csrf_field() ?>

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-cloud-upload me-2 text-primary"></i>Upload Asset
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="adhub-label" for="upload_campaign">Campaign *</label>
                        <select id="upload_campaign" name="campaign_id" class="form-select" required>
                            <option value="">— Select a campaign —</option>
                            <?php foreach ($campaigns as $camp): ?>
                            <option value="<?= $camp['id'] ?>"><?= e($camp['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- FIXED upload zone: input is absolute overlay, zone handles visual -->
                    <div class="upload-zone" id="uploadZone">
                        <input type="file" name="asset_file" id="assetFileInput" required
                               accept="image/*,.pdf,.mp4,.mov,.zip,.doc,.docx">
                        <i class="bi bi-cloud-arrow-up upload-zone-icon"></i>
                        <div class="upload-zone-label">Click to browse or drag & drop</div>
                        <div class="upload-zone-hint">
                            Images, PDF, Video, ZIP, Word — max 10 MB
                        </div>
                    </div>

                    <!-- Upload progress bar (shown after submit) -->
                    <div id="uploadProgress" class="mt-3" style="display:none;">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">Uploading…</span>
                            <span id="uploadPct">0%</span>
                        </div>
                        <div class="adhub-progress">
                            <div class="adhub-progress-bar" id="uploadBar" style="width:0%;transition:width .2s;"></div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="bi bi-x me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-adhub-primary" id="uploadBtn">
                        <i class="bi bi-upload me-1"></i>Upload File
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= render_confirm_delete_modal() ?>

<script>
// Simulate upload progress for UX feedback
document.getElementById('uploadForm')?.addEventListener('submit', function(e) {
    const fileInput = document.getElementById('assetFileInput');
    if (!fileInput.files.length) return;

    const progress = document.getElementById('uploadProgress');
    const bar      = document.getElementById('uploadBar');
    const pct      = document.getElementById('uploadPct');
    const btn      = document.getElementById('uploadBtn');

    progress.style.display = 'block';
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Uploading…';

    // Animate to 90% while form submits (real progress would need XHR)
    let current = 0;
    const interval = setInterval(() => {
        current = Math.min(current + Math.random() * 15, 90);
        bar.style.width = current + '%';
        pct.textContent = Math.round(current) + '%';
    }, 150);

    // Form will actually submit and redirect
    setTimeout(() => clearInterval(interval), 3000);
});
</script>
