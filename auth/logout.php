<?php
/**
 * AdHub - Logout
 * Uses: logout() from functions.php
 */

session_start();
require_once __DIR__ . '/../includes/functions.php';

logout(); // Destroys session and redirects to login
