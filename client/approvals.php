<?php
/**
 * AdHub - Client Approvals Page
 * Clients can review campaign assets and approve, request revision, or leave notes.
 */

$page_title   = 'Approvals';
$require_auth = true;
$require_role = 'client';

require_once __DIR__ . '/../includes/header.php';

$msg      = '';
$msg_type = 'success';

// ── Handle approval action ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $approval_id = intval($_POST['approval_id'] ?? 0);
    $action_val  = $_POST['approval_action'] ?? '';
    $notes       = trim($_POST['notes'] ?? '');

    $valid_actions = ['approved', 'revision'];

    if ($approval_id > 0 && in_array($action_val, $valid_actions)) {
        // Verify this approval belongs to the logged-in client
        $check = $pdo->prepare("SELECT id FROM approvals WHERE id = ? AND client_id = ?");
        $check->execute([$approval_id, $user_id]);

        if ($check->fetch()) {
            $stmt = $pdo->prepare(
                "UPDATE approvals SET status = ?, notes = ?, reviewed_at = NOW() WHERE id = ? AND client_id = ?"
            );
            $stmt->execute([$action_val, $notes, $approval_id, $user_id]);

            $msg = $action_val === 'approved'
                ? 'Campaign approved successfully!'
                : 'Revision request submitted. The agency will be notified.';
            $msg_type = $action_val === 'approved' ? 'success' : 'warning';
        } else {
            $msg      = 'Invalid approval request.';
            $msg_type = 'danger';
        }
    }
}

// ── Fetch all approvals for this client ───────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT a.id, a.status, a.notes, a.created_at, a.reviewed_at,
            c.id AS campaign_id, c.title AS campaign_title, c.status AS campaign_status,
            c.progress, c.description
     FROM approvals a
     JOIN campaigns c ON a.campaign_id = c.id
     WHERE a.client_id = ?
     ORDER BY
         FIELD(a.status, 'pending', 'revision', 'approved'),
         a.created_at DESC"
);
$stmt->execute([$user_id]);
$approvals = $stmt->fetchAll();

// Count by status
$counts = ['pending' => 0, 'approved' => 0, 'revision' => 0];
foreach ($approvals as $a) {
    $counts[$a['status']] = ($counts[$a['status']] ?? 0) + 1;
}

// ── Fetch assets per campaign (for modal) ─────────────────────────────────────
$assets_map = [];
if (!empty($approvals)) {
    $camp_ids = array_unique(array_column($approvals, 'campaign_id'));
    $in       = implode(',', array_fill(0, count($camp_ids), '?'));
    $astmt    = $pdo->prepare(
        "SELECT * FROM campaign_assets WHERE campaign_id IN ($in) ORDER BY uploaded_at DESC"
    );
    $astmt->execute($camp_ids);
    foreach ($astmt->fetchAll() as $asset) {
        $assets_map[$asset['campaign_id']][] = $asset;
    }
}

$badge_map = [
    'pending'  => 'badge-pending',
    'approved' => 'badge-approved',
    'revision' => 'badge-revision',
];

$icon_map = [
    'pending'  => 'bi-hourglass-split',
    'approved' => 'bi-check-circle-fill',
    'revision' => 'bi-arrow-repeat',
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
                <h1 class="page-title">Approvals</h1>
                <p class="page-subtitle">Review campaign materials and provide your feedback.</p>
            </div>
        </div>

        <!-- Flash message -->
        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible alert-auto-dismiss d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi bi-<?= $msg_type === 'success' ? 'check-circle-fill' : ($msg_type === 'warning' ? 'exclamation-triangle-fill' : 'x-circle-fill') ?>"></i>
            <div><?= htmlspecialchars($msg) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Status summary pills -->
        <div class="d-flex gap-3 mb-4 flex-wrap">
            <div class="d-flex align-items-center gap-2 px-4 py-3 adhub-card" style="flex:1;min-width:140px;">
                <div class="stat-icon amber" style="width:40px;height:40px;font-size:1.1rem;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <div class="stat-value" style="font-size:1.4rem;"><?= $counts['pending'] ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 px-4 py-3 adhub-card" style="flex:1;min-width:140px;">
                <div class="stat-icon green" style="width:40px;height:40px;font-size:1.1rem;">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <div class="stat-value" style="font-size:1.4rem;"><?= $counts['approved'] ?></div>
                    <div class="stat-label">Approved</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 px-4 py-3 adhub-card" style="flex:1;min-width:140px;">
                <div class="stat-icon red" style="width:40px;height:40px;font-size:1.1rem;">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <div>
                    <div class="stat-value" style="font-size:1.4rem;"><?= $counts['revision'] ?></div>
                    <div class="stat-label">Revisions</div>
                </div>
            </div>
        </div>

        <!-- Approvals list -->
        <?php if (empty($approvals)): ?>
        <div class="adhub-card">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-patch-check fs-1 d-block mb-3 opacity-25"></i>
                <p class="mb-0">No approval requests yet. Your agency will notify you when materials are ready for review.</p>
            </div>
        </div>

        <?php else: ?>

        <!-- Pending first, then others -->
        <?php
        $grouped = ['pending' => [], 'revision' => [], 'approved' => []];
        foreach ($approvals as $a) {
            $grouped[$a['status']][] = $a;
        }
        $sections = [
            'pending'  => ['label' => 'Awaiting Your Review',    'color' => 'warning'],
            'revision' => ['label' => 'Revision Requested',      'color' => 'danger'],
            'approved' => ['label' => 'Approved',                 'color' => 'success'],
        ];

        foreach ($sections as $status => $section):
            if (empty($grouped[$status])) continue;
        ?>

        <div class="mb-4">
            <h6 class="fw-700 mb-3 d-flex align-items-center gap-2" style="font-family:var(--font-heading);">
                <span class="badge bg-<?= $section['color'] ?>"><?= count($grouped[$status]) ?></span>
                <?= $section['label'] ?>
            </h6>

            <div class="row g-3">
                <?php foreach ($grouped[$status] as $a): ?>
                <div class="col-lg-6">
                    <div class="adhub-card h-100 <?= $status === 'pending' ? 'approval-pending-card' : '' ?>">
                        <div class="card-body">

                            <!-- Campaign title & badge -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 class="fw-700 mb-1" style="font-family:var(--font-heading);">
                                        <?= htmlspecialchars($a['campaign_title']) ?>
                                    </h6>
                                    <div class="text-muted small">
                                        Submitted <?= date('M d, Y', strtotime($a['created_at'])) ?>
                                    </div>
                                </div>
                                <span class="adhub-badge <?= $badge_map[$a['status']] ?>">
                                    <i class="bi <?= $icon_map[$a['status']] ?> me-1"></i>
                                    <?= ucfirst($a['status']) ?>
                                </span>
                            </div>

                            <!-- Campaign description -->
                            <?php if ($a['description']): ?>
                            <p class="text-muted small mb-3"><?= htmlspecialchars($a['description']) ?></p>
                            <?php endif; ?>

                            <!-- Progress bar -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Campaign Progress</span>
                                    <span class="fw-600"><?= $a['progress'] ?>%</span>
                                </div>
                                <div class="adhub-progress">
                                    <div class="adhub-progress-bar" data-progress="<?= $a['progress'] ?>"></div>
                                </div>
                            </div>

                            <!-- Notes (if any) -->
                            <?php if ($a['notes'] && $a['status'] !== 'pending'): ?>
                            <div class="rounded-2 p-3 mb-3" style="background:var(--ah-bg);font-size:.8rem;">
                                <div class="fw-600 mb-1 text-muted">Your notes:</div>
                                <?= htmlspecialchars($a['notes']) ?>
                            </div>
                            <?php endif; ?>

                            <!-- Reviewed at -->
                            <?php if ($a['reviewed_at']): ?>
                            <div class="text-muted small mb-3">
                                <i class="bi bi-clock-history me-1"></i>
                                Reviewed on <?= date('M d, Y \a\t g:i A', strtotime($a['reviewed_at'])) ?>
                            </div>
                            <?php endif; ?>

                            <!-- Assets attached to this campaign -->
                            <?php if (!empty($assets_map[$a['campaign_id']])): ?>
                            <div class="mb-3">
                                <div class="text-muted small fw-600 mb-2">Attached Files:</div>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($assets_map[$a['campaign_id']] as $asset): ?>
                                    <a href="/adhub/uploads/<?= htmlspecialchars($asset['filename']) ?>"
                                       target="_blank"
                                       class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                                       title="<?= htmlspecialchars($asset['original_name']) ?>">
                                        <i class="bi bi-paperclip"></i>
                                        <span class="text-truncate" style="max-width:120px;font-size:.75rem;">
                                            <?= htmlspecialchars($asset['original_name']) ?>
                                        </span>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Action buttons (pending only) -->
                            <?php if ($a['status'] === 'pending'): ?>
                            <div class="d-flex gap-2 pt-2 border-top">
                                <!-- Approve button -->
                                <button class="btn btn-success btn-sm flex-grow-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#approvalModal"
                                        data-approval-id="<?= $a['id'] ?>"
                                        data-action="approved"
                                        data-campaign="<?= htmlspecialchars($a['campaign_title'], ENT_QUOTES) ?>">
                                    <i class="bi bi-check-lg me-1"></i> Approve
                                </button>
                                <!-- Request revision -->
                                <button class="btn btn-outline-danger btn-sm flex-grow-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#approvalModal"
                                        data-approval-id="<?= $a['id'] ?>"
                                        data-action="revision"
                                        data-campaign="<?= htmlspecialchars($a['campaign_title'], ENT_QUOTES) ?>">
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

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div><!-- /.adhub-content-wrapper --></div>

<!-- Approval action modal -->
<div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:var(--ah-radius);">
            <form method="POST" action="">
                <input type="hidden" name="approval_id" id="modal_approval_id">
                <input type="hidden" name="approval_action" id="modal_approval_action">

                <div class="modal-header border-0">
                    <h5 class="modal-title" id="approvalModalLabel">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p class="mb-1 text-muted small">Campaign:</p>
                    <p class="fw-700 mb-3" id="modal_campaign_name"></p>

                    <!-- Shown only for revision requests -->
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

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

<script>
// Populate approval modal based on action type
document.getElementById('approvalModal').addEventListener('show.bs.modal', function (e) {
    const btn        = e.relatedTarget;
    const approvalId = btn.dataset.approvalId;
    const action     = btn.dataset.action;
    const campaign   = btn.dataset.campaign;

    this.querySelector('#modal_approval_id').value    = approvalId;
    this.querySelector('#modal_approval_action').value = action;
    this.querySelector('#modal_campaign_name').textContent = campaign;
    this.querySelector('#modal_notes').value = '';

    const submitBtn  = this.querySelector('#modal_submit_btn');
    const notesLabel = this.querySelector('#notesLabel');
    const notesHint  = this.querySelector('#notesHint');

    if (action === 'approved') {
        submitBtn.className = 'btn btn-success';
        submitBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Approve Campaign';
        notesLabel.textContent = 'Approval Notes (optional)';
        notesHint.textContent  = 'You can optionally leave a positive note for the team.';
        document.querySelector('#modal_notes').placeholder = 'e.g. Looks great, approved!';
    } else {
        submitBtn.className = 'btn btn-danger';
        submitBtn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Submit Revision Request';
        notesLabel.textContent = 'Revision Details *';
        notesHint.textContent  = 'Please describe clearly what changes you need.';
        document.querySelector('#modal_notes').placeholder = 'e.g. Please change the headline font and update the CTA button color.';
    }
});
</script>

<style>
/* Highlight pending cards with a subtle left border */
.approval-pending-card {
    border-left: 3px solid var(--ah-warning) !important;
}
</style>
