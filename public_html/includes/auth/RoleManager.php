<?php
/**
 * RoleManager Class
 * 
 * Manages user roles and permissions
 */

require_once __DIR__ . '/../db/Database.php';
require_once __DIR__ . '/../Utils.php';

class RoleManager {
    private static $instance = null;
    private $db;
    
    /**
     * Constructor - private for singleton pattern
     */
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get RoleManager instance (Singleton pattern)
     * 
     * @return RoleManager
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Check if one role is higher than another
     * 
     * @param string $role1 First role to check
     * @param string $role2 Second role to compare against
     * @return bool True if role1 is higher than role2, false otherwise
     */
    public function isRoleHigher($role1, $role2) {
        return Utils::isRoleHigher($role1, $role2);
    }
    
    /**
     * Check if a role is sufficient for user management
     * 
     * @param string $role Role to check
     * @return bool True if role allows user management
     */
    public function hasRequiredRoleForUserManagement($role) {
        // 'maestro' or higher roles can manage users
        return in_array($role, ['god', 'kk', 'sumo', 'sacerdote', 'maestro']);
    }
    
    /**
     * Update user role (only accessible by users with sufficient privileges)
     * 
     * @param int $userId ID of user to update
     * @param string $newRole New role to assign
     * @param int $updatedBy ID of user performing the update
     * @return array Result of role update
     */
    public function updateUserRole($userId, $newRole, $updatedBy) {
        $result = [
            'success' => false,
            'message' => ''
        ];
        
        // Get updater's role
        $this->db->query("SELECT r.name as role_name 
                         FROM users u 
                         JOIN roles r ON u.role_id = r.id 
                         WHERE u.id = :id", 
                         ['id' => $updatedBy]);
        
        $updater = $this->db->fetch();
        
        if (!$updater) {
            $result['message'] = "Error: No se pudo verificar el usuario que realiza la actualización.";
            return $result;
        }
        
        // Check if updater has permission (maestro or higher)
        if (!$this->hasRequiredRoleForUserManagement($updater['role_name'])) {
            $result['message'] = "Error: No tienes permiso para actualizar roles de usuarios.";
            return $result;
        }
        
        // Get user's current role
        $this->db->query("SELECT u.id, r.name as role_name 
                         FROM users u 
                         JOIN roles r ON u.role_id = r.id 
                         WHERE u.id = :id", 
                         ['id' => $userId]);
        
        $user = $this->db->fetch();
        
        if (!$user) {
            $result['message'] = "Error: Usuario no encontrado.";
            return $result;
        }
        
        // Check if updater has higher role than the user
        if (!$this->isRoleHigher($updater['role_name'], $user['role_name'])) {
            $result['message'] = "Error: Solo puedes actualizar usuarios con un rol inferior al tuyo.";
            return $result;
        }
        
        // Get new role ID
        $this->db->query("SELECT id FROM roles WHERE name = :name", 
                         ['name' => $newRole]);
        
        $role = $this->db->fetch();
        
        if (!$role) {
            $result['message'] = "Error: El rol especificado no existe.";
            return $result;
        }
        
        // Check if updater can assign this role (must be higher role)
        if (!$this->isRoleHigher($updater['role_name'], $newRole)) {
            $result['message'] = "Error: Solo puedes asignar roles inferiores al tuyo.";
            return $result;
        }
        
        // Update user role
        $this->db->query("UPDATE users SET role_id = :role_id WHERE id = :id", 
                         ['role_id' => $role['id'], 'id' => $userId]);
        
        if ($this->db->rowCount() > 0) {
            Utils::logActivity('role_updated', $updatedBy, "Updated user ID: {$userId} to role: {$newRole}");
            
            $result['success'] = true;
            $result['message'] = "Rol de usuario actualizado exitosamente.";
        } else {
            $result['message'] = "No se realizó ningún cambio. El usuario ya tiene ese rol.";
        }
        
        return $result;
    }
    
    /**
     * Get all available roles
     * 
     * @return array List of roles
     */
    public function getAllRoles() {
        $this->db->query("SELECT id, name, description, level 
                         FROM roles 
                         ORDER BY level");
        
        return $this->db->fetchAll();
    }
    
    /**
     * Get roles that a user can assign (roles lower than user's role)
     * 
     * @param string $userRole Current user's role
     * @return array List of assignable roles
     */
    public function getAssignableRoles($userRole) {
        // Get current user's hierarchy level
        $this->db->query("SELECT level 
                         FROM roles 
                         WHERE name = :name", 
                         ['name' => $userRole]);
        
        $currentRoleInfo = $this->db->fetch();
        
        if (!$currentRoleInfo) {
            return [];
        }
        
        // Get roles with lower hierarchy (higher level number)
        $this->db->query("SELECT id, name, description 
                         FROM roles 
                         WHERE level > :level 
                         ORDER BY level", 
                         ['level' => $currentRoleInfo['level']]);
        
        return $this->db->fetchAll();
    }
    
    /**
     * Get roles that a user can manage (roles lower than user's role)
     * 
     * @param string $userRole Current user's role
     * @return array List of manageable roles
     */
    public function getManageableRoles($userRole) {
        return $this->getAssignableRoles($userRole); // Same functionality as getAssignableRoles
    }
    
    /**
     * Get the hierarchy level for a role
     * 
     * @param string $roleName Name of the role
     * @return int|null Hierarchy level or null if role doesn't exist
     */
    public function getRoleHierarchyLevel($roleName) {
        $this->db->query("SELECT level FROM roles WHERE name = :name", ['name' => $roleName]);
        $roleInfo = $this->db->fetch();
        
        return $roleInfo ? $roleInfo['level'] : null;
    }
    
    /**
     * Check if a role can access a specific page or content
     * 
     * @param string $role Role name
     * @param string $contentType Type of content (page, post, etc.)
     * @param int $contentId Content ID
     * @return bool True if role has access
     */
    public function canRoleAccess($role, $contentType, $contentId) {
        // Get role's hierarchy level
        $this->db->query("SELECT level 
                         FROM roles 
                         WHERE name = :name", 
                         ['name' => $role]);
        
        $roleInfo = $this->db->fetch();
        
        if (!$roleInfo) {
            return false;
        }
        
        // Check if there's explicit permission for this role
        $this->db->query("SELECT id 
                         FROM content_permissions 
                         WHERE content_type = :type 
                         AND content_id = :id 
                         AND role_id = (SELECT id FROM roles WHERE name = :role)", 
                         [
                             'type' => $contentType,
                             'id' => $contentId,
                             'role' => $role
                         ]);
        
        if ($this->db->rowCount() > 0) {
            return true;
        }
        
        // Content might be restricted to specific roles
        // This logic would depend on your content access model
        // Here's a simple implementation that could be expanded:
        
        // Example: Check if content is restricted to a minimum role level
        $this->db->query("SELECT MIN(r.level) as min_level
                         FROM content_permissions cp
                         JOIN roles r ON cp.role_id = r.id
                         WHERE cp.content_type = :type 
                         AND cp.content_id = :id", 
                         [
                             'type' => $contentType,
                             'id' => $contentId
                         ]);
        
        $contentInfo = $this->db->fetch();
        
        // If there are no role restrictions or the role's level is less than or equal to the minimum required
        // (remember: lower level = higher privilege)
        if (!$contentInfo || !$contentInfo['min_level'] || $roleInfo['level'] <= $contentInfo['min_level']) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Allow a role to access specific content
     * 
     * @param string $contentType Type of content (page, post, etc.)
     * @param int $contentId Content ID
     * @param int $roleId Role ID
     * @param int $grantedBy ID of user granting permission
     * @return array Result of permission grant
     */
    public function grantRoleContentAccess($contentType, $contentId, $roleId, $grantedBy) {
        $result = [
            'success' => false,
            'message' => ''
        ];
        
        // Get granter's role
        $this->db->query("SELECT r.name as role_name 
                         FROM users u 
                         JOIN roles r ON u.role_id = r.id 
                         WHERE u.id = :id", 
                         ['id' => $grantedBy]);
        
        $granter = $this->db->fetch();
        
        // Get role info
        $this->db->query("SELECT name FROM roles WHERE id = :id", 
                         ['id' => $roleId]);
        
        $role = $this->db->fetch();
        
        if (!$granter || !$role) {
            $result['message'] = "Error: Información de usuario o rol no encontrada.";
            return $result;
        }
        
        // Check if granter has permission (maestro or higher)
        if (!$this->hasRequiredRoleForUserManagement($granter['role_name'])) {
            $result['message'] = "Error: No tienes permiso para otorgar accesos.";
            return $result;
        }
        
        // Check if permission already exists
        $this->db->query("SELECT id FROM content_permissions 
                         WHERE content_type = :type 
                         AND content_id = :id 
                         AND role_id = :role_id", 
                         [
                             'type' => $contentType,
                             'id' => $contentId,
                             'role_id' => $roleId
                         ]);
        
        if ($this->db->rowCount() > 0) {
            $result['message'] = "Este rol ya tiene acceso a este contenido.";
            return $result;
        }
        
        // Grant permission
        $this->db->query("INSERT INTO content_permissions 
                         (content_type, content_id, role_id, user_id, granted_by) 
                         VALUES (:type, :id, :role_id, NULL, :granted_by)", 
                         [
                             'type' => $contentType,
                             'id' => $contentId,
                             'role_id' => $roleId,
                             'granted_by' => $grantedBy
                         ]);
        
        if ($this->db->rowCount() > 0) {
            Utils::logActivity('permission_granted', $grantedBy, 
                               "Granted {$contentType}:{$contentId} access to role: {$role['name']}");
            
            $result['success'] = true;
            $result['message'] = "Acceso otorgado exitosamente al rol {$role['name']}.";
        } else {
            $result['message'] = "Error al otorgar acceso. Por favor, inténtalo de nuevo.";
        }
        
        return $result;
    }
    
    /**
     * Revoke content access for a role
     * 
     * @param string $contentType Type of content
     * @param int $contentId Content ID
     * @param int $roleId Role ID
     * @param int $revokedBy User ID revoking access
     * @return array Result of operation
     */
    public function revokeRoleContentAccess($contentType, $contentId, $roleId, $revokedBy) {
        $result = [
            'success' => false,
            'message' => ''
        ];
        
        // Check if user has permission to revoke
        $this->db->query("SELECT r.name as role_name 
                         FROM users u 
                         JOIN roles r ON u.role_id = r.id 
                         WHERE u.id = :id", 
                         ['id' => $revokedBy]);
        
        $revoker = $this->db->fetch();
        
        if (!$revoker || !$this->hasRequiredRoleForUserManagement($revoker['role_name'])) {
            $result['message'] = "No tienes permisos para revocar accesos.";
            return $result;
        }
        
        // Get role name for logging
        $this->db->query("SELECT name FROM roles WHERE id = :id", 
                         ['id' => $roleId]);
        
        $role = $this->db->fetch();
        
        // Delete permission
        $this->db->query("DELETE FROM content_permissions 
                         WHERE content_type = :type 
                         AND content_id = :id 
                         AND role_id = :role_id", 
                         [
                             'type' => $contentType,
                             'id' => $contentId,
                             'role_id' => $roleId
                         ]);
        
        if ($this->db->rowCount() > 0) {
            Utils::logActivity('permission_revoked', $revokedBy, 
                              "Revoked {$contentType}:{$contentId} access from role: {$role['name']}");
            
            $result['success'] = true;
            $result['message'] = "Acceso revocado exitosamente para el rol {$role['name']}.";
        } else {
            $result['message'] = "No se encontró ningún permiso para revocar.";
        }
        
        return $result;
    }
}
