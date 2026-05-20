<?php
/**
 * AdHub — functions.php
 * =====================================================================
 * Central library of ALL reusable functions for the application.
 * Included by header.php so every page automatically has access.
 *
 * Sections:
 *  1.  Auth & Session
 *  2.  Database Helpers
 *  3.  Campaign Functions
 *  4.  User / Client Functions
 *  5.  Asset Functions
 *  6.  Approval Functions
 *  7.  Report Functions
 *  8.  UI / HTML Helpers
 *  9.  Validation Helpers
 *  10. Utility Helpers
 * =====================================================================
 */


/* ===================================================================
   1. AUTH & SESSION
   =================================================================== */

/**
 * Check if a user is currently logged in.
 *
 * @return bool
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Get the current user's role.
 *
 * @return string  'admin' | 'client' | ''
 */
function current_role(): string
{
    return $_SESSION['role'] ?? '';
}

/**
 * Get the current user's ID.
 *
 * @return int|null
 */
function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/**
 * Get the current user's display name.
 *
 * @return string
 */
function current_user_name(): string
{
    return $_SESSION['user_name'] ?? 'Guest';
}

/**
 * Require the user to be logged in. Redirect to login if not.
 *
 * @param string $redirect  URL to redirect to after login (optional)
 * @return void
 */
function require_login(string $redirect = '/adhub/auth/login.php'): void
{
    if (!is_logged_in()) {
        header("Location: $redirect");
        exit;
    }
}

/**
 * Require a specific role. Redirect with error if role does not match.
 *
 * @param string $role  'admin' or 'client'
 * @return void
 */
function require_role(string $role): void
{
    require_login();
    if (current_role() !== $role) {
        header('Location: /adhub/auth/login.php?error=unauthorized');
        exit;
    }
}

/**
 * Destroy the session and redirect to login.
 *
 * @return void
 */
function logout(): void
{
    session_unset();
    session_destroy();

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    header('Location: /adhub/auth/login.php');
    exit;
}

/**
 * Attempt to log in a user with email + password.
 * Sets session variables on success.
 *
 * @param PDO    $pdo
 * @param string $email
 * @param string $password  Plain-text password
 * @return array|false  User row on success, false on failure
 */
function attempt_login(PDO $pdo, string $email, string $password): array|false
{
    $stmt = $pdo->prepare(
        "SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1"
    );
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['role']      = $user['role'];
        return $user;
    }

    return false;
}

/**
 * Register a new client user.
 *
 * @param PDO    $pdo
 * @param string $name
 * @param string $email
 * @param string $password  Plain-text password (will be hashed)
 * @param string $company
 * @return array  ['success' => bool, 'message' => string]
 */
function register_user(PDO $pdo, string $name, string $email, string $password, string $company = ''): array
{
    // Check duplicate
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $check->execute([trim($email)]);
    if ($check->fetch()) {
        return ['success' => false, 'message' => 'An account with that email already exists.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        "INSERT INTO users (name, email, password, role, company) VALUES (?, ?, ?, 'client', ?)"
    );
    $stmt->execute([trim($name), trim($email), $hash, trim($company)]);

    return ['success' => true, 'message' => 'Account created successfully.'];
}


/* ===================================================================
   2. DATABASE HELPERS
   =================================================================== */

/**
 * Fetch a single row by table and ID.
 *
 * @param PDO    $pdo
 * @param string $table
 * @param int    $id
 * @return array|false
 */
function db_find(PDO $pdo, string $table, int $id): array|false
{
    $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Delete a single row by table and ID.
 *
 * @param PDO    $pdo
 * @param string $table
 * @param int    $id
 * @return bool
 */
function db_delete(PDO $pdo, string $table, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Count rows in a table with an optional WHERE clause.
 *
 * @param PDO    $pdo
 * @param string $table
 * @param string $where   e.g. "status = 'active'"
 * @param array  $params  Bound parameters for prepared WHERE
 * @return int
 */
function db_count(PDO $pdo, string $table, string $where = '', array $params = []): int
{
    $sql  = "SELECT COUNT(*) FROM `$table`";
    $sql .= $where ? " WHERE $where" : '';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/**
 * Run a raw SELECT query and return all rows.
 *
 * @param PDO    $pdo
 * @param string $sql
 * @param array  $params
 * @return array
 */
function db_query(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Run a raw SELECT query and return a single row.
 *
 * @param PDO    $pdo
 * @param string $sql
 * @param array  $params
 * @return array|false
 */
function db_query_one(PDO $pdo, string $sql, array $params = []): array|false
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

/**
 * Run an INSERT and return the last insert ID.
 *
 * @param PDO    $pdo
 * @param string $table
 * @param array  $data   Associative array of column => value
 * @return int  Last inserted ID
 */
function db_insert(PDO $pdo, string $table, array $data): int
{
    $cols        = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    $stmt = $pdo->prepare("INSERT INTO `$table` ($cols) VALUES ($placeholders)");
    $stmt->execute(array_values($data));
    return (int) $pdo->lastInsertId();
}

/**
 * Run an UPDATE by ID.
 *
 * @param PDO    $pdo
 * @param string $table
 * @param array  $data   Associative array of column => value
 * @param int    $id
 * @return bool
 */
function db_update(PDO $pdo, string $table, array $data, int $id): bool
{
    $sets = implode(', ', array_map(fn($col) => "`$col` = ?", array_keys($data)));
    $stmt = $pdo->prepare("UPDATE `$table` SET $sets WHERE id = ?");
    return $stmt->execute([...array_values($data), $id]);
}


/* ===================================================================
   3. CAMPAIGN FUNCTIONS
   =================================================================== */

/**
 * Fetch a single campaign by ID.
 *
 * @param PDO $pdo
 * @param int $id
 * @return array|false
 */
function get_campaign(PDO $pdo, int $id): array|false
{
    return db_query_one($pdo,
        "SELECT c.*, u.name AS client_name
         FROM campaigns c
         JOIN users u ON c.client_id = u.id
         WHERE c.id = ?",
        [$id]
    );
}

/**
 * Fetch all campaigns, optionally filtered by status and/or client.
 *
 * @param PDO         $pdo
 * @param string|null $status    Filter by status (null = all)
 * @param int|null    $client_id Filter by client ID (null = all)
 * @return array
 */
function get_campaigns(PDO $pdo, ?string $status = null, ?int $client_id = null): array
{
    $where  = [];
    $params = [];

    if ($status && $status !== 'all') {
        $where[]  = 'c.status = ?';
        $params[] = $status;
    }
    if ($client_id) {
        $where[]  = 'c.client_id = ?';
        $params[] = $client_id;
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    return db_query($pdo,
        "SELECT c.*, u.name AS client_name
         FROM campaigns c
         JOIN users u ON c.client_id = u.id
         $whereSQL
         ORDER BY c.created_at DESC",
        $params
    );
}

/**
 * Create a new campaign.
 *
 * @param PDO   $pdo
 * @param array $data  Keys: title, description, client_id, status, budget, progress, start_date, end_date, created_by
 * @return int  New campaign ID
 */
function create_campaign(PDO $pdo, array $data): int
{
    return db_insert($pdo, 'campaigns', [
        'title'       => $data['title'],
        'description' => $data['description'] ?? '',
        'client_id'   => $data['client_id'],
        'status'      => $data['status']     ?? 'draft',
        'budget'      => $data['budget']     ?? 0,
        'progress'    => $data['progress']   ?? 0,
        'start_date'  => $data['start_date'] ?: null,
        'end_date'    => $data['end_date']   ?: null,
        'created_by'  => $data['created_by'],
    ]);
}

/**
 * Update an existing campaign.
 *
 * @param PDO   $pdo
 * @param int   $id
 * @param array $data
 * @return bool
 */
function update_campaign(PDO $pdo, int $id, array $data): bool
{
    return db_update($pdo, 'campaigns', [
        'title'       => $data['title'],
        'description' => $data['description'] ?? '',
        'client_id'   => $data['client_id'],
        'status'      => $data['status']     ?? 'draft',
        'budget'      => $data['budget']     ?? 0,
        'progress'    => $data['progress']   ?? 0,
        'start_date'  => $data['start_date'] ?: null,
        'end_date'    => $data['end_date']   ?: null,
        'updated_at'  => date('Y-m-d H:i:s'),
    ], $id);
}

/**
 * Delete a campaign and its related assets from disk + DB.
 *
 * @param PDO    $pdo
 * @param int    $id
 * @param string $upload_dir  Absolute path to uploads folder
 * @return bool
 */
function delete_campaign(PDO $pdo, int $id, string $upload_dir): bool
{
    // Remove physical asset files first
    $assets = get_assets_by_campaign($pdo, $id);
    foreach ($assets as $asset) {
        $file = rtrim($upload_dir, '/') . '/' . $asset['filename'];
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    return db_delete($pdo, 'campaigns', $id);
}

/**
 * Get campaign statistics for the admin dashboard.
 *
 * @param PDO $pdo
 * @return array  Keys: total, active, paused, completed, draft, review
 */
function get_campaign_stats(PDO $pdo): array
{
    $rows = db_query($pdo,
        "SELECT status, COUNT(*) AS total FROM campaigns GROUP BY status"
    );

    $stats = ['total' => 0, 'active' => 0, 'paused' => 0, 'completed' => 0, 'draft' => 0, 'review' => 0];
    foreach ($rows as $row) {
        $stats[$row['status']] = (int) $row['total'];
        $stats['total'] += (int) $row['total'];
    }
    return $stats;
}

/**
 * Validate campaign POST data.
 *
 * @param array $data  Raw POST fields
 * @return array  ['valid' => bool, 'errors' => string[]]
 */
function validate_campaign_data(array $data): array
{
    $errors = [];

    if (empty(trim($data['title'] ?? ''))) {
        $errors[] = 'Campaign title is required.';
    }
    if (empty($data['client_id']) || (int)$data['client_id'] < 1) {
        $errors[] = 'Please select a client.';
    }
    if (!empty($data['start_date']) && !empty($data['end_date'])) {
        if ($data['end_date'] < $data['start_date']) {
            $errors[] = 'End date must be after start date.';
        }
    }
    if (isset($data['budget']) && (float)$data['budget'] < 0) {
        $errors[] = 'Budget cannot be negative.';
    }

    return ['valid' => empty($errors), 'errors' => $errors];
}


/* ===================================================================
   4. USER / CLIENT FUNCTIONS
   =================================================================== */

/**
 * Fetch a single user by ID.
 *
 * @param PDO $pdo
 * @param int $id
 * @return array|false
 */
function get_user(PDO $pdo, int $id): array|false
{
    return db_find($pdo, 'users', $id);
}

/**
 * Fetch all users, optionally filtered by role.
 *
 * @param PDO         $pdo
 * @param string|null $role  'admin' | 'client' | null for all
 * @return array
 */
function get_users(PDO $pdo, ?string $role = null): array
{
    if ($role) {
        return db_query($pdo,
            "SELECT * FROM users WHERE role = ? ORDER BY name",
            [$role]
        );
    }
    return db_query($pdo, "SELECT * FROM users ORDER BY name");
}

/**
 * Fetch all clients (role = 'client') for dropdown lists.
 *
 * @param PDO $pdo
 * @return array
 */
function get_clients(PDO $pdo): array
{
    return get_users($pdo, 'client');
}

/**
 * Check if an email address is already registered.
 *
 * @param PDO    $pdo
 * @param string $email
 * @return bool
 */
function email_exists(PDO $pdo, string $email): bool
{
    return db_count($pdo, 'users', 'email = ?', [trim($email)]) > 0;
}

/**
 * Get total count of clients.
 *
 * @param PDO $pdo
 * @return int
 */
function count_clients(PDO $pdo): int
{
    return db_count($pdo, 'users', "role = 'client'");
}


/* ===================================================================
   5. ASSET FUNCTIONS
   =================================================================== */

/**
 * Allowed MIME types for asset uploads.
 *
 * @return string[]
 */
function allowed_mime_types(): array
{
    return [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'video/mp4', 'video/quicktime',
        'application/zip',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
}

/**
 * Max allowed upload file size in bytes (10 MB).
 *
 * @return int
 */
function max_upload_size(): int
{
    return 10 * 1024 * 1024;
}

/**
 * Handle a file upload for a campaign asset.
 *
 * @param PDO    $pdo
 * @param array  $file         $_FILES['asset_file']
 * @param int    $campaign_id
 * @param int    $uploaded_by  User ID
 * @param string $upload_dir   Absolute path to /uploads/
 * @return array  ['success' => bool, 'message' => string]
 */
function upload_asset(PDO $pdo, array $file, int $campaign_id, int $uploaded_by, string $upload_dir): array
{
    if ($campaign_id < 1) {
        return ['success' => false, 'message' => 'Please select a campaign.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error. Please try again.'];
    }
    if ($file['size'] > max_upload_size()) {
        return ['success' => false, 'message' => 'File too large. Maximum size is 10 MB.'];
    }
    if (!in_array($file['type'], allowed_mime_types())) {
        return ['success' => false, 'message' => 'File type not allowed.'];
    }

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $safe     = uniqid('asset_', true) . '.' . $ext;
    $dest     = rtrim($upload_dir, '/') . '/' . $safe;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => false, 'message' => 'Failed to save file. Check upload directory permissions.'];
    }

    db_insert($pdo, 'campaign_assets', [
        'campaign_id'   => $campaign_id,
        'filename'      => $safe,
        'original_name' => $file['name'],
        'file_type'     => $file['type'],
        'file_size'     => $file['size'],
        'uploaded_by'   => $uploaded_by,
    ]);

    return ['success' => true, 'message' => 'Asset uploaded successfully.'];
}

/**
 * Delete an asset file from disk and database.
 *
 * @param PDO    $pdo
 * @param int    $id
 * @param string $upload_dir
 * @return bool
 */
function delete_asset(PDO $pdo, int $id, string $upload_dir): bool
{
    $asset = db_find($pdo, 'campaign_assets', $id);
    if (!$asset) {
        return false;
    }

    $path = rtrim($upload_dir, '/') . '/' . $asset['filename'];
    if (file_exists($path)) {
        @unlink($path);
    }

    return db_delete($pdo, 'campaign_assets', $id);
}

/**
 * Get all assets belonging to a campaign.
 *
 * @param PDO $pdo
 * @param int $campaign_id
 * @return array
 */
function get_assets_by_campaign(PDO $pdo, int $campaign_id): array
{
    return db_query($pdo,
        "SELECT * FROM campaign_assets WHERE campaign_id = ? ORDER BY uploaded_at DESC",
        [$campaign_id]
    );
}

/**
 * Get all assets with campaign + uploader info (admin view).
 *
 * @param PDO $pdo
 * @return array
 */
function get_all_assets(PDO $pdo): array
{
    return db_query($pdo,
        "SELECT ca.*, c.title AS campaign_title, u.name AS uploader
         FROM campaign_assets ca
         JOIN campaigns c ON ca.campaign_id = c.id
         JOIN users u ON ca.uploaded_by = u.id
         ORDER BY ca.uploaded_at DESC"
    );
}

/**
 * Return a Bootstrap Icon class + color for a given MIME type.
 *
 * @param string $mime
 * @return string  e.g. 'bi-file-image text-success'
 */
function file_type_icon(string $mime): string
{
    if (str_starts_with($mime, 'image/'))  return 'bi-file-image text-success';
    if ($mime === 'application/pdf')       return 'bi-file-pdf text-danger';
    if (str_starts_with($mime, 'video/')) return 'bi-file-play text-info';
    if (str_contains($mime, 'word'))       return 'bi-file-word text-primary';
    if (str_contains($mime, 'zip'))        return 'bi-file-zip text-warning';
    return 'bi-file-earmark text-secondary';
}

/**
 * Format bytes into a human-readable size string.
 *
 * @param int $bytes
 * @return string  e.g. '4.2 MB'
 */
function format_file_size(int $bytes): string
{
    if ($bytes < 1024)    return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}


/* ===================================================================
   6. APPROVAL FUNCTIONS
   =================================================================== */

/**
 * Fetch all approvals for a specific client.
 *
 * @param PDO $pdo
 * @param int $client_id
 * @return array
 */
function get_client_approvals(PDO $pdo, int $client_id): array
{
    return db_query($pdo,
        "SELECT a.id, a.status, a.notes, a.created_at, a.reviewed_at,
                c.id AS campaign_id, c.title AS campaign_title,
                c.status AS campaign_status, c.progress, c.description
         FROM approvals a
         JOIN campaigns c ON a.campaign_id = c.id
         WHERE a.client_id = ?
         ORDER BY FIELD(a.status,'pending','revision','approved'), a.created_at DESC",
        [$client_id]
    );
}

/**
 * Fetch all approvals (admin view) with campaign + client info.
 *
 * @param PDO $pdo
 * @return array
 */
function get_all_approvals(PDO $pdo): array
{
    return db_query($pdo,
        "SELECT a.id, a.status, a.created_at, a.notes,
                c.title AS campaign_title, u.name AS client_name
         FROM approvals a
         JOIN campaigns c ON a.campaign_id = c.id
         JOIN users u ON a.client_id = u.id
         ORDER BY a.created_at DESC"
    );
}

/**
 * Count pending approvals, optionally scoped to a client.
 *
 * @param PDO      $pdo
 * @param int|null $client_id  null = all clients
 * @return int
 */
function count_pending_approvals(PDO $pdo, ?int $client_id = null): int
{
    if ($client_id) {
        return db_count($pdo, 'approvals', "status = 'pending' AND client_id = ?", [$client_id]);
    }
    return db_count($pdo, 'approvals', "status = 'pending'");
}

/**
 * Create a new approval request for a campaign.
 *
 * @param PDO      $pdo
 * @param int      $campaign_id
 * @param int      $client_id
 * @param int|null $asset_id
 * @return int  New approval ID
 */
function create_approval(PDO $pdo, int $campaign_id, int $client_id, ?int $asset_id = null): int
{
    return db_insert($pdo, 'approvals', [
        'campaign_id' => $campaign_id,
        'client_id'   => $client_id,
        'asset_id'    => $asset_id,
        'status'      => 'pending',
    ]);
}

/**
 * Update an approval status (approve or request revision).
 * Verifies ownership by client_id before updating.
 *
 * @param PDO    $pdo
 * @param int    $approval_id
 * @param int    $client_id    Must match the approval's client_id
 * @param string $status       'approved' | 'revision'
 * @param string $notes        Client feedback / notes
 * @return array  ['success' => bool, 'message' => string]
 */
function respond_to_approval(PDO $pdo, int $approval_id, int $client_id, string $status, string $notes = ''): array
{
    $allowed = ['approved', 'revision'];
    if (!in_array($status, $allowed)) {
        return ['success' => false, 'message' => 'Invalid approval action.'];
    }

    // Ownership check
    $approval = db_query_one($pdo,
        "SELECT id FROM approvals WHERE id = ? AND client_id = ?",
        [$approval_id, $client_id]
    );

    if (!$approval) {
        return ['success' => false, 'message' => 'Approval not found or access denied.'];
    }

    $stmt = $pdo->prepare(
        "UPDATE approvals SET status = ?, notes = ?, reviewed_at = NOW() WHERE id = ? AND client_id = ?"
    );
    $stmt->execute([$status, $notes, $approval_id, $client_id]);

    $msg = $status === 'approved'
        ? 'Campaign approved successfully!'
        : 'Revision request submitted. The agency will be notified.';

    return ['success' => true, 'message' => $msg];
}

/**
 * Group an array of approval rows by their status.
 *
 * @param array $approvals
 * @return array  ['pending' => [...], 'revision' => [...], 'approved' => [...]]
 */
function group_approvals_by_status(array $approvals): array
{
    $grouped = ['pending' => [], 'revision' => [], 'approved' => []];
    foreach ($approvals as $a) {
        $grouped[$a['status']][] = $a;
    }
    return $grouped;
}


/* ===================================================================
   7. REPORT FUNCTIONS
   =================================================================== */

/**
 * Fetch all report entries with campaign title.
 *
 * @param PDO $pdo
 * @return array
 */
function get_all_reports(PDO $pdo): array
{
    return db_query($pdo,
        "SELECT r.*, c.title AS campaign_title,
                CASE WHEN r.impressions > 0 THEN ROUND(r.clicks / r.impressions * 100, 2) ELSE 0 END AS ctr,
                CASE WHEN r.clicks > 0 THEN ROUND(r.conversions / r.clicks * 100, 2) ELSE 0 END AS cvr
         FROM reports r
         JOIN campaigns c ON r.campaign_id = c.id
         ORDER BY r.report_date DESC"
    );
}

/**
 * Get aggregate totals across all report entries.
 *
 * @param PDO $pdo
 * @return array  Keys: imp, clk, cvt, spd
 */
function get_report_totals(PDO $pdo): array
{
    $row = db_query_one($pdo,
        "SELECT SUM(impressions) AS imp, SUM(clicks) AS clk,
                SUM(conversions) AS cvt, SUM(spend) AS spd
         FROM reports"
    );
    return $row ?: ['imp' => 0, 'clk' => 0, 'cvt' => 0, 'spd' => 0];
}

/**
 * Insert a new report entry.
 *
 * @param PDO   $pdo
 * @param array $data  Keys: campaign_id, impressions, clicks, conversions, spend, report_date
 * @return array  ['success' => bool, 'message' => string]
 */
function save_report(PDO $pdo, array $data): array
{
    $campaign_id = intval($data['campaign_id'] ?? 0);
    if ($campaign_id < 1) {
        return ['success' => false, 'message' => 'Please select a campaign.'];
    }

    db_insert($pdo, 'reports', [
        'campaign_id'  => $campaign_id,
        'impressions'  => intval($data['impressions']  ?? 0),
        'clicks'       => intval($data['clicks']       ?? 0),
        'conversions'  => intval($data['conversions']  ?? 0),
        'spend'        => floatval($data['spend']      ?? 0),
        'report_date'  => $data['report_date'] ?? date('Y-m-d'),
    ]);

    return ['success' => true, 'message' => 'Report saved successfully.'];
}

/**
 * Get monthly aggregated report data for charting (last N months).
 *
 * @param PDO $pdo
 * @param int $months  Number of months to look back
 * @return array
 */
function get_monthly_report_data(PDO $pdo, int $months = 6): array
{
    return db_query($pdo,
        "SELECT DATE_FORMAT(report_date,'%b %Y') AS month,
                SUM(impressions) AS imp,
                SUM(clicks) AS clk,
                SUM(spend) AS spd
         FROM reports
         WHERE report_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
         GROUP BY DATE_FORMAT(report_date,'%Y-%m')
         ORDER BY report_date",
        [$months]
    );
}

/**
 * Get top N campaigns by total impressions.
 *
 * @param PDO $pdo
 * @param int $limit
 * @return array
 */
function get_top_campaigns_by_impressions(PDO $pdo, int $limit = 5): array
{
    return db_query($pdo,
        "SELECT c.title, SUM(r.impressions) AS imp, SUM(r.clicks) AS clk, SUM(r.spend) AS spd
         FROM reports r
         JOIN campaigns c ON r.campaign_id = c.id
         GROUP BY c.id, c.title
         ORDER BY imp DESC
         LIMIT ?",
        [$limit]
    );
}

/**
 * Get campaign status distribution for the donut chart.
 *
 * @param PDO $pdo
 * @return array  Assoc: status => count
 */
function get_status_distribution(PDO $pdo): array
{
    $rows   = db_query($pdo, "SELECT status, COUNT(*) AS total FROM campaigns GROUP BY status");
    $result = [];
    foreach ($rows as $row) {
        $result[$row['status']] = (int) $row['total'];
    }
    return $result;
}

/**
 * Get total ad spend from all reports.
 *
 * @param PDO $pdo
 * @return float
 */
function get_total_spend(PDO $pdo): float
{
    $val = $pdo->query("SELECT COALESCE(SUM(spend),0) FROM reports")->fetchColumn();
    return (float) $val;
}


/* ===================================================================
   8. UI / HTML HELPERS
   =================================================================== */

/**
 * Return the CSS badge class for a given campaign/approval status.
 *
 * @param string $status
 * @return string  CSS class name
 */
function status_badge_class(string $status): string
{
    $map = [
        'active'    => 'badge-active',
        'draft'     => 'badge-draft',
        'review'    => 'badge-review',
        'completed' => 'badge-completed',
        'paused'    => 'badge-paused',
        'pending'   => 'badge-pending',
        'approved'  => 'badge-approved',
        'revision'  => 'badge-revision',
    ];
    return $map[$status] ?? 'badge-draft';
}

/**
 * Return the Bootstrap Icon class for a given approval status.
 *
 * @param string $status
 * @return string  e.g. 'bi-check-circle-fill'
 */
function approval_icon(string $status): string
{
    $map = [
        'pending'  => 'bi-hourglass-split',
        'approved' => 'bi-check-circle-fill',
        'revision' => 'bi-arrow-repeat',
    ];
    return $map[$status] ?? 'bi-circle';
}

/**
 * Render a status badge span element.
 *
 * @param string $status
 * @return string  HTML string
 */
function render_badge(string $status): string
{
    $class = status_badge_class($status);
    $label = ucfirst($status);
    return "<span class=\"adhub-badge $class\">$label</span>";
}

/**
 * Render an animated progress bar HTML block.
 *
 * @param int $percent  0–100
 * @return string  HTML string
 */
function render_progress_bar(int $percent): string
{
    $percent = max(0, min(100, $percent));
    return "
        <div class=\"d-flex align-items-center gap-2\">
            <div class=\"adhub-progress flex-grow-1\">
                <div class=\"adhub-progress-bar\" data-progress=\"$percent\"></div>
            </div>
            <span class=\"small text-muted\">{$percent}%</span>
        </div>";
}

/**
 * Render an alert/flash message div.
 *
 * @param string $message
 * @param string $type     'success' | 'danger' | 'warning' | 'info'
 * @param bool   $dismissible
 * @return string  HTML string
 */
function render_alert(string $message, string $type = 'success', bool $dismissible = true): string
{
    $icons = [
        'success' => 'check-circle-fill',
        'danger'  => 'x-circle-fill',
        'warning' => 'exclamation-triangle-fill',
        'info'    => 'info-circle-fill',
    ];
    $icon    = $icons[$type] ?? 'info-circle-fill';
    $dismiss = $dismissible
        ? '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
        : '';
    $safe    = htmlspecialchars($message);

    return "
        <div class=\"alert alert-{$type} alert-dismissible alert-auto-dismiss d-flex align-items-center gap-2 mb-3\" role=\"alert\">
            <i class=\"bi bi-{$icon}\"></i>
            <div>{$safe}</div>
            {$dismiss}
        </div>";
}

/**
 * Render the page header section (title + subtitle + optional action button).
 *
 * @param string $title
 * @param string $subtitle
 * @param string $action_html  Optional right-side HTML (e.g. a button)
 * @return string  HTML string
 */
function render_page_header(string $title, string $subtitle = '', string $action_html = ''): string
{
    $subtitle_html = $subtitle
        ? "<p class=\"page-subtitle\">" . htmlspecialchars($subtitle) . "</p>"
        : '';
    return "
        <div class=\"page-header\">
            <div>
                <h1 class=\"page-title\">" . htmlspecialchars($title) . "</h1>
                {$subtitle_html}
            </div>
            {$action_html}
        </div>";
}

/**
 * Render an empty-state block for tables / lists.
 *
 * @param string $icon     Bootstrap icon name e.g. 'inbox'
 * @param string $message
 * @return string  HTML string
 */
function render_empty_state(string $icon = 'inbox', string $message = 'No records found.'): string
{
    $safe = htmlspecialchars($message);
    return "
        <div class=\"text-center py-5 text-muted\">
            <i class=\"bi bi-{$icon} fs-1 d-block mb-3 opacity-25\"></i>
            <p class=\"mb-0\">{$safe}</p>
        </div>";
}

/**
 * Render a stat card HTML block.
 *
 * @param string $icon       Bootstrap icon class e.g. 'bi-megaphone'
 * @param string $color      CSS color modifier: 'indigo','cyan','green','amber','red','blue'
 * @param string $value      Main display value
 * @param string $label      Label below value
 * @param string $change_html Optional change indicator HTML
 * @return string  HTML
 */
function render_stat_card(string $icon, string $color, string $value, string $label, string $change_html = ''): string
{
    return "
        <div class=\"stat-card\">
            <div class=\"stat-icon {$color}\"><i class=\"bi {$icon}\"></i></div>
            <div>
                <div class=\"stat-value\">" . htmlspecialchars($value) . "</div>
                <div class=\"stat-label\">" . htmlspecialchars($label) . "</div>
                {$change_html}
            </div>
        </div>";
}

/**
 * Render the sidebar overlay div (required for mobile).
 *
 * @return string  HTML
 */
function render_sidebar_overlay(): string
{
    return '<div class="sidebar-overlay" id="sidebarOverlay"></div>';
}

/**
 * Render the toast container div.
 *
 * @return string  HTML
 */
function render_toast_container(): string
{
    return '<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>';
}

/**
 * Render a standard confirm-delete modal.
 *
 * @return string  HTML
 */
function render_confirm_delete_modal(): string
{
    return '
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border-radius:var(--ah-radius);">
                <div class="modal-header border-0">
                    <h6 class="modal-title" id="confirmDeleteLabel">Delete item?</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0 text-muted small">This action cannot be undone.</div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>';
}

/**
 * Build a <select> options HTML string from an array of users.
 *
 * @param array    $users       Array of user rows (id, name, company)
 * @param int|null $selected_id Currently selected ID
 * @return string  HTML <option> tags
 */
function render_client_options(array $users, ?int $selected_id = null): string
{
    $html = '<option value="">— Select client —</option>';
    foreach ($users as $u) {
        $sel     = ($selected_id && (int)$u['id'] === $selected_id) ? ' selected' : '';
        $company = !empty($u['company']) ? ' (' . htmlspecialchars($u['company']) . ')' : '';
        $html   .= "<option value=\"{$u['id']}\"{$sel}>" . htmlspecialchars($u['name']) . $company . "</option>";
    }
    return $html;
}

/**
 * Build campaign status <option> tags.
 *
 * @param string $selected  Currently selected status
 * @return string  HTML <option> tags
 */
function render_status_options(string $selected = 'draft'): string
{
    $statuses = ['draft', 'active', 'review', 'completed', 'paused'];
    $html = '';
    foreach ($statuses as $s) {
        $sel   = ($s === $selected) ? ' selected' : '';
        $html .= "<option value=\"{$s}\"{$sel}>" . ucfirst($s) . "</option>";
    }
    return $html;
}


/* ===================================================================
   9. VALIDATION HELPERS
   =================================================================== */

/**
 * Sanitize and return a trimmed string from POST/GET, or default.
 *
 * @param array  $source  $_POST or $_GET
 * @param string $key
 * @param string $default
 * @return string
 */
function input_str(array $source, string $key, string $default = ''): string
{
    return trim($source[$key] ?? $default);
}

/**
 * Return a sanitized integer from POST/GET.
 *
 * @param array  $source
 * @param string $key
 * @param int    $default
 * @return int
 */
function input_int(array $source, string $key, int $default = 0): int
{
    return intval($source[$key] ?? $default);
}

/**
 * Return a sanitized float from POST/GET.
 *
 * @param array  $source
 * @param string $key
 * @param float  $default
 * @return float
 */
function input_float(array $source, string $key, float $default = 0.0): float
{
    return floatval($source[$key] ?? $default);
}

/**
 * Validate registration form fields.
 *
 * @param array $data  POST fields: name, email, password, confirm
 * @return array  ['valid' => bool, 'errors' => string[]]
 */
function validate_registration(array $data): array
{
    $errors = [];

    if (empty(trim($data['name'] ?? ''))) {
        $errors[] = 'Full name is required.';
    }
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if (empty($data['password']) || strlen($data['password']) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if (($data['password'] ?? '') !== ($data['confirm'] ?? '')) {
        $errors[] = 'Passwords do not match.';
    }

    return ['valid' => empty($errors), 'errors' => $errors];
}

/**
 * Generate a CSRF token and store it in session.
 *
 * @return string
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a submitted CSRF token.
 *
 * @param string $token
 * @return bool
 */
function verify_csrf(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Return an HTML hidden input with the current CSRF token.
 *
 * @return string  HTML
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}


/* ===================================================================
   10. UTILITY HELPERS
   =================================================================== */

/**
 * Format a date string for display.
 *
 * @param string|null $date    MySQL date string
 * @param string      $format  PHP date format
 * @param string      $empty   Value to return when date is null/empty
 * @return string
 */
function format_date(?string $date, string $format = 'M d, Y', string $empty = '—'): string
{
    if (empty($date)) return $empty;
    return date($format, strtotime($date));
}

/**
 * Format a number as currency.
 *
 * @param float  $amount
 * @param string $symbol
 * @param int    $decimals
 * @return string  e.g. '$1,500.00'
 */
function format_currency(float $amount, string $symbol = '$', int $decimals = 2): string
{
    return $symbol . number_format($amount, $decimals);
}

/**
 * Truncate a string to a maximum length, appending an ellipsis if cut.
 *
 * @param string $str
 * @param int    $max
 * @param string $suffix
 * @return string
 */
function truncate_str(string $str, int $max = 80, string $suffix = '…'): string
{
    return mb_strlen($str) > $max ? mb_substr($str, 0, $max) . $suffix : $str;
}

/**
 * Return the first letter of a name, uppercased (used for avatar initials).
 *
 * @param string $name
 * @return string
 */
function avatar_initial(string $name): string
{
    return strtoupper(mb_substr(trim($name), 0, 1));
}

/**
 * Redirect to a URL and stop execution.
 *
 * @param string $url
 * @return never
 */
function redirect(string $url): never
{
    header("Location: $url");
    exit;
}

/**
 * Safely output a variable as HTML-escaped string.
 *
 * @param mixed $value
 * @return string
 */
function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Return 'active' CSS class if current page matches the given filename.
 *
 * @param string $filename  e.g. 'dashboard.php'
 * @return string  'active' or ''
 */
function nav_active_class(string $filename): string
{
    return basename($_SERVER['PHP_SELF']) === $filename ? 'active' : '';
}

/**
 * Return 'active' CSS class if both page and directory match.
 *
 * @param string $filename  e.g. 'dashboard.php'
 * @param string $dir       e.g. 'admin'
 * @return string  'active' or ''
 */
function nav_active_in(string $filename, string $dir): string
{
    return (basename($_SERVER['PHP_SELF']) === $filename
        && basename(dirname($_SERVER['PHP_SELF'])) === $dir)
        ? 'active' : '';
}

/**
 * Get a URL query param safely.
 *
 * @param string $key
 * @param string $default
 * @return string
 */
function query_param(string $key, string $default = ''): string
{
    return trim($_GET[$key] ?? $default);
}

/**
 * Check if the request method is POST.
 *
 * @return bool
 */
function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}
