<?php
/**
 * AdHub - Root Entry Point
 * Uses: is_logged_in(), current_role(), redirect() from functions.php
 */

session_start();
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect(current_role() === 'admin'
        ? '/adhub/admin/dashboard.php'
        : '/adhub/client/dashboard.php');
} else {
    redirect('/adhub/auth/login.php');
}
