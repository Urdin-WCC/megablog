<?php
/**
 * Configuration file
 * Contains database credentials and global settings
 */

// Database configuration
define('DB_HOST', 'srv1789.hstgr.io');
define('DB_NAME', 'u643065128_phpblog');
define('DB_USER', 'u643065128_urdinart');
define('DB_PASSWORD', 'Noctiluca3.');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'Sistema de Autenticación');
define('APP_URL', 'http://' . $_SERVER['HTTP_HOST']);  // Adjust if needed
define('COOKIE_LIFETIME', 86400 * 7);  // 7 days (in seconds)

// Security settings
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 15 * 60);  // 15 minutes (in seconds)
define('PASSWORD_MIN_LENGTH', 8);

// User roles in hierarchical order (highest to lowest)
define('USER_ROLES', ['god', 'kk', 'sumo', 'sacerdote', 'maestro', 'iniciado', 'novicio', 'adepto']);

// Email configuration (Replace with your SMTP settings when available)
define('MAIL_HOST', 'smtp.example.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'noreply@example.com');
define('MAIL_PASSWORD', 'your-email-password');
define('MAIL_FROM_ADDRESS', 'noreply@example.com');
define('MAIL_FROM_NAME', APP_NAME);

// Debug mode (set to false in production)
define('DEBUG', true);

// Set timezone
date_default_timezone_set('Europe/Madrid');

// NOTE: Session settings have been moved to session.php

// Return configuration as array (useful in some contexts)
return [
    'db' => [
        'host' => DB_HOST,
        'name' => DB_NAME,
        'user' => DB_USER,
        'password' => DB_PASSWORD,
        'charset' => DB_CHARSET
    ],
    'app' => [
        'name' => APP_NAME,
        'url' => APP_URL,
        'cookie_lifetime' => COOKIE_LIFETIME
    ],
    'security' => [
        'max_login_attempts' => MAX_LOGIN_ATTEMPTS,
        'lockout_time' => LOCKOUT_TIME,
        'password_min_length' => PASSWORD_MIN_LENGTH
    ],
    'roles' => USER_ROLES,
    'mail' => [
        'host' => MAIL_HOST,
        'port' => MAIL_PORT,
        'username' => MAIL_USERNAME,
        'password' => MAIL_PASSWORD,
        'from_address' => MAIL_FROM_ADDRESS,
        'from_name' => MAIL_FROM_NAME
    ],
    'debug' => DEBUG
];
