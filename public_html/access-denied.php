<?php
/**
 * Access Denied Page
 * 
 * Displayed when a user tries to access a page without sufficient permissions
 */

// Include session
require_once __DIR__ . '/includes/session.php';

// Get user data if logged in
$user = null;
$isLoggedIn = $auth->isLoggedIn();

if ($isLoggedIn) {
    $user = $auth->getCurrentUser();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado | <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="/assets/css/auth.css">
    <style>
        .error-code {
            font-size: 5rem;
            font-weight: 700;
            color: var(--danger-color);
            margin: 0;
            line-height: 1;
        }
        
        .error-message {
            font-size: 1.5rem;
            margin-top: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .auth-card {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="error-code">403</h1>
            <p class="error-message">Acceso Denegado</p>
            
            <div class="alert alert-danger">
                No tienes permisos suficientes para acceder a esta página.
            </div>
            
            <?php if ($isLoggedIn): ?>
                <p>
                    Estás autenticado como <strong><?= htmlspecialchars($user['username']) ?></strong> 
                    con el rol de <strong><?= htmlspecialchars($user['role_name']) ?></strong>.
                </p>
                
                <p>
                    Este contenido requiere un nivel de acceso superior al que tienes.
                </p>
                
                <a href="<?= $auth->getRoleHomepage($user['role_name']) ?>" class="btn btn-primary">
                    Ir a Mi Página Principal
                </a>
                
                <div class="auth-footer">
                    <form action="/logout.php" method="post">
                        <button type="submit" class="auth-link">Cerrar Sesión</button>
                    </form>
                </div>
            <?php else: ?>
                <p>
                    No has iniciado sesión o tu sesión ha expirado.
                </p>
                
                <a href="/login.php" class="btn btn-primary">
                    Iniciar Sesión
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
