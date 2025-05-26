<?php
/**
 * Logout Page
 * 
 * Handles user logout
 */

// Include session
require_once __DIR__ . '/includes/session.php';

// Check if user is logged in
if ($auth->isLoggedIn()) {
    // Log out the user
    $auth->logout();
}

// Redirect to login page with logged_out parameter
header('Location: /login.php?logged_out=1');
exit;
