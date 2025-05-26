<?php
/**
 * User Management Page
 * 
 * This page allows admin users (maestro or higher) to manage system users
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
$users = [];
$totalUsers = 0;
$totalPages = 1;
$currentPage = 1;
$searchTerm = '';
$perPage = 10; // Users per page
$sortField = 'username';
$sortOrder = 'ASC';
$filterRole = '';

// Get available roles for the dropdown
$availableRoles = $roleManager->getManageableRoles($currentUser['role_name']);

// Process search, pagination and sorting parameters
if (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) {
    $currentPage = (int)$_GET['page'];
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = Utils::sanitize($_GET['search']);
}

if (isset($_GET['sort']) && in_array($_GET['sort'], ['username', 'email', 'role_name', 'created_at'])) {
    $sortField = $_GET['sort'];
}

if (isset($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC'])) {
    $sortOrder = strtoupper($_GET['order']);
}

if (isset($_GET['role']) && !empty($_GET['role'])) {
    $filterRole = Utils::sanitize($_GET['role']);
}

// Calculate pagination offset
$offset = ($currentPage - 1) * $perPage;

// Get users with pagination, search, and sorting
try {
    $db = Database::getInstance();
    
    // Build query conditions
    $conditions = [];
    $params = [];
    
    // Filter by manageable roles (lower than current user)
    $roleHierarchyLevel = $roleManager->getRoleHierarchyLevel($currentUser['role_name']);
    $conditions[] = "r.level > :role_level";
    $params['role_level'] = $roleHierarchyLevel;
    
    // Filter by search term
    if (!empty($searchTerm)) {
        $conditions[] = "(u.username LIKE :search OR u.email LIKE :search OR u.civil_name LIKE :search)";
        $params['search'] = "%$searchTerm%";
    }
    
    // Filter by role
    if (!empty($filterRole)) {
        $conditions[] = "r.name = :role";
        $params['role'] = $filterRole;
    }
    
    $whereClause = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    // Count total users for pagination
    $countQuery = "SELECT COUNT(u.id) as total 
                   FROM users u 
                   JOIN roles r ON u.role_id = r.id 
                   $whereClause";
    
    $db->query($countQuery, $params);
    $result = $db->fetch();
    $totalUsers = $result['total'];
    $totalPages = ceil($totalUsers / $perPage);
    
    // Get users for the current page
    $usersQuery = "SELECT u.*, r.name as role_name 
                  FROM users u 
                  JOIN roles r ON u.role_id = r.id 
                  $whereClause
                  ORDER BY $sortField $sortOrder
                  LIMIT $offset, $perPage";
    
    $db->query($usersQuery, $params);
    $users = $db->fetchAll();
    
} catch (Exception $e) {
    $errorMessage = "Error al obtener la lista de usuarios: " . $e->getMessage();
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user']) && !empty($_POST['user_id'])) {
    $userId = (int)$_POST['user_id'];
    
    // Check if user exists and has a lower role
    $targetUser = $userManager->getUserById($userId);
    
    if ($targetUser && $roleManager->isRoleHigher($currentUser['role_name'], $targetUser['role_name'])) {
        try {
            $db->query("DELETE FROM users WHERE id = :id", ['id' => $userId]);
            
            if ($db->rowCount() > 0) {
                Utils::logActivity('user_deleted', $currentUser['id'], "Deleted user ID: $userId");
                $successMessage = "Usuario eliminado exitosamente.";
                
                // Redirect to clear POST data and avoid page refresh issues
                header("Location: manage.php?deleted=true");
                exit;
            } else {
                $errorMessage = "No se pudo eliminar el usuario.";
            }
        } catch (Exception $e) {
            $errorMessage = "Error al eliminar el usuario: " . $e->getMessage();
        }
    } else {
        $errorMessage = "No tienes permiso para eliminar este usuario o el usuario no existe.";
    }
}

// Check for status messages from redirects
if (isset($_GET['deleted']) && $_GET['deleted'] === 'true') {
    $successMessage = "Usuario eliminado exitosamente.";
}

if (isset($_GET['created']) && $_GET['created'] === 'true') {
    $successMessage = "Usuario creado exitosamente.";
}

if (isset($_GET['updated']) && $_GET['updated'] === 'true') {
    $successMessage = "Usuario actualizado exitosamente.";
}

// Set page title
$pageTitle = "Gestión de Usuarios";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Sistema de Autenticación</title>
    <link rel="stylesheet" href="/assets/css/auth.css">
    <style>
        /* User Management Page Styles */
        .container {
            max-width: 1200px;
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
        
        .controls {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .search-form input,
        .search-form select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .users-table th,
        .users-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .users-table th {
            background-color: #f9f9f9;
            font-weight: bold;
            cursor: pointer;
        }
        
        .users-table th:hover {
            background-color: #f0f0f0;
        }
        
        .users-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .users-table .actions {
            display: flex;
            gap: 5px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .pagination .page-item {
            margin: 0 5px;
        }
        
        .pagination .page-link {
            display: block;
            padding: 8px 12px;
            text-decoration: none;
            color: #4e73df;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .pagination .page-link:hover {
            background-color: #f0f0f0;
        }
        
        .pagination .active .page-link {
            background-color: #4e73df;
            color: white;
            border-color: #4e73df;
        }
        
        .pagination .disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            background-color: #f9f9f9;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-align: center;
            display: inline-block;
            text-decoration: none;
        }
        
        .btn-primary {
            background-color: #4e73df;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2e59d9;
        }
        
        .btn-danger {
            background-color: #e74a3b;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #d52a1a;
        }
        
        .btn-warning {
            background-color: #f6c23e;
            color: #212529;
        }
        
        .btn-warning:hover {
            background-color: #f4b619;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        /* Status badge styling */
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
        
        .sort-icon {
            display: inline-block;
            width: 10px;
            height: 10px;
            margin-left: 5px;
        }
        
        .sort-icon.asc:after {
            content: "▲";
            font-size: 0.7rem;
        }
        
        .sort-icon.desc:after {
            content: "▼";
            font-size: 0.7rem;
        }
        
        .empty-results {
            text-align: center;
            padding: 50px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
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
            <h2>Gestión de Usuarios</h2>
            <a href="create.php" class="btn btn-primary">Crear Usuario</a>
        </div>
        
        <div class="controls">
            <form method="get" class="search-form">
                <input type="text" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Buscar..." />
                
                <select name="role">
                    <option value="">Todos los rangos</option>
                    <?php foreach ($availableRoles as $role): ?>
                        <option value="<?php echo $role['name']; ?>" <?php echo $filterRole === $role['name'] ? 'selected' : ''; ?>>
                            <?php echo $role['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="btn btn-primary">Buscar</button>
                <a href="manage.php" class="btn btn-warning">Limpiar</a>
            </form>
            
            <div>
                Mostrando <?php echo count($users); ?> de <?php echo $totalUsers; ?> usuarios
            </div>
        </div>
        
        <?php if ($totalUsers > 0): ?>
            <div class="table-responsive">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>
                                <a href="?sort=username&order=<?php echo ($sortField === 'username' && $sortOrder === 'ASC') ? 'DESC' : 'ASC'; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo !empty($filterRole) ? '&role=' . urlencode($filterRole) : ''; ?>">
                                    Usuario
                                    <?php if ($sortField === 'username'): ?>
                                        <span class="sort-icon <?php echo strtolower($sortOrder); ?>"></span>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Nombre Civil</th>
                            <th>
                                <a href="?sort=email&order=<?php echo ($sortField === 'email' && $sortOrder === 'ASC') ? 'DESC' : 'ASC'; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo !empty($filterRole) ? '&role=' . urlencode($filterRole) : ''; ?>">
                                    Email
                                    <?php if ($sortField === 'email'): ?>
                                        <span class="sort-icon <?php echo strtolower($sortOrder); ?>"></span>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=role_name&order=<?php echo ($sortField === 'role_name' && $sortOrder === 'ASC') ? 'DESC' : 'ASC'; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo !empty($filterRole) ? '&role=' . urlencode($filterRole) : ''; ?>">
                                    Rango
                                    <?php if ($sortField === 'role_name'): ?>
                                        <span class="sort-icon <?php echo strtolower($sortOrder); ?>"></span>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=created_at&order=<?php echo ($sortField === 'created_at' && $sortOrder === 'ASC') ? 'DESC' : 'ASC'; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo !empty($filterRole) ? '&role=' . urlencode($filterRole) : ''; ?>">
                                    Fecha Creación
                                    <?php if ($sortField === 'created_at'): ?>
                                        <span class="sort-icon <?php echo strtolower($sortOrder); ?>"></span>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['civil_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge <?php echo strtolower($user['role_name']); ?>">
                                        <?php echo htmlspecialchars($user['role_name']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                <td class="actions">
                                    <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                    
                                    <form method="post" style="display: inline;" onsubmit="return confirm('¿Estás seguro de querer eliminar este usuario?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="delete_user" value="1">
                                        <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                    </form>
                                    
                                    <a href="view.php?id=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <div class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=1<?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo !empty($filterRole) ? '&role=' . urlencode($filterRole) : ''; ?>&sort=<?php echo $sortField; ?>&order=<?php echo $sortOrder; ?>">
                            &laquo; Primera
                        </a>
                    </div>
                    
                    <div class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo !empty($filterRole) ? '&role=' . urlencode($filterRole) : ''; ?>&sort=<?php echo $sortField; ?>&order=<?php echo $sortOrder; ?>">
                            &laquo;
                        </a>
                    </div>
                    
                    <?php
                    // Calculate range of pages to show
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    
                    // Ensure we always show 5 pages if available
                    if ($endPage - $startPage + 1 < 5 && $totalPages >= 5) {
                        if ($startPage == 1) {
                            $endPage = min($totalPages, 5);
                        } elseif ($endPage == $totalPages) {
                            $startPage = max(1, $totalPages - 4);
                        }
                    }
                    
                    // Print page links
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <div class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo !empty($filterRole) ? '&role=' . urlencode($filterRole) : ''; ?>&sort=<?php echo $sortField; ?>&order=<?php echo $sortOrder; ?>">
                                <?php echo $i; ?>
                            </a>
                        </div>
                    <?php endfor; ?>
                    
                    <div class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo !empty($filterRole) ? '&role=' . urlencode($filterRole) : ''; ?>&sort=<?php echo $sortField; ?>&order=<?php echo $sortOrder; ?>">
                            &raquo;
                        </a>
                    </div>
                    
                    <div class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo !empty($filterRole) ? '&role=' . urlencode($filterRole) : ''; ?>&sort=<?php echo $sortField; ?>&order=<?php echo $sortOrder; ?>">
                            Última &raquo;
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="empty-results">
                <h3>No se encontraron usuarios</h3>
                <p>No hay resultados para los filtros aplicados.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Confirm delete action
        function confirmDelete() {
            return confirm("¿Estás seguro que deseas eliminar este usuario? Esta acción no se puede deshacer.");
        }
    </script>
</body>
</html>
