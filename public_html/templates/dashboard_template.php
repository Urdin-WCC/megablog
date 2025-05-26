<?php
/**
 * Dashboard Template
 * 
 * This is a reusable template for user dashboards
 * 
 * @param string $title Page title
 * @param string $roleName User role name
 * @param array $user User data
 * @param string $content Main content HTML
 */

// Ensure variables are defined
$title = $title ?? 'Dashboard';
$roleName = $roleName ?? 'Usuario';
$user = $user ?? [];
$content = $content ?? '<p>No content</p>';
$roleColor = '';

// Define color for each role (for visual identification)
switch (strtolower($roleName)) {
    case 'god':
        $roleColor = '#ff5252'; // red
        break;
    case 'kk':
        $roleColor = '#ff9800'; // orange
        break;
    case 'sumo':
        $roleColor = '#ffeb3b'; // yellow
        break;
    case 'sacerdote':
        $roleColor = '#4caf50'; // green
        break;
    case 'maestro':
        $roleColor = '#2196f3'; // blue
        break;
    case 'iniciado':
        $roleColor = '#673ab7'; // deep purple
        break;
    case 'novicio':
        $roleColor = '#9c27b0'; // purple
        break;
    case 'adepto':
        $roleColor = '#795548'; // brown
        break;
    default:
        $roleColor = '#607d8b'; // blue grey
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> | <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="/assets/css/auth.css">
    <style>
        :root {
            --role-color: <?= $roleColor ?>;
        }
        
        body {
            padding: 0;
            margin: 0;
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .navbar {
            background-color: var(--role-color);
            color: white;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
            text-decoration: none;
        }
        
        .navbar-user {
            display: flex;
            align-items: center;
        }
        
        .navbar-user-info {
            margin-right: 1rem;
            text-align: right;
        }
        
        .navbar-username {
            font-weight: 600;
        }
        
        .navbar-role {
            opacity: 0.9;
            font-size: 0.875rem;
        }
        
        .navbar-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: white;
            color: var(--role-color);
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            margin-left: 0.5rem;
        }
        
        .container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 2rem 1rem;
            flex: 1;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .card-title {
            margin: 0;
            font-size: 1.25rem;
            color: #333;
        }
        
        .card-role {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background-color: var(--role-color);
            color: white;
            border-radius: 4px;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .role-description {
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .footer {
            text-align: center;
            padding: 1.5rem;
            background-color: #f0f0f0;
            color: #666;
            font-size: 0.875rem;
            margin-top: auto;
        }
        
        .btn-logout {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 0.875rem;
            padding: 0.5rem;
            font-family: inherit;
        }
        
        .btn-logout:hover {
            text-decoration: underline;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                text-align: center;
            }
            
            .navbar-user {
                margin-top: 1rem;
                flex-direction: column;
            }
            
            .navbar-user-info {
                margin-right: 0;
                margin-bottom: 0.5rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="/" class="navbar-brand"><?= htmlspecialchars(APP_NAME) ?></a>
        
        <div class="navbar-user">
            <div class="navbar-user-info">
                <div class="navbar-username"><?= htmlspecialchars($user['username'] ?? 'Usuario') ?></div>
                <div class="navbar-role"><?= htmlspecialchars($roleName) ?></div>
            </div>
            
            <div class="navbar-avatar">
                <?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?>
            </div>
            
            <form action="/logout.php" method="post" style="margin-left: 1rem;">
                <button type="submit" class="btn-logout">Cerrar Sesión</button>
            </form>
        </div>
    </nav>
    
    <div class="container">
        <?= $content ?>
    </div>
    
    <footer class="footer">
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?>. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
