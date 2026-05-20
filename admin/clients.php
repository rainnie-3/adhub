<?php
/**
 * AdHub - Admin Clients Page
 * View and manage all client accounts.
 */

$page_title   = 'Clients';
$require_auth = true;
$require_role = 'admin';

require_once __DIR__ . '/../includes/header.php';

$msg      = '';
$msg_type = 'success';

// ── Handle add client POST ────────────────────────────────────────────────────
if (is_post()) {
    $name     = input_str($_POST, 'name');
    $email    = input_str($_POST, 'email');
    $company  = input_str($_POST, 'company');
    $password = $_POST['password'] ?? 'password';

    $validation = validate_registration([
        'name'     => $name,
        'email'    => $email,
        'password' => $password,
        'confirm'  => $password,
    ]);

    if (!$validation['valid']) {
        $msg = implode(' ', $validation['errors']); $msg_type = 'danger';
    } elseif (email_exists($pdo, $email)) {
        $msg = 'A client with that email already exists.'; $msg_type = 'danger';
    } else {
        $result = register_user($pdo, $name, $email, $password, $company);
        $msg      = $result['success'] ? 'Client added successfully.' : $result['message'];
        $msg_type = $result['success'] ? 'success' : 'danger';
    }
}

// ── Handle delete ─────────────────────────────────────────────────────────────
if (query_param('action') === 'delete') {
    $del_id = input_int($_GET, 'id');
    if ($del_id > 0 && $del_id !== $user_id) {
        db_delete($pdo, 'users', $del_id);
        $msg = 'Client removed.';
    }
}

// ── Fetch all clients ─────────────────────────────────────────────────────────
$clients = db_query($pdo,
    "SELECT u.*, COUNT(c.id) AS campaign_count
     FROM users u
     LEFT JOIN campaigns c ON c.client_id = u.id
     WHERE u.role = 'client'
     GROUP BY u.id
     ORDER BY u.created_at DESC"
);
?>

<?= render_sidebar_overlay() ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<div class="adhub-content-wrapper" id="contentWrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="adhub-main">

        <?= render_page_header(
            'Clients',
            'Manage all client accounts.',
            '<button class="btn btn-adhub-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
                <i class="bi bi-person-plus me-1"></i> Add Client
             </button>'
        ) ?>

        <?php if ($msg): ?>
            <?= render_alert($msg, $msg_type) ?>
        <?php endif; ?>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <?= render_stat_card('bi-people', 'indigo', (string)count($clients), 'Total Clients') ?>
            </div>
            <div class="col-sm-4">
                <?= render_stat_card('bi-megaphone', 'green',
                    (string)array_sum(array_column($clients, 'campaign_count')), 'Total Campaigns') ?>
            </div>
            <div class="col-sm-4">
                <?= render_stat_card('bi-person-check', 'cyan',
                    (string)count(array_filter($clients, fn($c) => $c['campaign_count'] > 0)),
                    'Active Clients') ?>
            </div>
        </div>

        <!-- Clients table -->
        <div class="adhub-card">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-people me-2 text-primary"></i>All Clients</h5>
                <input type="text" class="form-control form-control-sm" style="width:220px;"
                       placeholder="Search clients…" data-filter-table="#clientsTable">
            </div>
            <div class="card-body p-0">
                <?php if (empty($clients)): ?>
                    <?= render_empty_state('people', 'No clients yet. Add your first client.') ?>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="adhub-table" id="clientsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Client</th>
                                <th>Company</th>
                                <th>Email</th>
                                <th>Campaigns</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $i => $c): ?>
                            <tr>
                                <td class="text-muted small"><?= $i + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="adhub-avatar" style="width:32px;height:32px;font-size:.75rem;">
                                            <?= avatar_initial($c['name']) ?>
                                        </div>
                                        <span class="fw-600"><?= e($c['name']) ?></span>
                                    </div>
                                </td>
                                <td class="text-muted small"><?= e($c['company'] ?: '—') ?></td>
                                <td class="small"><?= e($c['email']) ?></td>
                                <td>
                                    <span class="adhub-badge <?= $c['campaign_count'] > 0 ? 'badge-active' : 'badge-draft' ?>">
                                        <?= $c['campaign_count'] ?> campaign<?= $c['campaign_count'] != 1 ? 's' : '' ?>
                                    </span>
                                </td>
                                <td class="text-muted small"><?= format_date($c['created_at']) ?></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="/adhub/admin/campaigns.php?client=<?= $c['id'] ?>"
                                           class="btn btn-sm btn-outline-primary" title="View Campaigns">
                                            <i class="bi bi-megaphone"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-outline-danger"
                                           data-confirm-delete
                                           data-action="/adhub/admin/clients.php?action=delete&id=<?= $c['id'] ?>"
                                           data-label="<?= e($c['name']) ?>"
                                           title="Remove Client">
                                            <i class="bi bi-trash"></i>
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

    </main>

    <?= render_toast_container() ?>
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</div>

<!-- Add Client Modal -->
<div class="modal fade" id="addClientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:var(--ah-radius);">
            <form method="POST" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-700">Add New Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="adhub-label">Full Name *</label>
                            <input type="text" name="name" class="form-control" required placeholder="Jane Smith">
                        </div>
                        <div class="col-12">
                            <label class="adhub-label">Email *</label>
                            <input type="email" name="email" class="form-control" required placeholder="jane@company.com">
                        </div>
                        <div class="col-12">
                            <label class="adhub-label">Company</label>
                            <input type="text" name="company" class="form-control" placeholder="Acme Corp">
                        </div>
                        <div class="col-12">
                            <label class="adhub-label">Temporary Password</label>
                            <input type="text" name="password" class="form-control" value="password" required>
                            <div class="form-text">Client should change this after first login.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-adhub-primary">
                        <i class="bi bi-person-plus me-1"></i> Add Client
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= render_confirm_delete_modal() ?>
