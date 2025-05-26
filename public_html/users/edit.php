<?php
/**
 * Edit User Page
 * 
 * This page allows admin users (maestro or higher) to edit users with lower roles
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/Utils.php';

// Require login with minimum role 'maestro'
requireLogin('maestro');

$auth = Auth::getInstance();
$currentUser = $auth->getCurrentUser();
$userManager = UserManager::getInstance();
$roleManager = RoleManager::getInstance();
$passwordManager = PasswordManager::getInstance();

// Initialize variables
$successMessage = '';
$errorMessage = '';
$user = null;

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
        // Check if current user has permission to edit this user (higher role)
        if (!$roleManager->isRoleHigher($currentUser['role_name'], $user['role_name'])) {
            $errorMessage = "No tienes permiso para editar este usuario.";
            $user = null;
        }
    }
}

// Get available roles (roles lower than current user)
$availableRoles = $roleManager->getAssignableRoles($currentUser['role_name']);

// Parse social links from JSON
$socialLinks = [];
if ($user && !empty($user['social_links'])) {
    $socialLinks = json_decode($user['social_links'], true) ?? [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $userData = [
            'civil_name' => Utils::sanitize($_POST['civil_name'] ?? ''),
            'email' => Utils::sanitize($_POST['email'] ?? ''),
            'username' => Utils::sanitize($_POST['username'] ?? ''),
            'phone' => Utils::sanitize($_POST['phone'] ?? ''),
            'location' => Utils::sanitize($_POST['location'] ?? '')
        ];
        
        // Handle social links array
        $socialLinks = [];
        if (!empty($_POST['social_facebook'])) {
            $socialLinks['facebook'] = Utils::sanitize($_POST['social_facebook']);
        }
        if (!empty($_POST['social_twitter'])) {
            $socialLinks['twitter'] = Utils::sanitize($_POST['social_twitter']);
        }
        if (!empty($_POST['social_instagram'])) {
            $socialLinks['instagram'] = Utils::sanitize($_POST['social_instagram']);
        }
        if (!empty($_POST['social_linkedin'])) {
            $socialLinks['linkedin'] = Utils::sanitize($_POST['social_linkedin']);
        }
        if (!empty($_POST['social_other'])) {
            $socialLinks['other'] = Utils::sanitize($_POST['social_other']);
        }
        
        $userData['social_links'] = $socialLinks;
        
        // Handle role update if provided
        if (isset($_POST['role']) && !empty($_POST['role'])) {
            $newRole = Utils::sanitize($_POST['role']);
            
            // Check if role is different and valid
            if ($newRole !== $user['role_name']) {
                // Update user role
                $roleUpdateResult = $roleManager->updateUserRole($userId, $newRole, $currentUser['id']);
                
                if (!$roleUpdateResult['success']) {
                    $errorMessage = $roleUpdateResult['message'];
                }
            }
        }
        
        // Handle additional fields (authorization and notes)
        if (isset($_POST['authorized_pages'])) {
            $userData['authorized_pages'] = Utils::sanitize($_POST['authorized_pages']);
        }
        
        if (isset($_POST['notes'])) {
            // Update user notes
            $notesUpdateResult = $userManager->updateUserNotes($userId, Utils::sanitize($_POST['notes']), $currentUser['id']);
            
            if (!$notesUpdateResult['success'] && empty($errorMessage)) {
                $errorMessage = $notesUpdateResult['message'];
            }
        }
        
        // Validate email format
        if (!Utils::validateEmail($userData['email'])) {
            $errorMessage = "El formato del correo electrónico es inválido.";
        } else {
            // Update user profile
            $db = Database::getInstance();
            
            try {
                // Check if email is already in use by another user
                $db->query("SELECT id FROM users 
                           WHERE email = :email 
                           AND id != :id", 
                           ['email' => $userData['email'], 'id' => $userId]);
                
                if ($db->rowCount() > 0) {
                    $errorMessage = "Este email ya está en uso por otro usuario.";
                } else {
                    // Build update query
                    $updateFields = [];
                    $params = ['id' => $userId];
                    
                    foreach ($userData as $field => $value) {
                        if ($field === 'social_links') {
                            $updateFields[] = "$field = :$field";
                            $params[$field] = json_encode($value);
                        } else {
                            $updateFields[] = "$field = :$field";
                            $params[$field] = $value;
                        }
                    }
                    
                    if (!empty($updateFields)) {
                        $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";
                        $db->query($query, $params);
                        
                        if ($db->rowCount() > 0 || empty($errorMessage)) {
                            $successMessage = "Usuario actualizado exitosamente.";
                            
                            // Refresh user data
                            $user = $userManager->getUserById($userId);
                            if (!empty($user['social_links'])) {
                                $socialLinks = json_decode($user['social_links'], true) ?? [];
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $errorMessage = "Error al actualizar el usuario: " . $e->getMessage();
            }
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($newPassword) || empty($confirmPassword)) {
            $errorMessage = "Por favor ingresa y confirma la nueva contraseña.";
        } else if ($newPassword !== $confirmPassword) {
            $errorMessage = "Las contraseñas no coinciden.";
        } else {
            // Validate password strength
            $passwordCheck = Utils::validatePassword($newPassword);
            
            if (!$passwordCheck['valid']) {
                $errorMessage = $passwordCheck['message'];
            } else {
                // Update password
                $db = Database::getInstance();
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                $db->query("UPDATE users SET password = :password WHERE id = :id", 
                         ['password' => $hashedPassword, 'id' => $userId]);
                
                if ($db->rowCount() > 0) {
                    Utils::logActivity('password_changed', $currentUser['id'], "Changed password for user ID: $userId");
                    $successMessage = "Contraseña actualizada exitosamente.";
                } else {
                    $errorMessage = "No se pudo actualizar la contraseña.";
                }
            }
        }
    }
    
    // If a redirect is needed after successful update
    if (isset($_POST['redirect']) && $_POST['redirect'] === 'list' && empty($errorMessage)) {
        header("Location: manage.php?updated=true");
        exit;
    }
}

// Set page title
$pageTitle = $user ? "Editar Usuario: " . $user['username'] : "Editar Usuario";
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
        
        .form-section {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .form-section h3 {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
            margin-top: 0;
        }
        
        .form-row {
            display: flex;
            margin-bottom: 15px;
        }
        
        .form-group {
            flex: 1;
            margin-right: 15px;
        }
        
        .form-group:last-child {
            margin-right: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group .required:after {
            content: "*";
            color: #e74a3b;
            margin-left: 3px;
        }
        
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: 0.9rem;
        }
        
        .form-group input:focus, 
        .form-group select:focus, 
        .form-group textarea:focus {
            border-color: #4e73df;
            outline: none;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .btn-container {
            text-align: right;
            margin-top: 20px;
        }
        
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
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
        
        .password-requirements {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .form-note {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 15px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            margin-right: 5px;
            border-radius: 4px 4px 0 0;
        }
        
        .tab.active {
            background-color: white;
            border-color: #ddd;
            margin-bottom: -1px;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../templates/header.php'; ?>
    
    <div class="container">
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success"><?php echo $successMessage; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
        <?php endif; ?>
        
        <div class="page-header">
            <h2><?php echo $pageTitle; ?></h2>
            <a href="manage.php" class="btn btn-secondary">Volver al Listado</a>
        </div>
        
        <?php if ($user): ?>
            <div class="tabs">
                <div class="tab active" data-tab="profile">Información de Perfil</div>
                <div class="tab" data-tab="password">Cambiar Contraseña</div>
                <div class="tab" data-tab="advanced">Configuración Avanzada</div>
            </div>
            
            <div class="tab-content active" id="profile-tab">
                <form method="post">
                    <div class="form-section">
                        <h3>Información Básica</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username" class="required">Nombre de Usuario</label>
                                <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($user['username']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="civil_name" class="required">Nombre Civil</label>
                                <input type="text" id="civil_name" name="civil_name" required value="<?php echo htmlspecialchars($user['civil_name']); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email" class="required">Email</label>
                                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>">
                            </div>
                            <div class="form-group">
                                <label for="role">Rango</label>
                                <select id="role" name="role">
                                    <option value="<?php echo htmlspecialchars($user['role_name']); ?>">
                                        <?php echo htmlspecialchars($user['role_name']); ?> (Actual)
                                    </option>
                                    <?php foreach ($availableRoles as $role): ?>
                                        <?php if ($role['name'] !== $user['role_name']): ?>
                                            <option value="<?php echo htmlspecialchars($role['name']); ?>">
                                                <?php echo htmlspecialchars($role['name']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Teléfono</label>
                                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="location">Localidad</label>
                                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($user['location'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>Redes Sociales</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="social_facebook">Facebook</label>
                                <input type="url" id="social_facebook" name="social_facebook" placeholder="https://facebook.com/perfil" value="<?php echo htmlspecialchars($socialLinks['facebook'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="social_twitter">Twitter</label>
                                <input type="url" id="social_twitter" name="social_twitter" placeholder="https://twitter.com/usuario" value="<?php echo htmlspecialchars($socialLinks['twitter'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="social_instagram">Instagram</label>
                                <input type="url" id="social_instagram" name="social_instagram" placeholder="https://instagram.com/usuario" value="<?php echo htmlspecialchars($socialLinks['instagram'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="social_linkedin">LinkedIn</label>
                                <input type="url" id="social_linkedin" name="social_linkedin" placeholder="https://linkedin.com/in/perfil" value="<?php echo htmlspecialchars($socialLinks['linkedin'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="social_other">Otro sitio</label>
                                <input type="url" id="social_other" name="social_other" placeholder="https://otrasredes.com" value="<?php echo htmlspecialchars($socialLinks['other'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="update_profile" value="1">
                    <div class="btn-container">
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        <button type="submit" name="redirect" value="list" class="btn btn-secondary">Guardar y Volver</button>
                    </div>
                </form>
            </div>
            
            <div class="tab-content" id="password-tab">
                <form method="post">
                    <div class="form-section">
                        <h3>Cambiar Contraseña</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password" class="required">Nueva Contraseña</label>
                                <input type="password" id="new_password" name="new_password" required>
                                <div class="password-requirements">
                                    La contraseña debe tener al menos 8 caracteres e incluir mayúsculas, minúsculas y números.
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password" class="required">Confirmar Contraseña</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="change_password" value="1">
                    <div class="btn-container">
                        <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
                    </div>
                </form>
            </div>
            
            <div class="tab-content" id="advanced-tab">
                <form method="post">
                    <div class="form-section">
                        <h3>Páginas Autorizadas</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="authorized_pages">Páginas Autorizadas</label>
                                <textarea id="authorized_pages" name="authorized_pages" placeholder="Listado de páginas separadas por comas"><?php echo htmlspecialchars($user['authorized_pages'] ?? ''); ?></textarea>
                                <div class="form-note">Ingresa las URLs de las páginas a las que este usuario tendrá acceso, separadas por comas.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>Notas Internas</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="notes">Notas</label>
                                <textarea id="notes" name="notes" placeholder="Notas internas sobre este usuario"><?php echo htmlspecialchars($user['notes'] ?? ''); ?></textarea>
                                <div class="form-note">Estas notas solo serán visibles para usuarios con rango superior.</div>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="update_profile" value="1">
                    <div class="btn-container">
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <p>No se puede acceder a este usuario o no tienes permisos suficientes.</p>
                <p><a href="manage.php" class="btn btn-secondary">Volver al Listado de Usuarios</a></p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Tab functionality
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs and contents
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to this tab
                this.classList.add('active');
                
                // Show corresponding content
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId + '-tab').classList.add('active');
            });
        });
        
        // Basic form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = this.querySelectorAll('[required]');
                let valid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.style.borderColor = '#e74a3b';
                        valid = false;
                    } else {
                        field.style.borderColor = '#ddd';
                    }
                });
                
                // Validate email format if present
                const email = this.querySelector('#email');
                if (email && email.value.trim()) {
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(email.value.trim())) {
                        email.style.borderColor = '#e74a3b';
                        valid = false;
                    }
                }
                
                // Validate password match if changing password
                const newPassword = this.querySelector('#new_password');
                const confirmPassword = this.querySelector('#confirm_password');
                if (newPassword && confirmPassword && 
                    (newPassword.value.trim() || confirmPassword.value.trim())) {
                    
                    if (newPassword.value !== confirmPassword.value) {
                        newPassword.style.borderColor = '#e74a3b';
                        confirmPassword.style.borderColor = '#e74a3b';
                        valid = false;
                    }
                    
                    // Validate password complexity
                    if (newPassword.value.trim()) {
                        const minLength = 8;
                        const hasUpperCase = /[A-Z]/.test(newPassword.value);
                        const hasLowerCase = /[a-z]/.test(newPassword.value);
                        const hasNumbers = /\d/.test(newPassword.value);
                        
                        if (newPassword.value.length < minLength || !hasUpperCase || !hasLowerCase || !hasNumbers) {
                            newPassword.style.borderColor = '#e74a3b';
                            valid = false;
                        }
                    }
                }
                
                if (!valid) {
                    e.preventDefault();
                    alert('Por favor, complete correctamente todos los campos requeridos.');
                }
            });
        });
    </script>
</body>
</html>
