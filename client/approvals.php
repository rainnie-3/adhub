<?php
/**
 * AdHub - Client Approvals Page
 * Uses: get_client_approvals(), respond_to_approval(), group_approvals_by_status(),
 *       count_pending_approvals(), get_assets_by_campaign(), render_badge(),
 *       render_progress_bar(), render_alert(), render_page_header(),
 *       render_empty_state(), render_stat_card(), render_sidebar_overlay(),
 *       render_toast_container(), approval_icon(), format_date(),
 *       input_int(), input_str(), is_post(), e()
 */

$page_title   = 'Approvals';
$require_auth = true;
$require_role = 'client';

require_once __DIR__ . '/../includes/header.php';

$msg      = '';
$msg_type = 'success';

// ── Handle approval response ──────────────────────────────────────────────────
if (is_post()) {
    $approval_id = input_int($_POST, 'approval_id');
    $action_val  = input_str($_POST, 'approval_action');
    $notes       = input_str($_POST, 'notes');

    $result   = respond_to_approval($pdo, $approval_id, $user_id, $action_val, $notes);
    $msg      = $result['message'];
    $msg_type = $result['success']
        ? ($action_val === 'approved' ? 'success' : 'warning')
        : 'danger';
}

// ── Fetch all approvals + group by status ─────────────────────────────────────
$approvals = get_client_approvals($pdo, $user_id);
$grouped   = group_approvals_by_status($approvals);
$counts    = [
    'pending'  => count($grouped['pending']),
    'approved' => count($grouped['approved']),
    'revision' => count($grouped['revision']),
];

// ── Pre-load assets for each campaign ─────────────────────────────────────────
$assets_map = [];
if (!empty($approvals)) {
    $camp_ids = array_unique(array_column($approvals, 'campaign_id'));
    foreach ($camp_ids as $cid) {
        $assets_map[$cid] = get_assets_by_campaign($pdo, (int)$cid);
    }
}

// Section config (order: pending first)
$sections = [
    'pending'  => ['label' => 'Awaiting Your Review',  'color' => 'warning'],
    'revision' => ['label' => 'Revision Requested',    'color' => 'danger'],
    'approved' => ['label' => 'Approved',               'color' => 'success'],
];
?>

<?= render_sidebar_overlay() ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="adhub-content-wrapper" id="contentWrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="adhub-main">

        <?= render_page_header(
            'Approvals',
            'Review campaign materials and provide your feedback.'
        ) ?>

        <?php if ($msg): ?>
            <?= render_alert($msg, $msg_type) ?>
        <?php endif; ?>

        <!-- Status summary cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <?= render_stat_card('bi-hourglass-split', 'amber',
                    (string)$counts['pending'], 'Pending Review') ?>
            </div>
            <div class="col-sm-4">
                <?= render_stat_card('bi-check-circle', 'green',
                    (string)$counts['approved'], 'Approved') ?>
            </div>
            <div class="col-sm-4">
                <?= render_stat_card('bi-arrow-repeat', 'red',
                    (string)$counts['revision'], 'Revisions Requested') ?>
            </div>
        </div>

        <?php if (empty($approvals)): ?>
        <div class="adhub-card">
            <div class="card-body">
                <?= render_empty_state(
                    'patch-check',
                    'No approval requests yet. Your agency will notify you when materials are ready for review.'
                ) ?>
            </div>
        </div>

        <?php else: ?>

        <?php foreach ($sections as $status => $section):
            if (empty($grouped[$status])) continue;
        ?>

        <!-- Section: <?= $section['label'] ?> -->
        <div class="mb-4">
            <h6 class="fw-700 mb-3 d-flex align-items-center gap-2"
                style="font-family:var(--font-heading);">
                <span class="badge bg-<?= $section['color'] ?>">
                    <?= $counts[$status] ?>
                </span>
                <?= $section['label'] ?>
            </h6>

            <div class="row g-3">
                <?php foreach ($grouped[$status] as $a): ?>
                <div class="col-lg-6">
                    <div class="adhub-card h-100 <?= $status === 'pending' ? 'approval-pending-card' : '' ?>">
                        <div class="card-body">

                            <!-- Header: title + badge -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 class="fw-700 mb-1" style="font-family:var(--font-heading);">
                                        <?= e($a['campaign_title']) ?>
                                    </h6>
                                    <div class="text-muted small">
                                        Submitted <?= format_date($a['created_at']) ?>
                                    </div>
                                </div>
                                <?= render_badge($a['status']) ?>
                            </div>

                            <!-- Campaign description -->
                            <?php if ($a['description']): ?>
                            <p class="text-muted small mb-3">
                                <?= e(truncate_str($a['description'], 100)) ?>
                            </p>
                            <?php endif; ?>

                            <!-- Progress bar -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Campaign Progress</span>
                                    <span class="fw-600"><?= $a['progress'] ?>%</span>
                                </div>
                                <?= render_progress_bar((int)$a['progress']) ?>
                            </div>

                            <!-- Notes (non-pending) -->
                            <?php if ($a['notes'] && $a['status'] !== 'pending'): ?>
                            <div class="rounded-2 p-3 mb-3" style="background:var(--ah-bg);font-size:.8rem;">
                                <div class="fw-600 mb-1 text-muted">Your notes:</div>
                                <?= e($a['notes']) ?>
                            </div>
                            <?php endif; ?>

                            <!-- Reviewed timestamp -->
                            <?php if ($a['reviewed_at']): ?>
                            <div class="text-muted small mb-3">
                                <i class="bi bi-clock-history me-1"></i>
                                Reviewed on <?= format_date($a['reviewed_at'], 'M d, Y \a\t g:i A') ?>
                            </div>
                            <?php endif; ?>

                            <!-- Attached files -->
                            <?php if (!empty($assets_map[$a['campaign_id']])): ?>
                            <div class="mb-3">
                                <div class="text-muted small fw-600 mb-2">
                                    <i class="bi bi-paperclip me-1"></i>Attached Files:
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($assets_map[$a['campaign_id']] as $asset): ?>
                                    <a href="/adhub/uploads/<?= e($asset['filename']) ?>"
                                       target="_blank"
                                       class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                                       title="<?= e($asset['original_name']) ?>">
                                        <i class="bi <?= file_type_icon($asset['file_type']) ?>"></i>
                                        <span class="text-truncate" style="max-width:120px;font-size:.75rem;">
                                            <?= e($asset['original_name']) ?>
                                        </span>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Action buttons (pending only) -->
                            <?php if ($a['status'] === 'pending'): ?>
                            <div class="d-flex gap-2 pt-2 border-top">
                                <button class="btn btn-success btn-sm flex-grow-1"
                                        data-bs-toggle="modal" data-bs-target="#approvalModal"
                                        data-approval-id="<?= $a['id'] ?>"
                                        data-action="approved"
                                        data-campaign="<?= e($a['campaign_title']) ?>">
                                    <i class="bi bi-check-lg me-1"></i> Approve
                                </button>
                                <button class="btn btn-outline-danger btn-sm flex-grow-1"
                                        data-bs-toggle="modal" data-bs-target="#approvalModal"
                                        data-approval-id="<?= $a['id'] ?>"
                                        data-action="revision"
                                        data-campaign="<?= e($a['campaign_title']) ?>">
                                    <i class="bi bi-pencil me-1"></i> Request Revision
                                </button>
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php endforeach; ?>
        <?php endif; ?>

    </main>

    <?= render_toast_container() ?>
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div><!-- /.adhub-content-wrapper -->

<!-- Approval action modal -->
<div class="modal fade" id="approvalModal" tabindex="-1"
     aria-labelledby="approvalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:var(--ah-radius);">
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="approval_id"     id="modal_approval_id">
                <input type="hidden" name="approval_action" id="modal_approval_action">

                <div class="modal-header border-0">
                    <h5 class="modal-title" id="approvalModalLabel">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p class="mb-1 text-muted small">Campaign:</p>
                    <p class="fw-700 mb-3" id="modal_campaign_name"></p>

                    <div id="notesGroup">
                        <label class="adhub-label" for="modal_notes">
                            <span id="notesLabel">Notes / Feedback</span>
                        </label>
                        <textarea id="modal_notes" name="notes" class="form-control" rows="4"
                                  placeholder="Describe what changes you need…"></textarea>
                        <div class="form-text" id="notesHint"></div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" id="modal_submit_btn">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.approval-pending-card { border-left: 3px solid var(--ah-warning) !important; }
</style>

<script>
// Populate approval modal based on action type
document.getElementById('approvalModal').addEventListener('show.bs.modal', function (e) {
    const btn        = e.relatedTarget;
    const approvalId = btn.dataset.approvalId;
    const action     = btn.dataset.action;
    const campaign   = btn.dataset.campaign;

    this.querySelector('#modal_approval_id').value     = approvalId;
    this.querySelector('#modal_approval_action').value = action;
    this.querySelector('#modal_campaign_name').textContent = campaign;
    this.querySelector('#modal_notes').value = '';

    const submitBtn  = this.querySelector('#modal_submit_btn');
    const notesLabel = this.querySelector('#notesLabel');
    const notesHint  = this.querySelector('#notesHint');
    const notesInput = this.querySelector('#modal_notes');

    if (action === 'approved') {
        submitBtn.className        = 'btn btn-success';
        submitBtn.innerHTML        = '<i class="bi bi-check-lg me-1"></i> Approve Campaign';
        notesLabel.textContent     = 'Approval Notes (optional)';
        notesHint.textContent      = 'You can optionally leave a positive note for the team.';
        notesInput.placeholder     = 'e.g. Looks great, approved!';
        notesInput.removeAttribute('required');
    } else {
        submitBtn.className        = 'btn btn-danger';
        submitBtn.innerHTML        = '<i class="bi bi-arrow-repeat me-1"></i> Submit Revision Request';
        notesLabel.textContent     = 'Revision Details *';
        notesHint.textContent      = 'Please describe clearly what changes you need.';
        notesInput.placeholder     = 'e.g. Please change the headline font and update the CTA button color.';
        notesInput.setAttribute('required', 'required');
    }
});
</script>
