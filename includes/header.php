<?php
/**
 * AdHub - Header Include
 * Starts session, loads DB + functions, outputs HTML <head>.
 *
 * Set before including this file:
 *   $page_title   (string)  — shown in <title>
 *   $require_auth (bool)    — redirect to login if not logged in
 *   $require_role (string)  — 'admin' or 'client' role gate
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load DB connection
require_once __DIR__ . '/db.php';

// Load all reusable functions
require_once __DIR__ . '/functions.php';

// ── Auth guard ────────────────────────────────────────────────────────────────
if (!empty($require_auth)) {
    require_login();
}

if (!empty($require_role)) {
    require_role($require_role);
}

// ── Convenience variables available in every page ─────────────────────────────
$page_title = $page_title ?? 'AdHub';
$user_name  = current_user_name();
$user_role  = current_role();
$user_id    = current_user_id();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> — AdHub</title>

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
