<?php
/**
 * AdHub - Search API
 * Returns JSON results for the global search bar.
 * Called via fetch() from script.js
 */

session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized']); exit;
}

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode(['results' => []]); exit;
}

$like    = '%' . $q . '%';
$results = [];

// ── Search campaigns ──────────────────────────────────────────────────────────
$where_client = $user_role === 'client' ? "AND c.client_id = $user_id" : '';
$campaigns = db_query($pdo,
    "SELECT c.id, c.title, c.status, u.name AS client_name
     FROM campaigns c
     JOIN users u ON c.client_id = u.id
     WHERE (c.title LIKE ? OR c.description LIKE ?) $where_client
     LIMIT 5",
    [$like, $like]
);
foreach ($campaigns as $c) {
    $results[] = [
        'type'     => 'Campaign',
        'icon'     => 'bi-megaphone',
        'color'    => 'text-primary',
        'title'    => $c['title'],
        'subtitle' => 'Client: ' . $c['client_name'] . ' · ' . ucfirst($c['status']),
        'url'      => '/adhub/' . $user_role . '/campaigns.php',
    ];
}

// ── Search clients (admin only) ───────────────────────────────────────────────
if ($user_role === 'admin') {
    $clients = db_query($pdo,
        "SELECT id, name, company, email FROM users
         WHERE role='client' AND (name LIKE ? OR company LIKE ? OR email LIKE ?)
         LIMIT 3",
        [$like, $like, $like]
    );
    foreach ($clients as $c) {
        $results[] = [
            'type'     => 'Client',
            'icon'     => 'bi-person-circle',
            'color'    => 'text-success',
            'title'    => $c['name'],
            'subtitle' => $c['company'] ? $c['company'] . ' · ' . $c['email'] : $c['email'],
            'url'      => '/adhub/admin/clients.php',
        ];
    }

    // Search assets
    $assets = db_query($pdo,
        "SELECT ca.id, ca.original_name, c.title AS campaign_title
         FROM campaign_assets ca
         JOIN campaigns c ON ca.campaign_id = c.id
         WHERE ca.original_name LIKE ?
         LIMIT 3",
        [$like]
    );
    foreach ($assets as $a) {
        $results[] = [
            'type'     => 'Asset',
            'icon'     => 'bi-file-earmark',
            'color'    => 'text-warning',
            'title'    => $a['original_name'],
            'subtitle' => 'In campaign: ' . $a['campaign_title'],
            'url'      => '/adhub/admin/assets.php',
        ];
    }
}

echo json_encode(['results' => $results, 'query' => $q]);
