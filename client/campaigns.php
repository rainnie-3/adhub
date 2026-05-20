<?php
/**
 * AdHub - Client Campaigns Page
 * Read-only view of campaigns assigned to this client.
 */

$page_title   = 'My Campaigns';
$require_auth = true;
$require_role = 'client';

require_once __DIR__ . '/../includes/header.php';

// Fetch all campaigns for this client
$stmt = $pdo->prepare(
    "SELECT c.*, u.name AS created_by_name
     FROM campaigns c
     JOIN users u ON c.created_by = u.id
     WHERE c.client_id = ?
     ORDER BY c.created_at DESC"
);
$stmt->execute([$user_id]);
$campaigns = $stmt->fetchAll();

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

        <div class="page-header">
            <div>
                <h1 class="page-title">My Campaigns</h1>
                <p class="page-subtitle">All campaigns created for your account.</p>
            </div>
        </div>

        <?php if (empty($campaigns)): ?>
        <div class="adhub-card">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-megaphone fs-1 d-block mb-3 opacity-25"></i>
                <p>No campaigns assigned to your account yet.<br>Contact your agency to get started.</p>
            </div>
        </div>
        <?php else: ?>

        <!-- Campaign cards -->
        <div class="row g-3">
            <?php foreach ($campaigns as $c): ?>
            <div class="col-md-6 col-xl-4">
                <div class="adhub-card h-100">
                    <div class="card-body">
                        <!-- Header row -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h6 class="fw-700 mb-0" style="font-family:var(--font-heading);">
                                <?= htmlspecialchars($c['title']) ?>
                            </h6>
                            <span class="adhub-badge <?= $badge_map[$c['status']] ?? 'badge-draft' ?>">
                                <?= ucfirst($c['status']) ?>
                            </span>
                        </div>

                        <!-- Description -->
                        <?php if ($c['description']): ?>
                        <p class="text-muted small mb-3"><?= htmlspecialchars($c['description']) ?></p>
                        <?php endif; ?>

                        <!-- Progress -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Progress</span>
                                <span class="fw-600"><?= $c['progress'] ?>%</span>
                            </div>
                            <div class="adhub-progress">
                                <div class="adhub-progress-bar" data-progress="<?= $c['progress'] ?>"></div>
                            </div>
                        </div>

                        <!-- Details -->
                        <div class="row g-2 text-center border-top pt-3">
                            <div class="col-4">
                                <div class="text-muted" style="font-size:.65rem;text-transform:uppercase;letter-spacing:.05em;">Budget</div>
                                <div class="fw-700 small">$<?= number_format($c['budget'], 0) ?></div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted" style="font-size:.65rem;text-transform:uppercase;letter-spacing:.05em;">Start</div>
                                <div class="fw-700 small"><?= $c['start_date'] ? date('M d', strtotime($c['start_date'])) : '—' ?></div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted" style="font-size:.65rem;text-transform:uppercase;letter-spacing:.05em;">End</div>
                                <div class="fw-700 small"><?= $c['end_date'] ? date('M d', strtotime($c['end_date'])) : '—' ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div><!-- /.adhub-content-wrapper --></div>
