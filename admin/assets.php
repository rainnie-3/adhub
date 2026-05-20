<?php
/**
 * AdHub - Admin Asset Management
 * Upload and manage campaign assets (images, PDFs, videos, etc.)
 */

$page_title   = 'Assets';
$require_auth = true;
$require_role = 'admin';

require_once __DIR__ . '/../includes/header.php';

$msg      = '';
$msg_type = 'success';

// Allowed file types and max size
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp',
                  'application/pdf', 'video/mp4', 'video/quicktime',
                  'application/zip', 'application/msword',
                  'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

// Fetch campaigns for dropdown
$campaigns = $pdo->query("SELECT id, title FROM campaigns ORDER BY title")->fetchAll();

// ── Handle upload ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['asset_file'])) {
    $campaign_id = intval($_POST['campaign_id'] ?? 0);
    $file        = $_FILES['asset_file'];

    if ($campaign_id < 1) {
        $msg      = 'Please select a campaign.';
        $msg_type = 'danger';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $msg      = 'Upload error. Please try again.';
        $msg_type = 'danger';
    } elseif ($file['size'] > MAX_FILE_SIZE) {
        $msg      = 'File too large. Maximum size is 10MB.';
        $msg_type = 'danger';
    } elseif (!in_array($file['type'], $allowed_types)) {
        $msg      = 'File type not allowed.';
        $msg_type = 'danger';
    } else {
        // Generate safe filename
        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safe     = uniqid('asset_', true) . '.' . strtolower($ext);
        $dest     = __DIR__ . '/../uploads/' . $safe;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $stmt = $pdo->prepare(
                "INSERT INTO campaign_assets (campaign_id, filename, original_name, file_type, file_size, uploaded_by)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$campaign_id, $safe, $file['name'], $file['type'], $file['size'], $user_id]);
            $msg = 'Asset uploaded successfully.';
        } else {
            $msg      = 'Failed to save file. Check upload directory permissions.';
            $msg_type = 'danger';
        }
    }
}

// ── Handle delete ─────────────────────────────────────────────────────────────
if (($_GET['action'] ?? '') === 'delete' && ($del_id = intval($_GET['id'] ?? 0)) > 0) {
    $row = $pdo->prepare("SELECT filename FROM campaign_assets WHERE id = ?");
    $row->execute([$del_id]);
    $row = $row->fetch();
    if ($row) {
        @unlink(__DIR__ . '/../uploads/' . $row['filename']);
        $pdo->prepare("DELETE FROM campaign_assets WHERE id = ?")->execute([$del_id]);
        $msg = 'Asset deleted.';
    }
}

// ── Fetch all assets ──────────────────────────────────────────────────────────
$assets = $pdo->query(
    "SELECT ca.*, c.title AS campaign_title, u.name AS uploader
     FROM campaign_assets ca
     JOIN campaigns c ON ca.campaign_id = c.id
     JOIN users u ON ca.uploaded_by = u.id
     ORDER BY ca.uploaded_at DESC"
)->fetchAll();

// Helper: human-readable file size
function human_size(int $bytes): string {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}

// Helper: icon for file type
function file_icon(string $mime): string {
    if (str_starts_with($mime, 'image/'))       return 'bi-file-image text-success';
    if ($mime === 'application/pdf')            return 'bi-file-pdf text-danger';
    if (str_starts_with($mime, 'video/'))       return 'bi-file-play text-info';
    if (str_contains($mime, 'word'))            return 'bi-file-word text-primary';
    return 'bi-file-earmark text-secondary';
}
?>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="adhub-content-wrapper" id="contentWrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="adhub-main">

        <div class="page-header">
            <div>
                <h1 class="page-title">Asset Manager</h1>
                <p class="page-subtitle">Upload and manage campaign files.</p>
            </div>
            <button class="btn btn-adhub-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="bi bi-cloud-upload me-1"></i> Upload Asset
            </button>
        </div>

        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible alert-auto-dismiss d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-<?= $msg_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill"></i>
            <div><?= htmlspecialchars($msg) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Assets grid / table -->
        <div class="adhub-card">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-folder2-open me-2 text-warning"></i>Uploaded Assets</h5>
                <span class="badge bg-secondary"><?= count($assets) ?> files</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($assets)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-folder-x fs-1 d-block mb-3 opacity-25"></i>
                    <p>No assets uploaded yet.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="adhub-table">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Campaign</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Uploaded By</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assets as $a): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi <?= file_icon($a['file_type']) ?> fs-5"></i>
                                        <span class="fw-600 small"><?= htmlspecialchars($a['original_name']) ?></span>
                                    </div>
                                </td>
                                <td class="small"><?= htmlspecialchars($a['campaign_title']) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border" style="font-size:.7rem;">
                                        <?= htmlspecialchars($a['file_type']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= human_size((int)$a['file_size']) ?></td>
                                <td class="small"><?= htmlspecialchars($a['uploader']) ?></td>
                                <td class="small text-muted"><?= date('M d, Y', strtotime($a['uploaded_at'])) ?></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="/adhub/uploads/<?= htmlspecialchars($a['filename']) ?>" target="_blank"
                                           class="btn btn-sm btn-outline-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="/adhub/uploads/<?= htmlspecialchars($a['filename']) ?>" download="<?= htmlspecialchars($a['original_name']) ?>"
                                           class="btn btn-sm btn-outline-secondary" title="Download">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <a href="?action=delete&id=<?= $a['id'] ?>"
                                           class="btn btn-sm btn-outline-danger"
                                           data-confirm-delete
                                           data-action="/adhub/admin/assets.php?action=delete&id=<?= $a['id'] ?>"
                                           data-label="<?= htmlspecialchars($a['original_name'], ENT_QUOTES) ?>"
                                           title="Delete">
                                            <i class="bi bi-trash"></i>
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

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div><!-- /.adhub-content-wrapper --></div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:var(--ah-radius);">
            <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-700">Upload Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="adhub-label" for="upload_campaign">Campaign *</label>
                        <select id="upload_campaign" name="campaign_id" class="form-select" required>
                            <option value="">— Select campaign —</option>
                            <?php foreach ($campaigns as $camp): ?>
                            <option value="<?= $camp['id'] ?>"><?= htmlspecialchars($camp['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Drag-and-drop upload zone -->
                    <div class="upload-zone">
                        <input type="file" name="asset_file" class="d-none" id="assetFileInput" required
                               accept="image/*,.pdf,.mp4,.mov,.zip,.doc,.docx">
                        <i class="bi bi-cloud-arrow-up d-block mb-2"></i>
                        <div class="fw-600 mb-1 upload-label">Drag & drop or click to browse</div>
                        <div class="text-muted small">Images, PDF, Video, ZIP, Word — max 10MB</div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-adhub-primary">
                        <i class="bi bi-upload me-1"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirm delete modal -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:var(--ah-radius);">
            <div class="modal-header border-0">
                <h6 class="modal-title" id="confirmDeleteLabel">Delete item?</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0 text-muted small">This action cannot be undone.</div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>
