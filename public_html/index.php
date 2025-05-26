<?php
/**
 * Main Index Page
 * 
 * This serves as the entry point to the application
 * It redirects users based on authentication status and role
 */

require_once __DIR__ . '/includes/session.php';

// If user is already logged in, redirect to their role-specific page
if ($auth->isLoggedIn()) {
    $user = $auth->getCurrentUser();
    $rolePage = $auth->getRoleHomepage($user['role_name']);
    
    header("Location: " . $rolePage);
    exit;
}

// If not logged in, redirect to login page
header("Location: /login.php");
exit;
