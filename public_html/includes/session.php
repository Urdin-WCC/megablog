<?php
/**
 * Session Management
 * 
 * This file should be included at the top of any page that requires authentication
 */

// Check if session has already started
if (session_status() === PHP_SESSION_NONE) {
    // Session settings - moved from config.php
    // Only set these if session not already started
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    
    // Start session
    session_start();
}

// Include configuration file
require_once __DIR__ . '/../config/config.php';

// Include required files
require_once __DIR__ . '/auth/Auth.php';

// Initialize Auth
$auth = Auth::getInstance();

// Check session timeout if user is logged in
if ($auth->isLoggedIn()) {
    // Define session timeout (15 minutes of inactivity)
    $sessionTimeout = 900; // 15 minutes in seconds
    
    // Check if last activity is set
    if (isset($_SESSION['last_activity'])) {
        // Calculate time since last activity
        $timeSinceLastActivity = time() - $_SESSION['last_activity'];
        
        // If user has been inactive for too long, log them out
        if ($timeSinceLastActivity > $sessionTimeout) {
            $auth->logout();
            header("Location: /login.php?session_expired=1");
            exit;
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

/**
 * Function to check if user is logged in
 * Redirects to login page if not
 * 
 * @param string|null $requiredRole Optional role requirement
 * @return void
 */
function requireLogin($requiredRole = null) {
    global $auth;
    
    // Check if user is logged in
    if (!$auth->isLoggedIn()) {
        // Store current page for redirection after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login page
        header("Location: /login.php");
        exit;
    }
    
    // If a specific role is required, check if user has this role or higher
    if ($requiredRole !== null) {
        $currentRole = $auth->getUserRole();
        
        // Check if user has the required role or higher
        if ($currentRole !== $requiredRole && !$auth->isRoleHigher($currentRole, $requiredRole)) {
            // User doesn't have the required role - redirect to access denied page
            header("Location: /access-denied.php");
            exit;
        }
    }
}

/**
 * Function to get current user ID
 * 
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    global $auth;
    
    if ($auth->isLoggedIn()) {
        $user = $auth->getCurrentUser();
        return $user['id'];
    }
    
    return null;
}

/**
 * Function to check if current user can manage users
 * 
 * @return bool True if user can manage users
 */
function canManageUsers() {
    global $auth;
    
    if (!$auth->isLoggedIn()) {
        return false;
    }
    
    $role = $auth->getUserRole();
    return $auth->hasRequiredRoleForUserManagement($role);
}
