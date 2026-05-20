<?php
/**
 * AdHub - Uploads Directory Index
 * Blocks direct directory browsing for security.
 * Individual files are served via direct URL only.
 */

// Redirect anyone who tries to browse /uploads/ directly
header('HTTP/1.1 403 Forbidden');
header('Location: /adhub/auth/login.php');
exit;
