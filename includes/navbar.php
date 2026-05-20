<?php
/**
 * AdHub - Top Navbar
 * Fixed: search, profile link, settings link, notification routing, all clickable
 */
?>
<nav class="navbar navbar-expand-lg adhub-navbar" id="topNavbar">
    <div class="container-fluid px-4">

        <button class="btn btn-link sidebar-toggle me-3 p-0" id="sidebarToggle" title="Toggle sidebar">
            <i class="bi bi-list fs-4"></i>
        </button>

        <a class="navbar-brand adhub-brand d-lg-none" href="/adhub/index.php">
            <span class="brand-icon">◈</span> AdHub
        </a>

        <div class="ms-auto d-flex align-items-center gap-3">

            <!-- ── Global Search ──────────────────────────────────────── -->
            <div class="adhub-search d-none d-md-flex" id="globalSearchWrap">
                <i class="bi bi-search text-muted"></i>
                <input type="text" id="globalSearchInput"
                       placeholder="Search campaigns, clients…"
                       class="adhub-search-input"
                       autocomplete="off">
                <!-- Results dropdown -->
                <div id="searchResults" class="search-results-dropdown" style="display:none;"></div>
            </div>

            <!-- ── Notifications ──────────────────────────────────────── -->
            <?php
            $pending_count   = count_pending_approvals($pdo, $user_role === 'client' ? $user_id : null);
            $recent_campaigns_notif = db_query($pdo,
                "SELECT id, title, status, updated_at FROM campaigns
                 " . ($user_role === 'client' ? "WHERE client_id = $user_id" : "") . "
                 ORDER BY updated_at DESC LIMIT 3"
            );
            ?>
            <div class="dropdown">
                <button class="btn btn-link nav-icon-btn position-relative"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell fs-5"></i>
                    <?php if ($pending_count > 0): ?>
                    <span class="badge-dot"></span>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end adhub-dropdown shadow"
                    style="min-width:320px;">
                    <li class="dropdown-header d-flex justify-content-between align-items-center px-3 py-2">
                        <span class="fw-600">Notifications</span>
                        <?php if ($pending_count > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= $pending_count ?></span>
                        <?php endif; ?>
                    </li>
                    <li><hr class="dropdown-divider my-0"></li>

                    <?php if ($pending_count > 0): ?>
                    <li>
                        <a class="dropdown-item notification-item py-2"
                           href="/adhub/<?= e($user_role) ?>/approvals.php">
                            <div class="notif-icon bg-warning-soft">
                                <i class="bi bi-hourglass-split text-warning"></i>
                            </div>
                            <div>
                                <div class="notif-title">
                                    <?= $pending_count ?> pending approval<?= $pending_count !== 1 ? 's' : '' ?>
                                </div>
                                <div class="notif-time">Tap to review</div>
                            </div>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php foreach ($recent_campaigns_notif as $nc): ?>
                    <li>
                        <a class="dropdown-item notification-item py-2"
                           href="/adhub/<?= e($user_role) ?>/campaigns.php">
                            <div class="notif-icon bg-primary-soft">
                                <i class="bi bi-megaphone text-primary"></i>
                            </div>
                            <div>
                                <div class="notif-title"><?= e(truncate_str($nc['title'], 30)) ?></div>
                                <div class="notif-time">
                                    Status: <?= ucfirst($nc['status']) ?> ·
                                    <?= format_date($nc['updated_at'], 'M d') ?>
                                </div>
                            </div>
                        </a>
                    </li>
                    <?php endforeach; ?>

                    <?php if ($pending_count === 0 && empty($recent_campaigns_notif)): ?>
                    <li>
                        <span class="dropdown-item text-muted small py-3 text-center d-block">
                            <i class="bi bi-check-circle me-1 text-success"></i>All caught up!
                        </span>
                    </li>
                    <?php endif; ?>

                    <li><hr class="dropdown-divider my-0"></li>
                    <li>
                        <a class="dropdown-item text-center text-primary small py-2"
                           href="/adhub/<?= e($user_role) ?>/<?= $user_role === 'admin' ? 'campaigns.php' : 'approvals.php' ?>">
                            View all activity →
                        </a>
                    </li>
                </ul>
            </div>

            <!-- ── User Dropdown ──────────────────────────────────────── -->
            <div class="dropdown">
                <button class="btn btn-link d-flex align-items-center gap-2 p-0 nav-user-btn"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="adhub-avatar"><?= avatar_initial($user_name) ?></div>
                    <div class="d-none d-md-block text-start">
                        <div class="user-name-nav"><?= e($user_name) ?></div>
                        <div class="user-role-nav"><?= ucfirst($user_role) ?></div>
                    </div>
                    <i class="bi bi-chevron-down small d-none d-md-block ms-1"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end adhub-dropdown shadow" style="min-width:220px;">
                    <li class="px-3 py-2">
                        <div class="fw-600"><?= e($user_name) ?></div>
                        <div class="text-muted small"><?= e($_SESSION['email'] ?? '') ?></div>
                    </li>
                    <li><hr class="dropdown-divider my-0"></li>
                    <!-- FIXED: real links -->
                    <li>
                        <a class="dropdown-item" href="/adhub/profile.php">
                            <i class="bi bi-person me-2 text-primary"></i>My Profile
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="/adhub/settings.php">
                            <i class="bi bi-gear me-2 text-secondary"></i>Settings
                        </a>
                    </li>
                    <?php if ($user_role === 'admin'): ?>
                    <li>
                        <a class="dropdown-item" href="/adhub/admin/clients.php">
                            <i class="bi bi-people me-2 text-info"></i>Manage Clients
                        </a>
                    </li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider my-0"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="/adhub/auth/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</nav>

<!-- Search results rendered via JS (see script.js) -->
