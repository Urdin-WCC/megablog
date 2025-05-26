<?php
/**
 * Header Template
 * 
 * This file contains the header template used across the site
 */

// Get current user information if logged in
$isLoggedIn = isset($auth) && $auth->isLoggedIn();
$currentUser = $isLoggedIn ? $auth->getCurrentUser() : null;
$userRole = $isLoggedIn ? $auth->getUserRole() : null;

// Set default page title if not set
if (!isset($pageTitle)) {
    $pageTitle = 'Sistema de Autenticación';
}
?>

<header class="site-header">
    <div class="header-container">
        <div class="logo">
            <a href="/index.php">Sistema de Autenticación</a>
        </div>
        
        <nav class="main-nav">
            <?php if ($isLoggedIn): ?>
                <ul>
                    <li><a href="/index.php">Inicio</a></li>
                    
                    <?php if ($userRole && ($userRole === 'god' || $userRole === 'kk' || $userRole === 'sumo' || $userRole === 'sacerdote' || $userRole === 'maestro')): ?>
                        <li><a href="/users/manage.php">Gestionar Usuarios</a></li>
                    <?php endif; ?>
                    
                    <li><a href="/profile.php">Mi Perfil</a></li>
                </ul>
            <?php endif; ?>
        </nav>
        
        <div class="user-controls">
            <?php if ($isLoggedIn): ?>
                <div class="user-info">
                    <span class="username"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                    <span class="role-badge <?php echo strtolower($userRole); ?>"><?php echo htmlspecialchars($userRole); ?></span>
                </div>
                <a href="/logout.php" class="logout-button">Cerrar Sesión</a>
            <?php else: ?>
                <a href="/login.php" class="login-button">Iniciar Sesión</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<style>
    /* Header Styles */
    .site-header {
        background-color: #ff4747;
        color: white;
        padding: 10px 0;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .header-container {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 20px;
    }
    
    .logo a {
        color: white;
        text-decoration: none;
        font-size: 1.5rem;
        font-weight: bold;
    }
    
    .main-nav ul {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    
    .main-nav li {
        margin: 0 15px;
    }
    
    .main-nav a {
        color: white;
        text-decoration: none;
        font-weight: 500;
        padding: 5px 0;
        transition: color 0.2s;
        position: relative;
    }
    
    .main-nav a:hover {
        color: rgba(255, 255, 255, 0.8);
    }
    
    .main-nav a:after {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        bottom: 0;
        left: 0;
        background-color: white;
        transition: width 0.2s;
    }
    
    .main-nav a:hover:after {
        width: 100%;
    }
    
    .user-controls {
        display: flex;
        align-items: center;
    }
    
    .user-info {
        margin-right: 15px;
        display: flex;
        align-items: center;
    }
    
    .username {
        font-weight: 500;
        margin-right: 8px;
    }
    
    .role-badge {
        background-color: rgba(255, 255, 255, 0.2);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.8rem;
        text-transform: uppercase;
    }
    
    /* Role-specific badge colors */
    .role-badge.god {
        background-color: #ffd700;
        color: #000;
    }
    
    .role-badge.kk {
        background-color: #c0c0c0;
        color: #000;
    }
    
    .role-badge.sumo {
        background-color: #cd7f32;
        color: #fff;
    }
    
    .role-badge.sacerdote {
        background-color: #800080;
        color: #fff;
    }
    
    .role-badge.maestro {
        background-color: #008000;
        color: #fff;
    }
    
    .role-badge.iniciado {
        background-color: #0000ff;
        color: #fff;
    }
    
    .role-badge.novicio {
        background-color: #ff8c00;
        color: #fff;
    }
    
    .role-badge.adepto {
        background-color: #ff4500;
        color: #fff;
    }
    
    .logout-button, .login-button {
        background-color: rgba(0, 0, 0, 0.1);
        color: white;
        text-decoration: none;
        padding: 5px 10px;
        border-radius: 4px;
        transition: background-color 0.2s;
    }
    
    .logout-button:hover, .login-button:hover {
        background-color: rgba(0, 0, 0, 0.2);
    }
</style>
