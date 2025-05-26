<?php
/**
 * User Profile Page
 * 
 * This page allows users to view and update their own profile information
 */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/Utils.php';

// Require login to access this page
requireLogin();

$auth = Auth::getInstance();
$currentUser = $auth->getCurrentUser();
$passwordManager = PasswordManager::getInstance();
$userManager = UserManager::getInstance();

$successMessage = '';
$errorMessage = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $profileData = [
        'phone' => Utils::sanitize($_POST['phone'] ?? ''),
        'location' => Utils::sanitize($_POST['location'] ?? ''),
    ];
    
    // Handle social links (convert to array)
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
    
    // Add social links to profile data
    $profileData['social_links'] = $socialLinks;
    
    // Update profile
    $updateResult = $userManager->updateProfile($currentUser['id'], $profileData);
    
    if ($updateResult['success']) {
        $successMessage = $updateResult['message'];
        // Refresh user data
        $auth->refreshUserData();
        $currentUser = $auth->getCurrentUser();
    } else {
        $errorMessage = $updateResult['message'];
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $errorMessage = "Todos los campos de contraseña son obligatorios.";
    } else {
        $passwordResult = $passwordManager->updatePassword(
            $currentUser['id'], 
            $currentPassword, 
            $newPassword, 
            $confirmPassword
        );
        
        if ($passwordResult['success']) {
            $successMessage = $passwordResult['message'];
        } else {
            $errorMessage = $passwordResult['message'];
        }
    }
}

// Parse social links from JSON
$socialLinks = [];
if (!empty($currentUser['social_links'])) {
    $socialLinks = json_decode($currentUser['social_links'], true) ?? [];
}

// Page title
$pageTitle = "Mi Perfil";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Sistema de Autenticación</title>
    <link rel="stylesheet" href="/assets/css/auth.css">
    <style>
        .profile-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .profile-header {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .profile-header h2 {
            margin-top: 0;
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
        
        .form-group input, 
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .form-group input:focus, 
        .form-group select:focus {
            border-color: #4e73df;
            outline: none;
        }
        
        .form-group.read-only input {
            background-color: #f9f9f9;
            cursor: not-allowed;
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
    </style>
</head>
<body>
    <?php include __DIR__ . '/templates/header.php'; ?>
    
    <div class="profile-container">
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success"><?php echo $successMessage; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
        <?php endif; ?>
        
        <div class="profile-header">
            <h2>Mi Perfil</h2>
            <p>Visualiza y actualiza tu información personal.</p>
        </div>
        
        <div class="profile-section">
            <h3>Información Personal</h3>
            <form method="post">
                <div class="form-row">
                    <div class="form-group read-only">
                        <label for="username">Nombre de Usuario</label>
                        <input type="text" id="username" value="<?php echo htmlspecialchars($currentUser['username']); ?>" readonly>
                    </div>
                    <div class="form-group read-only">
                        <label for="civil_name">Nombre Civil</label>
                        <input type="text" id="civil_name" value="<?php echo htmlspecialchars($currentUser['civil_name']); ?>" readonly>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group read-only">
                        <label for="email">Email</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" readonly>
                    </div>
                    <div class="form-group read-only">
                        <label for="role">Rango</label>
                        <input type="text" id="role" value="<?php echo htmlspecialchars($currentUser['role_name']); ?>" readonly>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Teléfono</label>
                        <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="location">Localidad</label>
                        <input type="text" name="location" id="location" value="<?php echo htmlspecialchars($currentUser['location'] ?? ''); ?>">
                    </div>
                </div>
                
                <input type="hidden" name="update_profile" value="1">
                <div class="btn-container">
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
        
        <div class="profile-section">
            <h3>Redes Sociales</h3>
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="social_facebook">Facebook</label>
                        <input type="url" name="social_facebook" id="social_facebook" placeholder="https://facebook.com/tu-perfil" 
                               value="<?php echo htmlspecialchars($socialLinks['facebook'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="social_twitter">Twitter</label>
                        <input type="url" name="social_twitter" id="social_twitter" placeholder="https://twitter.com/tu-usuario" 
                               value="<?php echo htmlspecialchars($socialLinks['twitter'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="social_instagram">Instagram</label>
                        <input type="url" name="social_instagram" id="social_instagram" placeholder="https://instagram.com/tu-usuario" 
                               value="<?php echo htmlspecialchars($socialLinks['instagram'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="social_linkedin">LinkedIn</label>
                        <input type="url" name="social_linkedin" id="social_linkedin" placeholder="https://linkedin.com/in/tu-perfil" 
                               value="<?php echo htmlspecialchars($socialLinks['linkedin'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="social_other">Otro Sitio</label>
                        <input type="url" name="social_other" id="social_other" placeholder="https://tu-sitio.com" 
                               value="<?php echo htmlspecialchars($socialLinks['other'] ?? ''); ?>">
                    </div>
                </div>
                
                <input type="hidden" name="update_profile" value="1">
                <div class="btn-container">
                    <button type="submit" class="btn btn-primary">Guardar Redes Sociales</button>
                </div>
            </form>
        </div>
        
        <div class="profile-section">
            <h3>Cambiar Contraseña</h3>
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="current_password">Contraseña Actual</label>
                        <input type="password" name="current_password" id="current_password" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password">Nueva Contraseña</label>
                        <input type="password" name="new_password" id="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirmar Nueva Contraseña</label>
                        <input type="password" name="confirm_password" id="confirm_password" required>
                    </div>
                </div>
                
                <input type="hidden" name="change_password" value="1">
                <div class="btn-container">
                    <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Password validation
        document.getElementById('new_password').addEventListener('change', function() {
            validatePassword();
        });
        
        document.getElementById('confirm_password').addEventListener('change', function() {
            validatePasswordMatch();
        });
        
        function validatePassword() {
            const password = document.getElementById('new_password').value;
            // Basic password validation - should match server requirements
            const minLength = 8;
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumbers = /\d/.test(password);
            
            if (password.length < minLength || !hasUpperCase || !hasLowerCase || !hasNumbers) {
                document.getElementById('new_password').setCustomValidity('La contraseña debe tener al menos 8 caracteres e incluir mayúsculas, minúsculas y números');
            } else {
                document.getElementById('new_password').setCustomValidity('');
            }
        }
        
        function validatePasswordMatch() {
            const password = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;
            
            if (password !== confirmPass) {
                document.getElementById('confirm_password').setCustomValidity('Las contraseñas no coinciden');
            } else {
                document.getElementById('confirm_password').setCustomValidity('');
            }
        }
    </script>
</body>
</html>
