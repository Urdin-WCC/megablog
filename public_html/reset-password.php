<?php
/**
 * Reset Password Page
 * 
 * Allows users to reset their password using a token
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
$validToken = false;
$token = '';
$userId = null;

// Get token from URL
if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    
    // Verify token
    $result = $auth->verifyResetToken($token);
    
    if ($result['success']) {
        $validToken = true;
        $userId = $result['user_id'];
    } else {
        $error = $result['message'];
    }
} else {
    $error = 'No se ha proporcionado un token válido.';
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    // Get form data
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    if (empty($password) || empty($confirmPassword)) {
        $error = 'Por favor, completa todos los campos.';
    } else if ($password !== $confirmPassword) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        // Attempt to reset password
        $result = $auth->completePasswordReset($token, $password, $confirmPassword);
        
        if ($result['success']) {
            $success = $result['message'];
            // Invalidate token to prevent re-use
            $validToken = false;
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
    <title>Restablecer Contraseña | <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="/assets/css/auth.css">
    <script>
        // Simple password strength checker
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const meter = document.getElementById('password-strength');
            
            // Hide meter if password is empty
            if (password.length === 0) {
                meter.value = 0;
                return;
            }
            
            // Calculate strength
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength += 1;
            if (password.length >= 12) strength += 1;
            
            // Character variety check
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[a-z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            // Update meter (scale to 0-100)
            meter.value = (strength / 6) * 100;
        }
        
        // Check password match
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchMessage = document.getElementById('password-match');
            
            if (password === '' || confirmPassword === '') {
                matchMessage.textContent = '';
                return;
            }
            
            if (password === confirmPassword) {
                matchMessage.textContent = 'Las contraseñas coinciden';
                matchMessage.className = 'form-text text-success';
            } else {
                matchMessage.textContent = 'Las contraseñas no coinciden';
                matchMessage.className = 'form-text text-danger';
            }
        }
    </script>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <!-- You can add a logo here -->
                <!-- <img src="/assets/img/logo.png" alt="Logo" class="auth-logo"> -->
                <h1 class="auth-title">Restablecer Contraseña</h1>
                <p class="auth-subtitle">Crea una nueva contraseña segura</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($success) ?>
                    <p>Puedes <a href="/login.php" class="alert-link">iniciar sesión</a> con tu nueva contraseña.</p>
                </div>
            <?php endif; ?>
            
            <?php if ($validToken): ?>
                <form action="/reset-password.php?token=<?= htmlspecialchars(urlencode($token)) ?>" method="post">
                    <div class="form-group">
                        <label for="password" class="form-label">Nueva Contraseña</label>
                        <div class="password-input-container">
                            <input type="password" id="password" name="password" class="form-control" 
                                   placeholder="Nueva contraseña" required autofocus
                                   onkeyup="checkPasswordStrength(); checkPasswordMatch();">
                            <button type="button" id="toggle-password" class="toggle-password" aria-label="Mostrar/ocultar constraseña">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="eye-icon" viewBox="0 0 16 16">
                                    <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                    <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                                </svg>
                            </button>
                        </div>
                        <small class="form-text">
                            La contraseña debe tener al menos <?= PASSWORD_MIN_LENGTH ?> caracteres,
                            incluir mayúsculas, minúsculas y números.
                        </small>
                        <meter id="password-strength" min="0" max="100" low="33" high="66" optimum="100" value="0"></meter>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                        <div class="password-input-container">
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="form-control" placeholder="Confirma tu contraseña" required
                                   onkeyup="checkPasswordMatch();">
                            <button type="button" id="toggle-confirm-password" class="toggle-password" aria-label="Mostrar/ocultar constraseña">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="eye-icon" viewBox="0 0 16 16">
                                    <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                    <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                                </svg>
                            </button>
                        </div>
                        <small id="password-match" class="form-text"></small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Guardar Contraseña</button>
                </form>
            <?php else: ?>
                <?php if (empty($success)): ?>
                    <p>El enlace para restablecer tu contraseña no es válido o ha expirado. Por favor, solicita un nuevo enlace.</p>
                    <a href="/forgot-password.php" class="btn btn-primary btn-block">Solicitar Nuevo Enlace</a>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="auth-footer">
                <div class="auth-links">
                    <a href="/login.php" class="auth-link">Volver a Iniciar Sesión</a>
                </div>
                
                <p>Todos los derechos reservados &copy; <?= date('Y') ?></p>
            </div>
        </div>
    </div>

    <!-- JavaScript para el botón de mostrar/ocultar contraseña -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
            
            const togglePassword = document.getElementById('toggle-password');
            const passwordInput = document.getElementById('password');
            
            if (togglePassword && passwordInput) {
                togglePassword.innerHTML = eyeOpenSVG;
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    togglePassword.innerHTML = type === 'password' ? eyeOpenSVG : eyeClosedSVG;
                });
            }
            
            const toggleConfirmPassword = document.getElementById('toggle-confirm-password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            if (toggleConfirmPassword && confirmPasswordInput) {
                toggleConfirmPassword.innerHTML = eyeOpenSVG;
                toggleConfirmPassword.addEventListener('click', function() {
                    const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    confirmPasswordInput.setAttribute('type', type);
                    toggleConfirmPassword.innerHTML = type === 'password' ? eyeOpenSVG : eyeClosedSVG;
                });
            }
        });
    </script>
</body>
</html>
