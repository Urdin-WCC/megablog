<?php
/**
 * Create User Page
 * 
 * This page allows admin users (maestro or higher) to create new users
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/Utils.php';

// Require login with minimum role 'maestro'
requireLogin('maestro');

$auth = Auth::getInstance();
$currentUser = $auth->getCurrentUser();
$userManager = UserManager::getInstance();
$roleManager = RoleManager::getInstance();

// Initialize variables
$successMessage = '';
$errorMessage = '';

// Get available roles (roles lower than current user)
$availableRoles = $roleManager->getAssignableRoles($currentUser['role_name']);

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $userData = [
        'username' => Utils::sanitize($_POST['username'] ?? ''),
        'civil_name' => Utils::sanitize($_POST['civil_name'] ?? ''),
        'email' => Utils::sanitize($_POST['email'] ?? ''),
        'phone' => Utils::sanitize($_POST['phone'] ?? ''),
        'location' => Utils::sanitize($_POST['location'] ?? ''),
        'role' => Utils::sanitize($_POST['role'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'notes' => Utils::sanitize($_POST['notes'] ?? '')
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
    
    // Add social links to user data
    $userData['social_links'] = $socialLinks;
    
    // Handle authorized pages field
    if (!empty($_POST['authorized_pages'])) {
        $userData['authorized_pages'] = Utils::sanitize($_POST['authorized_pages']);
    }
    
    // Validate required fields
    $requiredFields = ['username', 'civil_name', 'email', 'password', 'role'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (empty($userData[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        $errorMessage = "Faltan campos obligatorios: " . implode(', ', $missingFields);
    }
    // Validate email format
    else if (!Utils::validateEmail($userData['email'])) {
        $errorMessage = "El formato del correo electrónico es inválido.";
    }
    // Validate password strength
    else {
        $passwordCheck = Utils::validatePassword($userData['password']);
        
        if (!$passwordCheck['valid']) {
            $errorMessage = $passwordCheck['message'];
        } else {
            // Create user
            $createResult = $userManager->createUser($userData, $currentUser['id']);
            
            if ($createResult['success']) {
                // Redirect to manage page with success message
                header("Location: manage.php?created=true");
                exit;
            } else {
                $errorMessage = $createResult['message'];
            }
        }
    }
}

// Set page title
$pageTitle = "Crear Usuario";
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
            <h2>Crear Nuevo Usuario</h2>
            <a href="manage.php" class="btn btn-secondary">Volver al Listado</a>
        </div>
        
        <form method="post" id="create-user-form">
            <div class="form-section">
                <h3>Información Básica</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="username" class="required">Nombre de Usuario</label>
                        <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="civil_name" class="required">Nombre Civil</label>
                        <input type="text" id="civil_name" name="civil_name" required value="<?php echo isset($_POST['civil_name']) ? htmlspecialchars($_POST['civil_name']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email" class="required">Email</label>
                        <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="role" class="required">Rango</label>
                        <select id="role" name="role" required>
                            <option value="">Seleccionar rango</option>
                            <?php foreach ($availableRoles as $role): ?>
                                <option value="<?php echo htmlspecialchars($role['name']); ?>" <?php echo isset($_POST['role']) && $_POST['role'] === $role['name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($role['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Teléfono</label>
                        <input type="text" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="location">Localidad</label>
                        <input type="text" id="location" name="location" value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Seguridad</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="required">Contraseña</label>
                        <input type="password" id="password" name="password" required>
                        <div class="password-requirements">
                            La contraseña debe tener al menos 8 caracteres e incluir mayúsculas, minúsculas y números.
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Redes Sociales</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="social_facebook">Facebook</label>
                        <input type="url" id="social_facebook" name="social_facebook" placeholder="https://facebook.com/perfil" value="<?php echo isset($_POST['social_facebook']) ? htmlspecialchars($_POST['social_facebook']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="social_twitter">Twitter</label>
                        <input type="url" id="social_twitter" name="social_twitter" placeholder="https://twitter.com/usuario" value="<?php echo isset($_POST['social_twitter']) ? htmlspecialchars($_POST['social_twitter']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="social_instagram">Instagram</label>
                        <input type="url" id="social_instagram" name="social_instagram" placeholder="https://instagram.com/usuario" value="<?php echo isset($_POST['social_instagram']) ? htmlspecialchars($_POST['social_instagram']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="social_linkedin">LinkedIn</label>
                        <input type="url" id="social_linkedin" name="social_linkedin" placeholder="https://linkedin.com/in/perfil" value="<?php echo isset($_POST['social_linkedin']) ? htmlspecialchars($_POST['social_linkedin']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="social_other">Otro sitio</label>
                        <input type="url" id="social_other" name="social_other" placeholder="https://otrasredes.com" value="<?php echo isset($_POST['social_other']) ? htmlspecialchars($_POST['social_other']) : ''; ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Campos Adicionales</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="authorized_pages">Páginas Autorizadas</label>
                        <textarea id="authorized_pages" name="authorized_pages" placeholder="Listado de páginas separadas por comas"><?php echo isset($_POST['authorized_pages']) ? htmlspecialchars($_POST['authorized_pages']) : ''; ?></textarea>
                        <div class="form-note">Ingresa las URLs de las páginas a las que este usuario tendrá acceso, separadas por comas.</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="notes">Notas</label>
                        <textarea id="notes" name="notes" placeholder="Notas internas sobre este usuario"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                        <div class="form-note">Estas notas solo serán visibles para usuarios con rango superior.</div>
                    </div>
                </div>
            </div>
            
            <div class="btn-container">
                <a href="manage.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Crear Usuario</button>
            </div>
        </form>
    </div>
    
    <script>
        // Basic form validation
        document.getElementById('create-user-form').addEventListener('submit', function(e) {
            let valid = true;
            const requiredFields = ['username', 'civil_name', 'email', 'password', 'role'];
            
            requiredFields.forEach(field => {
                const input = document.getElementById(field);
                if (!input.value.trim()) {
                    input.style.borderColor = '#e74a3b';
                    valid = false;
                } else {
                    input.style.borderColor = '#ddd';
                }
            });
            
            // Validate email format
            const email = document.getElementById('email');
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email.value.trim() && !emailPattern.test(email.value.trim())) {
                email.style.borderColor = '#e74a3b';
                valid = false;
            }
            
            // Validate password complexity
            const password = document.getElementById('password');
            if (password.value.trim()) {
                const minLength = 8;
                const hasUpperCase = /[A-Z]/.test(password.value);
                const hasLowerCase = /[a-z]/.test(password.value);
                const hasNumbers = /\d/.test(password.value);
                
                if (password.value.length < minLength || !hasUpperCase || !hasLowerCase || !hasNumbers) {
                    password.style.borderColor = '#e74a3b';
                    valid = false;
                }
            }
            
            if (!valid) {
                e.preventDefault();
                alert('Por favor, complete todos los campos obligatorios correctamente.');
            }
        });
    </script>
</body>
</html>
