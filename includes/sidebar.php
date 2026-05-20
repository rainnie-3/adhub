<?php
/**
 * AdHub - Sidebar Include
 * Renders the left sidebar. Nav links are filtered by $user_role.
 */

// Current page for active state highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir  = basename(dirname($_SERVER['PHP_SELF']));

/**
 * Helper: returns 'active' if the given filename matches current page.
 */
function nav_active(string $file): string {
    global $current_page;
    return $current_page === $file ? 'active' : '';
}
?>
<!-- ── Sidebar ────────────────────────────────────────────────────────────── -->
<aside class="adhub-sidebar" id="sidebar">

    <!-- Brand -->
    <div class="sidebar-brand">
        <span class="brand-icon-lg">◈</span>
        <span class="brand-text">AdHub</span>
    </div>

    <!-- Role badge -->
    <div class="sidebar-role-badge">
        <span class="badge <?= $user_role === 'admin' ? 'bg-primary' : 'bg-success' ?>">
            <i class="bi bi-<?= $user_role === 'admin' ? 'shield-check' : 'person-check' ?> me-1"></i>
            <?= ucfirst($user_role) ?>
        </span>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">

        <?php if ($user_role === 'admin'): ?>
        <!-- ── ADMIN NAV ── -->
        <div class="nav-section-label">Main</div>

        <a href="/adhub/admin/dashboard.php" class="nav-link-item <?= ($current_page === 'dashboard.php' && $current_dir === 'admin') ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i>
            <span>Dashboard</span>
        </a>

        <div class="nav-section-label">Campaigns</div>

        <a href="/adhub/admin/campaigns.php" class="nav-link-item <?= nav_active('campaigns.php') ?>">
            <i class="bi bi-megaphone"></i>
            <span>Campaigns</span>
            <span class="nav-badge">12</span>
        </a>

        <a href="/adhub/admin/assets.php" class="nav-link-item <?= nav_active('assets.php') ?>">
            <i class="bi bi-folder2-open"></i>
            <span>Assets</span>
        </a>

        <div class="nav-section-label">Insights</div>

        <a href="/adhub/admin/reports.php" class="nav-link-item <?= nav_active('reports.php') ?>">
            <i class="bi bi-file-bar-graph"></i>
            <span>Reports</span>
        </a>

        <a href="/adhub/admin/analytics.php" class="nav-link-item <?= nav_active('analytics.php') ?>">
            <i class="bi bi-graph-up-arrow"></i>
            <span>Analytics</span>
        </a>

        <div class="nav-section-label">System</div>

        <a href="#" class="nav-link-item">
            <i class="bi bi-people"></i>
            <span>Clients</span>
        </a>

        <a href="#" class="nav-link-item">
            <i class="bi bi-gear"></i>
            <span>Settings</span>
        </a>

        <?php elseif ($user_role === 'client'): ?>
        <!-- ── CLIENT NAV ── -->
        <div class="nav-section-label">Overview</div>

        <a href="/adhub/client/dashboard.php" class="nav-link-item <?= ($current_page === 'dashboard.php' && $current_dir === 'client') ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i>
            <span>Dashboard</span>
        </a>

        <div class="nav-section-label">My Campaigns</div>

        <a href="/adhub/client/campaigns.php" class="nav-link-item <?= nav_active('campaigns.php') ?>">
            <i class="bi bi-megaphone"></i>
            <span>Campaigns</span>
        </a>

        <a href="/adhub/client/approvals.php" class="nav-link-item <?= nav_active('approvals.php') ?>">
            <i class="bi bi-patch-check"></i>
            <span>Approvals</span>
            <span class="nav-badge pending">2</span>
        </a>

        <div class="nav-section-label">Account</div>

        <a href="#" class="nav-link-item">
            <i class="bi bi-person-circle"></i>
            <span>Profile</span>
        </a>

        <?php endif; ?>

    </nav>

    <!-- Sidebar footer -->
    <div class="sidebar-footer">
        <a href="/adhub/auth/logout.php" class="nav-link-item text-danger-soft">
            <i class="bi bi-box-arrow-left"></i>
            <span>Logout</span>
        </a>
    </div>

</aside>
<!-- ── /Sidebar ───────────────────────────────────────────────────────────── -->
