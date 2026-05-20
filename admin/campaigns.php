<?php
/**
 * AdHub - Admin Campaign Management (v2)
 * Fixed: action button layout, modal population, delete confirm.
 */

$page_title   = 'Campaigns';
$require_auth = true;
$require_role = 'admin';

require_once __DIR__ . '/../includes/header.php';

$msg      = '';
$msg_type = 'success';
$clients  = get_clients($pdo);

// ── Handle POST ───────────────────────────────────────────────────────────────
if (is_post()) {
    $data = [
        'title'       => input_str($_POST, 'title'),
        'description' => input_str($_POST, 'description'),
        'client_id'   => input_int($_POST, 'client_id'),
        'status'      => input_str($_POST, 'status', 'draft'),
        'budget'      => input_float($_POST, 'budget'),
        'progress'    => input_int($_POST, 'progress'),
        'start_date'  => input_str($_POST, 'start_date'),
        'end_date'    => input_str($_POST, 'end_date'),
        'created_by'  => $user_id,
    ];
    $edit_id    = input_int($_POST, 'edit_id');
    $validation = validate_campaign_data($data);

    if (!$validation['valid']) {
        $msg = implode(' ', $validation['errors']); $msg_type = 'danger';
    } else {
        if ($edit_id > 0) {
            update_campaign($pdo, $edit_id, $data);
            $msg = 'Campaign updated successfully.';
        } else {
            create_campaign($pdo, $data);
            $msg = 'Campaign created successfully.';
        }
    }
}

// ── Handle delete ─────────────────────────────────────────────────────────────
if (query_param('action') === 'delete') {
    $del_id = input_int($_GET, 'id');
    if ($del_id > 0) {
        delete_campaign($pdo, $del_id, __DIR__ . '/../uploads/');
        $msg = 'Campaign deleted.';
    }
}

// ── Fetch ─────────────────────────────────────────────────────────────────────
$filter_status = query_param('status', 'all');
$all_campaigns = get_campaigns($pdo, $filter_status !== 'all' ? $filter_status : null);

$status_filters = [
    'all'       => 'All',
    'active'    => 'Active',
    'draft'     => 'Draft',
    'review'    => 'In Review',
    'completed' => 'Completed',
    'paused'    => 'Paused',
];
?>

<?= render_sidebar_overlay() ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="adhub-content-wrapper" id="contentWrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="adhub-main">

        <?= render_page_header(
            'Campaigns',
            'Manage all marketing campaigns across clients.',
            '<button class="btn btn-adhub-primary" data-bs-toggle="modal" data-bs-target="#campaignModal">
                <i class="bi bi-plus-lg me-1"></i> New Campaign
             </button>'
        ) ?>

        <?php if ($msg): ?>
            <?= render_alert($msg, $msg_type) ?>
        <?php endif; ?>

        <!-- Filter + search bar -->
        <div class="d-flex gap-2 flex-wrap align-items-center mb-3">
            <?php foreach ($status_filters as $val => $label): ?>
            <a href="?status=<?= $val ?>"
               class="btn btn-sm <?= $filter_status === $val ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <?= $label ?>
                <?php if ($val !== 'all'): ?>
                <span class="badge ms-1 <?= $filter_status === $val ? 'bg-white text-primary' : 'bg-secondary' ?>">
                    <?= count(array_filter($all_campaigns, fn($c) => $c['status'] === $val)) ?>
                </span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
            <div class="ms-auto">
                <div class="input-group input-group-sm" style="width:230px;">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control"
                           placeholder="Filter table…"
                           data-filter-table="#campaignsTable">
                </div>
            </div>
        </div>

        <!-- Campaigns table -->
        <div class="adhub-card">
            <div class="card-body p-0">
                <?php if (empty($all_campaigns)): ?>
                    <?= render_empty_state('megaphone', 'No campaigns found. Create one now.') ?>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="adhub-table" id="campaignsTable">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>Title</th>
                                <th>Client</th>
                                <th>Status</th>
                                <th style="min-width:130px;">Progress</th>
                                <th>Budget</th>
                                <th>Dates</th>
                                <th style="width:110px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_campaigns as $i => $c): ?>
                            <tr>
                                <td class="text-muted small"><?= $i + 1 ?></td>
                                <td>
                                    <div class="fw-600"><?= e($c['title']) ?></div>
                                    <?php if ($c['description']): ?>
                                    <div class="text-muted small"
                                         style="font-size:.75rem;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        <?= e($c['description']) ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="adhub-avatar" style="width:26px;height:26px;font-size:.65rem;">
                                            <?= avatar_initial($c['client_name']) ?>
                                        </div>
                                        <span class="small"><?= e($c['client_name']) ?></span>
                                    </div>
                                </td>
                                <td><?= render_badge($c['status']) ?></td>
                                <td><?= render_progress_bar((int)$c['progress']) ?></td>
                                <td class="fw-600 small"><?= format_currency((float)$c['budget'], '$', 0) ?></td>
                                <td class="small text-muted">
                                    <?= format_date($c['start_date'], 'M d') ?>
                                    <?= $c['end_date'] ? ' – ' . format_date($c['end_date'], 'M d, Y') : '' ?>
                                </td>
                                <td>
                                    <!-- FIXED: clean action button group -->
                                    <div class="action-btns">
                                        <button class="action-btn edit"
                                                title="Edit campaign"
                                                data-bs-toggle="modal"
                                                data-bs-target="#campaignModal"
                                                data-id="<?= $c['id'] ?>"
                                                data-title="<?= e($c['title']) ?>"
                                                data-description="<?= e($c['description']) ?>"
                                                data-client="<?= $c['client_id'] ?>"
                                                data-status="<?= $c['status'] ?>"
                                                data-budget="<?= $c['budget'] ?>"
                                                data-progress="<?= $c['progress'] ?>"
                                                data-start="<?= $c['start_date'] ?>"
                                                data-end="<?= $c['end_date'] ?>">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="#" class="action-btn delete"
                                           title="Delete campaign"
                                           data-confirm-delete
                                           data-action="/adhub/admin/campaigns.php?action=delete&id=<?= $c['id'] ?>"
                                           data-label="<?= e($c['title']) ?>">
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

        <!-- Campaign count summary -->
        <?php if (!empty($all_campaigns)): ?>
        <div class="text-muted small mt-2 text-end">
            Showing <?= count($all_campaigns) ?> campaign<?= count($all_campaigns) !== 1 ? 's' : '' ?>
        </div>
        <?php endif; ?>

    </main>

    <?= render_toast_container() ?>
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div>

<!-- ── Campaign Modal ─────────────────────────────────────────────────────── -->
<div class="modal fade" id="campaignModal" tabindex="-1"
     aria-labelledby="campaignModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="edit_id" id="modalEditId" value="0">

                <div class="modal-header">
                    <h5 class="modal-title" id="campaignModalLabel">
                        <i class="bi bi-megaphone me-2 text-primary"></i>New Campaign
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="adhub-label" for="modal_title">Campaign Title *</label>
                            <input type="text" id="modal_title" name="title"
                                   class="form-control" required
                                   placeholder="e.g. Q3 Product Launch">
                        </div>
                        <div class="col-md-6">
                            <label class="adhub-label" for="modal_client">Client *</label>
                            <select id="modal_client" name="client_id" class="form-select" required>
                                <?= render_client_options($clients) ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="adhub-label" for="modal_status">Status</label>
                            <select id="modal_status" name="status" class="form-select">
                                <?= render_status_options() ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="adhub-label" for="modal_desc">Description</label>
                            <textarea id="modal_desc" name="description"
                                      class="form-control" rows="3"
                                      placeholder="Brief campaign overview…"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="adhub-label" for="modal_budget">Budget ($)</label>
                            <input type="number" id="modal_budget" name="budget"
                                   class="form-control" min="0" step="100" placeholder="5000">
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
                            <label class="adhub-label">
                                Progress — <span id="progressValDisplay">0%</span>
                            </label>
                            <input type="range" id="modal_progress" name="progress"
                                   class="form-range" min="0" max="100" value="0"
                                   data-label="progressValDisplay">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="bi bi-x me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-adhub-primary">
                        <i class="bi bi-save me-1"></i>Save Campaign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= render_confirm_delete_modal() ?>

<script>
// Populate modal on edit
document.getElementById('campaignModal').addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;

    if (!btn || !btn.dataset.id) {
        // New mode — reset
        this.querySelector('#campaignModalLabel').innerHTML =
            '<i class="bi bi-megaphone me-2 text-primary"></i>New Campaign';
        this.querySelector('#modalEditId').value = '0';
        this.querySelector('form').reset();
        document.getElementById('progressValDisplay').textContent = '0%';
        return;
    }

    // Edit mode
    this.querySelector('#campaignModalLabel').innerHTML =
        '<i class="bi bi-pencil-square me-2 text-warning"></i>Edit Campaign';
    this.querySelector('#modalEditId').value    = btn.dataset.id;
    this.querySelector('#modal_title').value    = btn.dataset.title    || '';
    this.querySelector('#modal_desc').value     = btn.dataset.description || '';
    this.querySelector('#modal_budget').value   = btn.dataset.budget   || '';
    this.querySelector('#modal_start').value    = btn.dataset.start    || '';
    this.querySelector('#modal_end').value      = btn.dataset.end      || '';
    this.querySelector('#modal_progress').value = btn.dataset.progress || 0;
    document.getElementById('progressValDisplay').textContent = (btn.dataset.progress || 0) + '%';

    // Client select
    [...this.querySelector('#modal_client').options].forEach(o => {
        o.selected = (o.value == btn.dataset.client);
    });
    // Status select
    [...this.querySelector('#modal_status').options].forEach(o => {
        o.selected = (o.value === btn.dataset.status);
    });
});
</script>
