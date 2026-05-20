<?php
/**
 * AdHub - Sidebar
 * Fixed: Clients, Settings, Profile links all working.
 */
?>
<aside class="adhub-sidebar" id="sidebar">

    <div class="sidebar-brand">
        <a href="/adhub/index.php" class="d-flex align-items-center gap-2 text-decoration-none">
            <span class="brand-icon-lg">◈</span>
            <span class="brand-text">AdHub</span>
        </a>
    </div>

    <div class="sidebar-role-badge">
        <span class="badge <?= $user_role === 'admin' ? 'bg-primary' : 'bg-success' ?>">
            <i class="bi bi-<?= $user_role === 'admin' ? 'shield-check' : 'person-check' ?> me-1"></i>
            <?= ucfirst($user_role) ?>
        </span>
    </div>

    <nav class="sidebar-nav">

        <?php if ($user_role === 'admin'): ?>

        <div class="nav-section-label">Main</div>

        <a href="/adhub/admin/dashboard.php"
           class="nav-link-item <?= nav_active_in('dashboard.php','admin') ?>">
            <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
        </a>

        <div class="nav-section-label">Campaigns</div>

        <a href="/adhub/admin/campaigns.php"
           class="nav-link-item <?= nav_active_class('campaigns.php') ?>">
            <i class="bi bi-megaphone"></i><span>Campaigns</span>
            <?php $pa = count_pending_approvals($pdo); if ($pa > 0): ?>
            <span class="nav-badge pending"><?= $pa ?></span>
            <?php endif; ?>
        </a>

        <a href="/adhub/admin/assets.php"
           class="nav-link-item <?= nav_active_class('assets.php') ?>">
            <i class="bi bi-folder2-open"></i><span>Assets</span>
        </a>

        <div class="nav-section-label">Insights</div>

        <a href="/adhub/admin/reports.php"
           class="nav-link-item <?= nav_active_class('reports.php') ?>">
            <i class="bi bi-file-bar-graph"></i><span>Reports</span>
        </a>

        <a href="/adhub/admin/analytics.php"
           class="nav-link-item <?= nav_active_class('analytics.php') ?>">
            <i class="bi bi-graph-up-arrow"></i><span>Analytics</span>
        </a>

        <div class="nav-section-label">System</div>

        <!-- FIXED: real link to clients.php -->
        <a href="/adhub/admin/clients.php"
           class="nav-link-item <?= nav_active_class('clients.php') ?>">
            <i class="bi bi-people"></i><span>Clients</span>
            <span class="nav-badge"><?= count_clients($pdo) ?></span>
        </a>

        <!-- FIXED: real link to settings.php -->
        <a href="/adhub/settings.php"
           class="nav-link-item <?= nav_active_class('settings.php') ?>">
            <i class="bi bi-gear"></i><span>Settings</span>
        </a>

        <a href="/adhub/profile.php"
           class="nav-link-item <?= nav_active_class('profile.php') ?>">
            <i class="bi bi-person-circle"></i><span>My Profile</span>
        </a>

        <?php elseif ($user_role === 'client'): ?>

        <div class="nav-section-label">Overview</div>

        <a href="/adhub/client/dashboard.php"
           class="nav-link-item <?= nav_active_in('dashboard.php','client') ?>">
            <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
        </a>

        <div class="nav-section-label">My Campaigns</div>

        <a href="/adhub/client/campaigns.php"
           class="nav-link-item <?= nav_active_class('campaigns.php') ?>">
            <i class="bi bi-megaphone"></i><span>Campaigns</span>
        </a>

        <a href="/adhub/client/approvals.php"
           class="nav-link-item <?= nav_active_class('approvals.php') ?>">
            <i class="bi bi-patch-check"></i><span>Approvals</span>
            <?php $p = count_pending_approvals($pdo, $user_id); if ($p > 0): ?>
            <span class="nav-badge pending"><?= $p ?></span>
            <?php endif; ?>
        </a>

        <div class="nav-section-label">Account</div>

        <!-- FIXED: real link -->
        <a href="/adhub/profile.php"
           class="nav-link-item <?= nav_active_class('profile.php') ?>">
            <i class="bi bi-person-circle"></i><span>Profile</span>
        </a>

        <a href="/adhub/settings.php"
           class="nav-link-item <?= nav_active_class('settings.php') ?>">
            <i class="bi bi-gear"></i><span>Settings</span>
        </a>

        <?php endif; ?>

    </nav>

    <div class="sidebar-footer">
        <a href="/adhub/auth/logout.php" class="nav-link-item text-danger-soft">
            <i class="bi bi-box-arrow-left"></i><span>Logout</span>
        </a>
    </div>

</aside>
