<?php
/**
 * AdHub - Top Navbar Include
 * Renders the top navigation bar with user info, notifications, and toggle.
 */
?>
<!-- ── Top Navbar ──────────────────────────────────────────────────────────── -->
<nav class="navbar navbar-expand-lg adhub-navbar" id="topNavbar">
    <div class="container-fluid px-4">

        <!-- Sidebar toggle button (mobile + desktop collapse) -->
        <button class="btn btn-link sidebar-toggle me-3 p-0" id="sidebarToggle" title="Toggle sidebar">
            <i class="bi bi-list fs-4"></i>
        </button>

        <!-- Brand logo (visible when sidebar is collapsed) -->
        <a class="navbar-brand adhub-brand d-lg-none" href="#">
            <span class="brand-icon">◈</span> AdHub
        </a>

        <!-- Right-side items -->
        <div class="ms-auto d-flex align-items-center gap-3">

            <!-- Search (desktop only) -->
            <div class="adhub-search d-none d-md-flex">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Search campaigns…" class="adhub-search-input">
            </div>

            <!-- Notification bell -->
            <div class="dropdown">
                <button class="btn btn-link nav-icon-btn position-relative" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell fs-5"></i>
                    <span class="badge-dot"></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end adhub-dropdown shadow" style="min-width:320px;">
                    <li class="dropdown-header d-flex justify-content-between align-items-center px-3 py-2">
                        <span class="fw-600">Notifications</span>
                        <span class="badge bg-primary rounded-pill">3</span>
                    </li>
                    <li><hr class="dropdown-divider my-0"></li>
                    <li>
                        <a class="dropdown-item notification-item" href="#">
                            <div class="notif-icon bg-primary-soft"><i class="bi bi-check-circle text-primary"></i></div>
                            <div>
                                <div class="notif-title">Campaign approved</div>
                                <div class="notif-time">2 minutes ago</div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item notification-item" href="#">
                            <div class="notif-icon bg-warning-soft"><i class="bi bi-exclamation-circle text-warning"></i></div>
                            <div>
                                <div class="notif-title">Revision requested</div>
                                <div class="notif-time">1 hour ago</div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item notification-item" href="#">
                            <div class="notif-icon bg-success-soft"><i class="bi bi-file-earmark-plus text-success"></i></div>
                            <div>
                                <div class="notif-title">New asset uploaded</div>
                                <div class="notif-time">3 hours ago</div>
                            </div>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-0"></li>
                    <li><a class="dropdown-item text-center text-primary small py-2" href="#">View all notifications</a></li>
                </ul>
            </div>

            <!-- User avatar & dropdown -->
            <div class="dropdown">
                <button class="btn btn-link d-flex align-items-center gap-2 p-0 nav-user-btn" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="adhub-avatar">
                        <?= strtoupper(substr($user_name, 0, 1)) ?>
                    </div>
                    <div class="d-none d-md-block text-start">
                        <div class="user-name-nav"><?= htmlspecialchars($user_name) ?></div>
                        <div class="user-role-nav"><?= ucfirst($user_role) ?></div>
                    </div>
                    <i class="bi bi-chevron-down small d-none d-md-block ms-1"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end adhub-dropdown shadow">
                    <li class="px-3 py-2">
                        <div class="fw-600"><?= htmlspecialchars($user_name) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></div>
                    </li>
                    <li><hr class="dropdown-divider my-0"></li>
                    <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>My Profile</a></li>
                    <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
                    <li><hr class="dropdown-divider my-0"></li>
                    <li><a class="dropdown-item text-danger" href="/adhub/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>

        </div>
    </div>
</nav>
<!-- ── /Top Navbar ─────────────────────────────────────────────────────────── -->
