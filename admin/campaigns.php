<?php
/**
 * AdHub - Admin Campaign Management
 * List, create, edit, and delete campaigns.
 */

$page_title   = 'Campaigns';
$require_auth = true;
$require_role = 'admin';

require_once __DIR__ . '/../includes/header.php';

$action     = $_GET['action'] ?? 'list';
$campaign_id = intval($_GET['id'] ?? 0);
$msg        = '';
$msg_type   = 'success';

// Fetch all clients for dropdown
$clients = $pdo->query("SELECT id, name, company FROM users WHERE role = 'client' ORDER BY name")->fetchAll();

// ── Handle POST (create / update) ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $client_id   = intval($_POST['client_id'] ?? 0);
    $status      = $_POST['status']           ?? 'draft';
    $budget      = floatval($_POST['budget']  ?? 0);
    $progress    = intval($_POST['progress']  ?? 0);
    $start_date  = $_POST['start_date']       ?? null;
    $end_date    = $_POST['end_date']         ?? null;
    $edit_id     = intval($_POST['edit_id']   ?? 0);

    if (empty($title) || $client_id < 1) {
        $msg      = 'Title and client are required.';
        $msg_type = 'danger';
    } else {
        if ($edit_id > 0) {
            // Update existing
            $stmt = $pdo->prepare(
                "UPDATE campaigns SET title=?, description=?, client_id=?, status=?, budget=?, progress=?, start_date=?, end_date=?, updated_at=NOW()
                 WHERE id=?"
            );
            $stmt->execute([$title, $description, $client_id, $status, $budget, $progress, $start_date ?: null, $end_date ?: null, $edit_id]);
            $msg = 'Campaign updated successfully.';
        } else {
            // Insert new
            $stmt = $pdo->prepare(
                "INSERT INTO campaigns (title, description, client_id, status, budget, progress, start_date, end_date, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$title, $description, $client_id, $status, $budget, $progress, $start_date ?: null, $end_date ?: null, $user_id]);
            $msg = 'Campaign created successfully.';
        }
        $action = 'list'; // Return to list after save
    }
}

// ── Handle delete ─────────────────────────────────────────────────────────────
if ($action === 'delete' && $campaign_id > 0) {
    $pdo->prepare("DELETE FROM campaigns WHERE id = ?")->execute([$campaign_id]);
    $msg    = 'Campaign deleted.';
    $action = 'list';
}

// ── Fetch campaign for editing ────────────────────────────────────────────────
$edit_campaign = null;
if ($action === 'edit' && $campaign_id > 0) {
    $edit_campaign = $pdo->prepare("SELECT * FROM campaigns WHERE id = ?");
    $edit_campaign->execute([$campaign_id]);
    $edit_campaign = $edit_campaign->fetch();
}

// ── Fetch all campaigns (with filter) ────────────────────────────────────────
$filter_status = $_GET['status'] ?? 'all';
$where         = $filter_status !== 'all' ? "WHERE c.status = " . $pdo->quote($filter_status) : '';

$all_campaigns = $pdo->query(
    "SELECT c.*, u.name AS client_name
     FROM campaigns c
     JOIN users u ON c.client_id = u.id
     $where
     ORDER BY c.created_at DESC"
)->fetchAll();

$status_options = ['draft', 'active', 'review', 'completed', 'paused'];
$badge_map = [
    'active'    => 'badge-active',
    'draft'     => 'badge-draft',
    'review'    => 'badge-review',
    'completed' => 'badge-completed',
    'paused'    => 'badge-paused',
];
?>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="adhub-content-wrapper" id="contentWrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="adhub-main">

        <!-- Page header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Campaigns</h1>
                <p class="page-subtitle">Manage all marketing campaigns across clients.</p>
            </div>
            <button class="btn btn-adhub-primary" data-bs-toggle="modal" data-bs-target="#campaignModal">
                <i class="bi bi-plus-lg me-1"></i> New Campaign
            </button>
        </div>

        <!-- Flash message -->
        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible alert-auto-dismiss d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi bi-<?= $msg_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill"></i>
            <div><?= htmlspecialchars($msg) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Status filter tabs -->
        <div class="mb-3 d-flex gap-2 flex-wrap">
            <?php
            $all_filters = ['all' => 'All', 'active' => 'Active', 'draft' => 'Draft', 'review' => 'In Review', 'completed' => 'Completed', 'paused' => 'Paused'];
            foreach ($all_filters as $val => $label):
                $active_tab = $filter_status === $val ? 'btn-primary' : 'btn-outline-secondary';
            ?>
            <a href="?status=<?= $val ?>" class="btn btn-sm <?= $active_tab ?>"><?= $label ?></a>
            <?php endforeach; ?>

            <!-- Search -->
            <div class="ms-auto">
                <input type="text" class="form-control form-control-sm" placeholder="Search campaigns…"
                       data-filter-table="#campaignsTable" style="width:220px;">
            </div>
        </div>

        <!-- Campaigns table -->
        <div class="adhub-card">
            <div class="card-body p-0">
                <?php if (empty($all_campaigns)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-megaphone fs-1 d-block mb-3 opacity-25"></i>
                    <p>No campaigns found. <button class="btn btn-link p-0" data-bs-toggle="modal" data-bs-target="#campaignModal">Create one now.</button></p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="adhub-table" id="campaignsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Client</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Budget</th>
                                <th>Start Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_campaigns as $i => $c): ?>
                            <tr>
                                <td class="text-muted small"><?= $i + 1 ?></td>
                                <td>
                                    <div class="fw-600"><?= htmlspecialchars($c['title']) ?></div>
                                    <?php if ($c['description']): ?>
                                    <div class="text-muted small text-truncate" style="max-width:200px;"><?= htmlspecialchars($c['description']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($c['client_name']) ?></td>
                                <td>
                                    <span class="adhub-badge <?= $badge_map[$c['status']] ?? 'badge-draft' ?>">
                                        <?= ucfirst($c['status']) ?>
                                    </span>
                                </td>
                                <td style="min-width:110px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="adhub-progress flex-grow-1">
                                            <div class="adhub-progress-bar" data-progress="<?= $c['progress'] ?>"></div>
                                        </div>
                                        <span class="small text-muted"><?= $c['progress'] ?>%</span>
                                    </div>
                                </td>
                                <td class="fw-600">$<?= number_format($c['budget'], 2) ?></td>
                                <td class="text-muted small"><?= $c['start_date'] ? date('M d, Y', strtotime($c['start_date'])) : '—' ?></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <!-- Edit -->
                                        <button class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal" data-bs-target="#campaignModal"
                                                data-id="<?= $c['id'] ?>"
                                                data-title="<?= htmlspecialchars($c['title'], ENT_QUOTES) ?>"
                                                data-description="<?= htmlspecialchars($c['description'], ENT_QUOTES) ?>"
                                                data-client="<?= $c['client_id'] ?>"
                                                data-status="<?= $c['status'] ?>"
                                                data-budget="<?= $c['budget'] ?>"
                                                data-progress="<?= $c['progress'] ?>"
                                                data-start="<?= $c['start_date'] ?>"
                                                data-end="<?= $c['end_date'] ?>"
                                                title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <!-- Delete -->
                                        <a href="?action=delete&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger"
                                           data-confirm-delete
                                           data-action="/adhub/admin/campaigns.php?action=delete&id=<?= $c['id'] ?>"
                                           data-label="<?= htmlspecialchars($c['title'], ENT_QUOTES) ?>"
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

<!-- ── Campaign Create / Edit Modal ─────────────────────────────────────────── -->
<div class="modal fade" id="campaignModal" tabindex="-1" aria-labelledby="campaignModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:var(--ah-radius);">
            <form method="POST" action="" class="needs-validation" novalidate>
                <input type="hidden" name="edit_id" id="modalEditId" value="0">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-700" id="campaignModalLabel">New Campaign</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="adhub-label" for="modal_title">Campaign Title *</label>
                            <input type="text" id="modal_title" name="title" class="form-control" required placeholder="e.g. Q3 Product Launch">
                        </div>

                        <div class="col-md-6">
                            <label class="adhub-label" for="modal_client">Client *</label>
                            <select id="modal_client" name="client_id" class="form-select" required>
                                <option value="">— Select client —</option>
                                <?php foreach ($clients as $cl): ?>
                                <option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['name']) ?>
                                    <?= $cl['company'] ? '(' . htmlspecialchars($cl['company']) . ')' : '' ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="adhub-label" for="modal_status">Status</label>
                            <select id="modal_status" name="status" class="form-select">
                                <?php foreach ($status_options as $s): ?>
                                <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="adhub-label" for="modal_desc">Description</label>
                            <textarea id="modal_desc" name="description" class="form-control" rows="3" placeholder="Brief campaign overview…"></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="adhub-label" for="modal_budget">Budget ($)</label>
                            <input type="number" id="modal_budget" name="budget" class="form-control" min="0" step="0.01" placeholder="5000.00">
                        </div>

                        <div class="col-md-4">
                            <label class="adhub-label" for="modal_start">Start Date</label>
                            <input type="date" id="modal_start" name="start_date" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="adhub-label" for="modal_end">End Date</label>
                            <input type="date" id="modal_end" name="end_date" class="form-control">
                        </div>

                        <div class="col-12">
                            <label class="adhub-label" for="modal_progress">Progress (<?= 0 ?>%)</label>
                            <input type="range" id="modal_progress" name="progress" class="form-range" min="0" max="100" value="0"
                                   oninput="document.getElementById('progressVal').textContent = this.value + '%'">
                            <div class="text-muted small" id="progressVal">0%</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-adhub-primary">
                        <i class="bi bi-save me-1"></i> Save Campaign
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

<script>
// Populate modal with data when editing
document.getElementById('campaignModal').addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    if (!btn || !btn.dataset.id) {
        // New campaign — reset form
        this.querySelector('#campaignModalLabel').textContent = 'New Campaign';
        this.querySelector('#modalEditId').value = '0';
        this.querySelector('form').reset();
        return;
    }

    // Edit mode
    this.querySelector('#campaignModalLabel').textContent = 'Edit Campaign';
    this.querySelector('#modalEditId').value   = btn.dataset.id;
    this.querySelector('#modal_title').value   = btn.dataset.title;
    this.querySelector('#modal_desc').value    = btn.dataset.description;
    this.querySelector('#modal_budget').value  = btn.dataset.budget;
    this.querySelector('#modal_start').value   = btn.dataset.start;
    this.querySelector('#modal_end').value     = btn.dataset.end;
    this.querySelector('#modal_progress').value = btn.dataset.progress;
    document.getElementById('progressVal').textContent = btn.dataset.progress + '%';

    // Set client select
    const clientSel = this.querySelector('#modal_client');
    [...clientSel.options].forEach(o => o.selected = (o.value == btn.dataset.client));

    // Set status select
    const statusSel = this.querySelector('#modal_status');
    [...statusSel.options].forEach(o => o.selected = (o.value === btn.dataset.status));
});
</script>
