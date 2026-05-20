<?php
/**
 * AdHub - Header Include
 * Starts session, loads DB, and outputs HTML <head>
 *
 * @param string $page_title  Title shown in <title> tag
 * @param string $user_role   'admin' or 'client' — used to gate access
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

// ── Auth guard ────────────────────────────────────────────────────────────────
// Pages that need a logged-in user should call this before including header.php
// OR set $require_auth = true before including.
if (!empty($require_auth) && empty($_SESSION['user_id'])) {
    header('Location: /adhub/auth/login.php');
    exit;
}

// Role guard — set $require_role = 'admin' or 'client' before including
if (!empty($require_role) && ($_SESSION['role'] ?? '') !== $require_role) {
    header('Location: /adhub/auth/login.php?error=unauthorized');
    exit;
}

$page_title = $page_title ?? 'AdHub';
$user_name  = $_SESSION['user_name'] ?? 'Guest';
$user_role  = $_SESSION['role']      ?? 'guest';
$user_id    = $_SESSION['user_id']   ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — AdHub</title>

    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/adhub/assets/css/style.css">
</head>
<body class="adhub-body">
