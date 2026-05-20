<?php
/**
 * AdHub - Admin Dashboard
 * Overview statistics, recent campaigns, and activity feed.
 */

$page_title  = 'Dashboard';
$require_auth = true;
$require_role = 'admin';

require_once __DIR__ . '/../includes/header.php';

// ── Fetch dashboard stats ─────────────────────────────────────────────────────
$stats = [];

// Total campaigns
$stats['campaigns'] = $pdo->query("SELECT COUNT(*) FROM campaigns")->fetchColumn();

// Active campaigns
$stats['active'] = $pdo->query("SELECT COUNT(*) FROM campaigns WHERE status = 'active'")->fetchColumn();

// Total clients
$stats['clients'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();

// Pending approvals
$stats['pending'] = $pdo->query("SELECT COUNT(*) FROM approvals WHERE status = 'pending'")->fetchColumn();

// Total ad spend
$stats['spend'] = $pdo->query("SELECT COALESCE(SUM(spend),0) FROM reports")->fetchColumn();

// Recent campaigns (last 5)
$recent_campaigns = $pdo->query(
    "SELECT c.id, c.title, c.status, c.progress, c.budget, c.start_date, c.end_date,
            u.name AS client_name
     FROM campaigns c
     JOIN users u ON c.client_id = u.id
     ORDER BY c.created_at DESC
     LIMIT 5"
)->fetchAll();

// Recent approvals (last 5)
$recent_approvals = $pdo->query(
    "SELECT a.id, a.status, a.created_at, c.title AS campaign_title, u.name AS client_name
     FROM approvals a
     JOIN campaigns c ON a.campaign_id = c.id
     JOIN users u ON a.client_id = u.id
     ORDER BY a.created_at DESC
     LIMIT 5"
)->fetchAll();
?>

<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<!-- Content wrapper -->
<div class="adhub-content-wrapper" id="contentWrapper">

    <!-- Navbar -->
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <!-- Main -->
    <main class="adhub-main">

        <!-- Page header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">Welcome back, <?= htmlspecialchars($user_name) ?>! Here's what's happening.</p>
            </div>
            <a href="/adhub/admin/campaigns.php?action=new" class="btn btn-adhub-primary">
                <i class="bi bi-plus-lg me-1"></i> New Campaign
            </a>
        </div>

        <!-- ── Stat Cards ───────────────────────────────────────────────── -->
        <div class="row g-3 mb-4">

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon indigo"><i class="bi bi-megaphone"></i></div>
                    <div>
                        <div class="stat-value"><?= number_format($stats['campaigns']) ?></div>
                        <div class="stat-label">Total Campaigns</div>
                        <div class="stat-change up"><i class="bi bi-arrow-up-short"></i>+3 this month</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="bi bi-play-circle"></i></div>
                    <div>
                        <div class="stat-value"><?= number_format($stats['active']) ?></div>
                        <div class="stat-label">Active Campaigns</div>
                        <div class="stat-change up"><i class="bi bi-arrow-up-short"></i>Running now</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon cyan"><i class="bi bi-people"></i></div>
                    <div>
                        <div class="stat-value"><?= number_format($stats['clients']) ?></div>
                        <div class="stat-label">Clients</div>
                        <div class="stat-change up"><i class="bi bi-arrow-up-short"></i>+1 this week</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon amber"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="stat-value"><?= number_format($stats['pending']) ?></div>
                        <div class="stat-label">Pending Approvals</div>
                        <div class="stat-change <?= $stats['pending'] > 0 ? 'down' : 'up' ?>">
                            <?= $stats['pending'] > 0 ? '<i class="bi bi-exclamation-circle"></i> Needs attention' : '<i class="bi bi-check-circle"></i> All clear' ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── Two-column row ───────────────────────────────────────────── -->
        <div class="row g-3 mb-4">

            <!-- Recent Campaigns Table -->
            <div class="col-lg-8">
                <div class="adhub-card h-100">
                    <div class="card-header">
                        <h5 class="card-title"><i class="bi bi-megaphone me-2 text-primary"></i>Recent Campaigns</h5>
                        <a href="/adhub/admin/campaigns.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_campaigns)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            No campaigns yet. <a href="/adhub/admin/campaigns.php?action=new">Create one</a>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="adhub-table">
                                <thead>
                                    <tr>
                                        <th>Campaign</th>
                                        <th>Client</th>
                                        <th>Status</th>
                                        <th>Progress</th>
                                        <th>Budget</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_campaigns as $c): ?>
                                    <tr>
                                        <td>
                                            <a href="/adhub/admin/campaigns.php?id=<?= $c['id'] ?>" class="fw-600 text-decoration-none text-dark">
                                                <?= htmlspecialchars($c['title']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($c['client_name']) ?></td>
                                        <td>
                                            <?php
                                            $map = [
                                                'active'    => 'badge-active',
                                                'draft'     => 'badge-draft',
                                                'review'    => 'badge-review',
                                                'completed' => 'badge-completed',
                                                'paused'    => 'badge-paused',
                                            ];
                                            $cls = $map[$c['status']] ?? 'badge-draft';
                                            ?>
                                            <span class="adhub-badge <?= $cls ?>"><?= ucfirst($c['status']) ?></span>
                                        </td>
                                        <td style="min-width:100px;">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="adhub-progress flex-grow-1">
                                                    <div class="adhub-progress-bar" data-progress="<?= $c['progress'] ?>"></div>
                                                </div>
                                                <span class="small text-muted"><?= $c['progress'] ?>%</span>
                                            </div>
                                        </td>
                                        <td class="fw-600">$<?= number_format($c['budget'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Approvals -->
            <div class="col-lg-4">
                <div class="adhub-card h-100">
                    <div class="card-header">
                        <h5 class="card-title"><i class="bi bi-patch-check me-2 text-success"></i>Approvals</h5>
                        <a href="/adhub/admin/campaigns.php" class="btn btn-sm btn-outline-success">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_approvals)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            No approvals yet.
                        </div>
                        <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($recent_approvals as $a): ?>
                            <?php
                            $amap = [
                                'pending'  => ['badge-pending',  'bi-hourglass-split'],
                                'approved' => ['badge-approved', 'bi-check-circle'],
                                'revision' => ['badge-revision', 'bi-arrow-repeat'],
                            ];
                            [$abadge, $aicon] = $amap[$a['status']] ?? ['badge-draft','bi-circle'];
                            ?>
                            <li class="list-group-item border-0 px-4 py-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-600 small"><?= htmlspecialchars($a['campaign_title']) ?></div>
                                        <div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($a['client_name']) ?></div>
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

        <!-- ── Quick stats row ──────────────────────────────────────────── -->
        <div class="row g-3">
            <div class="col-md-4">
                <div class="adhub-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="stat-icon blue"><i class="bi bi-currency-dollar"></i></div>
                            <div>
                                <div class="stat-value">$<?= number_format($stats['spend'], 2) ?></div>
                                <div class="stat-label">Total Ad Spend</div>
                            </div>
                        </div>
                        <div class="adhub-progress">
                            <div class="adhub-progress-bar" data-progress="68"></div>
                        </div>
                        <div class="text-muted small mt-1">68% of monthly budget used</div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="adhub-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="stat-icon green"><i class="bi bi-graph-up-arrow"></i></div>
                            <div>
                                <div class="stat-value">4.2%</div>
                                <div class="stat-label">Avg. Conversion Rate</div>
                            </div>
                        </div>
                        <div class="adhub-progress">
                            <div class="adhub-progress-bar" data-progress="42"></div>
                        </div>
                        <div class="text-muted small mt-1">Industry avg: 3.1%</div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="adhub-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="stat-icon indigo"><i class="bi bi-eye"></i></div>
                            <div>
                                <div class="stat-value">128K</div>
                                <div class="stat-label">Total Impressions</div>
                            </div>
                        </div>
                        <div class="adhub-progress">
                            <div class="adhub-progress-bar" data-progress="85"></div>
                        </div>
                        <div class="text-muted small mt-1">85% of target reached</div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Toast container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div><!-- /.adhub-content-wrapper -->
