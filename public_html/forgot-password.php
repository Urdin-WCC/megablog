<?php
/**
 * Forgot Password Page
 * 
 * Allows users to request a password reset link
 */

// Include session management (includes config and Auth)
require_once __DIR__ . '/includes/session.php';

// If user is already logged in, redirect to home
if ($auth->isLoggedIn()) {
    header('Location: /index.php');
    exit;
}
$error = '';
$success = '';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    if (empty($email)) {
        $error = 'Por favor, introduce tu dirección de email.';
    } else {
        // Attempt to initialize password reset
        $result = $auth->initPasswordReset($email);
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña | <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <!-- You can add a logo here -->
                <!-- <img src="/assets/img/logo.png" alt="Logo" class="auth-logo"> -->
                <h1 class="auth-title">Recuperar Contraseña</h1>
                <p class="auth-subtitle">Introduce tu email para recibir instrucciones</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <form action="/forgot-password.php" method="post">
                <div class="form-group">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="tu@email.com" required autofocus
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    <small class="form-text">Introduce el email con el que te registraste.</small>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Enviar Instrucciones</button>
            </form>
            
            <div class="auth-footer">
                <div class="auth-links">
                    <a href="/login.php" class="auth-link">Volver a Iniciar Sesión</a>
                </div>
                
                <p>Todos los derechos reservados &copy; <?= date('Y') ?></p>
            </div>
        </div>
    </div>
</body>
</html>
