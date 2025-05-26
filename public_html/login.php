<?php
/**
 * Login Page
 * 
 * Handles user authentication
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

// Check for session expiration message
if (isset($_GET['session_expired'])) {
    $error = 'Tu sesión ha expirado. Por favor, inicia sesión de nuevo.';
}

// Check for logged out message
if (isset($_GET['logged_out'])) {
    $success = 'Has cerrado sesión correctamente.';
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? true : false;
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor, completa todos los campos.';
    } else {
        // Attempt login
        $result = $auth->login($email, $password, $remember);
        
        if ($result['success']) {
            // Check if there's a redirect after login
            $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : '/index.php';
            unset($_SESSION['redirect_after_login']);
            
            // Redirect to appropriate page
            header("Location: $redirect");
            exit;
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
    <title>Iniciar Sesión | <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <!-- You can add a logo here -->
                <!-- <img src="/assets/img/logo.png" alt="Logo" class="auth-logo"> -->
                <h1 class="auth-title"><?= htmlspecialchars(APP_NAME) ?></h1>
                <p class="auth-subtitle">Inicia sesión en tu cuenta</p>
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
            
            <form action="/login.php" method="post">
                <div class="form-group">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="tu@email.com" required autofocus
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="password-input-container">
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Contraseña" required>
                        <button type="button" id="toggle-password" class="toggle-password" aria-label="Mostrar/ocultar constraseña">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="eye-icon" viewBox="0 0 16 16">
                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="form-check">
                    <input type="checkbox" id="remember" name="remember" class="form-check-input"
                           <?= isset($_POST['remember']) ? 'checked' : '' ?>>
                    <label for="remember">Recordarme</label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Iniciar Sesión</button>
            </form>
            
            <div class="auth-footer">
                <div class="auth-links">
                    <a href="/forgot-password.php" class="auth-link">¿Olvidaste tu contraseña?</a>
                </div>
                
                <p>Todos los derechos reservados &copy; <?= date('Y') ?></p>
            </div>
        </div>
    </div>

    <!-- JavaScript para el botón de mostrar/ocultar contraseña -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('toggle-password');
            const passwordInput = document.getElementById('password');
            
            // SVG icons for eye open and eye closed
            const eyeOpenSVG = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="eye-icon" viewBox="0 0 16 16">
                    <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                    <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                </svg>
            `;
            
            const eyeClosedSVG = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="eye-icon" viewBox="0 0 16 16">
                    <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7.028 7.028 0 0 0-2.79.588l.77.771A5.944 5.944 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.134 13.134 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755-.165.165-.337.328-.517.486l.708.709z"/>
                    <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/>
                    <path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12-.708.708z"/>
                </svg>
            `;
            
            if (togglePassword && passwordInput) {
                // Initial state (password hidden, eye open icon)
                togglePassword.innerHTML = eyeOpenSVG;
                
                // Toggle password visibility when button is clicked
                togglePassword.addEventListener('click', function() {
                    // Toggle type between "password" and "text"
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    // Toggle the eye icon
                    togglePassword.innerHTML = type === 'password' ? eyeOpenSVG : eyeClosedSVG;
                });
            }
        });
    </script>
</body>
</html>
