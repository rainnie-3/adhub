<?php
/**
 * AdHub - Client Dashboard
 * Overview for the client: campaign summary, progress, pending approvals.
 */

$page_title   = 'My Dashboard';
$require_auth = true;
$require_role = 'client';

require_once __DIR__ . '/../includes/header.php';

// ── Fetch data for this client ────────────────────────────────────────────────

// Campaign counts
$total = $pdo->prepare("SELECT COUNT(*) FROM campaigns WHERE client_id = ?");
$total->execute([$user_id]);
$total_campaigns = $total->fetchColumn();

$active_stmt = $pdo->prepare("SELECT COUNT(*) FROM campaigns WHERE client_id = ? AND status = 'active'");
$active_stmt->execute([$user_id]);
$active_campaigns = $active_stmt->fetchColumn();

$pending_stmt = $pdo->prepare("SELECT COUNT(*) FROM approvals WHERE client_id = ? AND status = 'pending'");
$pending_stmt->execute([$user_id]);
$pending_approvals = $pending_stmt->fetchColumn();

// Recent campaigns
$recent = $pdo->prepare(
    "SELECT id, title, status, progress, budget, start_date, end_date
     FROM campaigns WHERE client_id = ?
     ORDER BY created_at DESC LIMIT 5"
);
$recent->execute([$user_id]);
$recent_campaigns = $recent->fetchAll();

// Recent approvals
$approvals_stmt = $pdo->prepare(
    "SELECT a.id, a.status, a.notes, a.created_at, c.title AS campaign_title
     FROM approvals a JOIN campaigns c ON a.campaign_id = c.id
     WHERE a.client_id = ?
     ORDER BY a.created_at DESC LIMIT 5"
);
$approvals_stmt->execute([$user_id]);
$recent_approvals = $approvals_stmt->fetchAll();

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
                <h1 class="page-title">My Dashboard</h1>
                <p class="page-subtitle">Welcome back, <?= htmlspecialchars($user_name) ?>! Here's your campaign overview.</p>
            </div>
        </div>

        <!-- Stat cards -->
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="stat-card">
                    <div class="stat-icon indigo"><i class="bi bi-megaphone"></i></div>
                    <div>
                        <div class="stat-value"><?= $total_campaigns ?></div>
                        <div class="stat-label">My Campaigns</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="bi bi-play-circle"></i></div>
                    <div>
                        <div class="stat-value"><?= $active_campaigns ?></div>
                        <div class="stat-label">Active Now</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="stat-card">
                    <div class="stat-icon amber"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="stat-value"><?= $pending_approvals ?></div>
                        <div class="stat-label">Awaiting My Approval</div>
                        <?php if ($pending_approvals > 0): ?>
                        <div class="stat-change down"><i class="bi bi-exclamation-circle"></i> Action needed</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">

            <!-- Campaign list -->
            <div class="col-lg-8">
                <div class="adhub-card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="bi bi-megaphone me-2 text-primary"></i>My Campaigns</h5>
                        <a href="/adhub/client/campaigns.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_campaigns)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-3 opacity-25"></i>
                            <p>No campaigns assigned yet.</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($recent_campaigns as $c): ?>
                        <div class="px-4 py-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="fw-600"><?= htmlspecialchars($c['title']) ?></div>
                                    <div class="text-muted small">
                                        <?= $c['start_date'] ? date('M d', strtotime($c['start_date'])) : 'TBD' ?>
                                        <?= $c['end_date'] ? ' → ' . date('M d, Y', strtotime($c['end_date'])) : '' ?>
                                    </div>
                                </div>
                                <span class="adhub-badge <?= $badge_map[$c['status']] ?? 'badge-draft' ?>">
                                    <?= ucfirst($c['status']) ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="adhub-progress flex-grow-1">
                                    <div class="adhub-progress-bar" data-progress="<?= $c['progress'] ?>"></div>
                                </div>
                                <span class="small fw-600"><?= $c['progress'] ?>%</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Approvals -->
            <div class="col-lg-4">
                <div class="adhub-card">
                    <div class="card-header">
                        <h5 class="card-title"><i class="bi bi-patch-check me-2 text-success"></i>Approvals</h5>
                        <a href="/adhub/client/approvals.php" class="btn btn-sm btn-outline-success">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_approvals)): ?>
                        <div class="text-center py-5 text-muted small">No approvals yet.</div>
                        <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($recent_approvals as $a): ?>
                            <?php
                            $amap = [
                                'pending'  => ['badge-pending',  'bi-hourglass-split text-warning'],
                                'approved' => ['badge-approved', 'bi-check-circle text-success'],
                                'revision' => ['badge-revision', 'bi-arrow-repeat text-danger'],
                            ];
                            [$abadge, $aicon] = $amap[$a['status']] ?? ['badge-draft', 'bi-circle'];
                            ?>
                            <li class="list-group-item border-0 px-4 py-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <i class="bi <?= $aicon ?> me-1"></i>
                                        <span class="fw-600 small"><?= htmlspecialchars($a['campaign_title']) ?></span>
                                        <?php if ($a['notes']): ?>
                                        <div class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($a['notes']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="adhub-badge <?= $abadge ?>"><?= ucfirst($a['status']) ?></span>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div><!-- /.adhub-content-wrapper --></div>
