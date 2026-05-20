<?php
/**
 * AdHub - Root Entry Point
 * Redirects to the appropriate dashboard based on login state and role.
 */

session_start();

if (!empty($_SESSION['user_id'])) {
    // Already logged in — send to correct dashboard
    if ($_SESSION['role'] === 'admin') {
        header('Location: /adhub/admin/dashboard.php');
    } else {
        header('Location: /adhub/client/dashboard.php');
    }
} else {
    // Not logged in — go to login
    header('Location: /adhub/auth/login.php');
}
exit;
