<?php
/**
 * User View Page
 * 
 * This page allows viewing detailed user information
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/Utils.php';

// Require login 
requireLogin();

$auth = Auth::getInstance();
$currentUser = $auth->getCurrentUser();
$userManager = UserManager::getInstance();
$roleManager = RoleManager::getInstance();

// Initialize variables
$errorMessage = '';
$user = null;
$canEditUser = false;
$canViewHiddenFields = false;

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $errorMessage = "ID de usuario no válido.";
} else {
    $userId = (int)$_GET['id'];
    
    // Get user data
    $user = $userManager->getUserById($userId);
    
    if (!$user) {
        $errorMessage = "Usuario no encontrado.";
    } else {
        // Check permissions
        
        // Users can view their own profile
        if ($userId == $currentUser['id']) {
            // User viewing their own profile
            $canViewHiddenFields = false; // Can't view their own hidden fields
            $canEditUser = true; // Can edit their own basic info
        } 
        // Admin users can view and edit profiles of users with lower rank
        else if ($roleManager->hasRequiredRoleForUserManagement($currentUser['role_name'])) {
            if ($roleManager->isRoleHigher($currentUser['role_name'], $user['role_name'])) {
                // Admin viewing a lower-rank user
                $canViewHiddenFields = true;
                $canEditUser = true;
            } else {
                // Admin viewing a higher-rank or same-rank user - only public info
                $canViewHiddenFields = false;
                $canEditUser = false;
            }
        } else {
            // Regular user viewing another user - only public info
            $canViewHiddenFields = false;
            $canEditUser = false;
        }
    }
}

// Parse social links from JSON
$socialLinks = [];
if ($user && !empty($user['social_links'])) {
    $socialLinks = json_decode($user['social_links'], true) ?? [];
}

// Set page title
$pageTitle = $user ? "Perfil de: " . $user['username'] : "Ver Usuario";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Sistema de Autenticación</title>
    <link rel="stylesheet" href="/assets/css/auth.css">
    <style>
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-header h2 {
            margin: 0;
        }
        
        .profile-section {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .profile-section h3 {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
            margin-top: 0;
        }
        
        .profile-row {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-start;
        }
        
        .profile-label {
            flex: 0 0 30%;
            font-weight: bold;
            padding-right: 15px;
        }
        
        .profile-value {
            flex: 0 0 70%;
        }
        
        .social-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .social-links li {
            margin-bottom: 5px;
        }
        
        .social-links a {
            text-decoration: none;
            color: #4e73df;
        }
        
        .social-links a:hover {
            text-decoration: underline;
        }
        
        .role-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #6c757d;
            color: white;
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
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background-color: #4e73df;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2e59d9;
        }
        
        .btn-secondary {
            background-color: #858796;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #717384;
        }
        
        .btn-container {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: flex-end;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .admin-notice {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        pre {
            white-space: pre-wrap;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #eee;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../templates/header.php'; ?>
    
    <div class="container">
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
        <?php endif; ?>
        
        <?php if ($user): ?>
            <div class="page-header">
                <h2><?php echo $pageTitle; ?></h2>
                <div class="btn-container">
                    <?php if ($canEditUser && $userId != $currentUser['id']): ?>
                        <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-primary">Editar Usuario</a>
                    <?php endif; ?>
                    <?php if ($userId == $currentUser['id']): ?>
                        <a href="/profile.php" class="btn btn-primary">Editar Mi Perfil</a>
                    <?php endif; ?>
                    <a href="manage.php" class="btn btn-secondary">Volver al Listado</a>
                </div>
            </div>
            
            <div class="profile-section">
                <h3>Información Básica</h3>
                
                <div class="profile-row">
                    <div class="profile-label">Nombre de Usuario</div>
                    <div class="profile-value"><?php echo htmlspecialchars($user['username']); ?></div>
                </div>
                
                <div class="profile-row">
                    <div class="profile-label">Nombre Civil</div>
                    <div class="profile-value"><?php echo htmlspecialchars($user['civil_name']); ?></div>
                </div>
                
                <div class="profile-row">
                    <div class="profile-label">Email</div>
                    <div class="profile-value">
                        <a href="mailto:<?php echo htmlspecialchars($user['email']); ?>">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </a>
                    </div>
                </div>
                
                <div class="profile-row">
                    <div class="profile-label">Rango</div>
                    <div class="profile-value">
                        <span class="role-badge <?php echo strtolower($user['role_name']); ?>">
                            <?php echo htmlspecialchars($user['role_name']); ?>
                        </span>
                    </div>
                </div>
                
                <?php if (!empty($user['phone'])): ?>
                    <div class="profile-row">
                        <div class="profile-label">Teléfono</div>
                        <div class="profile-value"><?php echo htmlspecialchars($user['phone']); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($user['location'])): ?>
                    <div class="profile-row">
                        <div class="profile-label">Localidad</div>
                        <div class="profile-value"><?php echo htmlspecialchars($user['location']); ?></div>
                    </div>
                <?php endif; ?>
                
                <div class="profile-row">
                    <div class="profile-label">Fecha de Registro</div>
                    <div class="profile-value"><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></div>
                </div>
            </div>
            
            <?php if (!empty($socialLinks)): ?>
                <div class="profile-section">
                    <h3>Redes Sociales</h3>
                    <ul class="social-links">
                        <?php if (!empty($socialLinks['facebook'])): ?>
                            <li>
                                <strong>Facebook:</strong> 
                                <a href="<?php echo htmlspecialchars($socialLinks['facebook']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($socialLinks['facebook']); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (!empty($socialLinks['twitter'])): ?>
                            <li>
                                <strong>Twitter:</strong> 
                                <a href="<?php echo htmlspecialchars($socialLinks['twitter']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($socialLinks['twitter']); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (!empty($socialLinks['instagram'])): ?>
                            <li>
                                <strong>Instagram:</strong> 
                                <a href="<?php echo htmlspecialchars($socialLinks['instagram']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($socialLinks['instagram']); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (!empty($socialLinks['linkedin'])): ?>
                            <li>
                                <strong>LinkedIn:</strong> 
                                <a href="<?php echo htmlspecialchars($socialLinks['linkedin']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($socialLinks['linkedin']); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if (!empty($socialLinks['other'])): ?>
                            <li>
                                <strong>Otro sitio:</strong> 
                                <a href="<?php echo htmlspecialchars($socialLinks['other']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($socialLinks['other']); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($canViewHiddenFields): ?>
                <div class="profile-section">
                    <h3>Campos Adicionales (Solo para Administradores)</h3>
                    <div class="admin-notice">
                        Notas: La siguiente información solo es visible para usuarios con rango superior a este usuario.
                    </div>
                    
                    <?php if (!empty($user['authorized_pages'])): ?>
                        <div class="profile-row">
                            <div class="profile-label">Páginas Autorizadas</div>
                            <div class="profile-value">
                                <pre><?php echo htmlspecialchars($user['authorized_pages']); ?></pre>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="profile-row">
                            <div class="profile-label">Páginas Autorizadas</div>
                            <div class="profile-value">
                                <em>No hay páginas específicas autorizadas.</em>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($user['notes'])): ?>
                        <div class="profile-row">
                            <div class="profile-label">Notas</div>
                            <div class="profile-value">
                                <pre><?php echo htmlspecialchars($user['notes']); ?></pre>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="profile-row">
                            <div class="profile-label">Notas</div>
                            <div class="profile-value">
                                <em>No hay notas disponibles para este usuario.</em>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Display login attempts information -->
                    <div class="profile-row">
                        <div class="profile-label">Intentos de inicio de sesión</div>
                        <div class="profile-value">
                            <?php 
                            if (isset($user['login_attempts']) && $user['login_attempts'] > 0) {
                                echo "<strong>Intentos fallidos:</strong> " . htmlspecialchars($user['login_attempts']) . 
                                     "<br><strong>Último intento:</strong> " . 
                                     (isset($user['last_attempt_time']) ? date('d/m/Y H:i', strtotime($user['last_attempt_time'])) : "No disponible");
                                
                                if (isset($user['account_locked']) && $user['account_locked']) {
                                    echo "<br><strong class='text-danger'>CUENTA BLOQUEADA</strong>";
                                }
                            } else {
                                echo "<em>No hay intentos fallidos recientes.</em>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Show account access statistics -->
            <div class="profile-section">
                <h3>Información de Actividad</h3>
                <?php
                $db = Database::getInstance();
                
                // Get last login time
                $db->query("SELECT MAX(timestamp) as last_login FROM activity_log 
                           WHERE user_id = :user_id AND activity_type = 'login'", 
                           ['user_id' => $userId]);
                
                $lastLogin = $db->fetch();
                
                // Count total logins
                $db->query("SELECT COUNT(*) as login_count FROM activity_log 
                           WHERE user_id = :user_id AND activity_type = 'login'", 
                           ['user_id' => $userId]);
                
                $loginCount = $db->fetch();
                ?>
                
                <div class="profile-row">
                    <div class="profile-label">Último inicio de sesión</div>
                    <div class="profile-value">
                        <?php 
                        if ($lastLogin && $lastLogin['last_login']) {
                            echo date('d/m/Y H:i', strtotime($lastLogin['last_login'])); 
                        } else {
                            echo "<em>No ha iniciado sesión todavía.</em>";
                        }
                        ?>
                    </div>
                </div>
                
                <div class="profile-row">
                    <div class="profile-label">Cantidad de inicios de sesión</div>
                    <div class="profile-value">
                        <?php echo $loginCount ? $loginCount['login_count'] : 0; ?>
                    </div>
                </div>
                
                <div class="profile-row">
                    <div class="profile-label">Cuenta creada</div>
                    <div class="profile-value">
                        <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                    </div>
                </div>
            </div>
            
            <div class="btn-container">
                <a href="manage.php" class="btn btn-secondary">Volver al Listado</a>
                <?php if ($canEditUser && $userId != $currentUser['id']): ?>
                    <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-primary">Editar Usuario</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <p>No se puede acceder a este usuario o no tienes permisos suficientes.</p>
                <p><a href="manage.php" class="btn btn-secondary">Volver al Listado de Usuarios</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
