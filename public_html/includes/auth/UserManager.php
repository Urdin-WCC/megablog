<?php
/**
 * UserManager Class
 * 
 * Manages user operations like creation, updates, and queries
 */

require_once __DIR__ . '/../db/Database.php';
require_once __DIR__ . '/../Utils.php';
require_once __DIR__ . '/RoleManager.php';

class UserManager {
    private static $instance = null;
    private $db;
    private $roleManager;
    
    /**
     * Constructor - private for singleton pattern
     */
    private function __construct() {
        $this->db = Database::getInstance();
        $this->roleManager = RoleManager::getInstance();
    }
    
    /**
     * Get UserManager instance (Singleton pattern)
     * 
     * @return UserManager
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Create a new user (only accessible by users with role 'maestro' or higher)
     * 
     * @param array $userData User data (username, civil_name, email, password, role, etc.)
     * @param int $createdBy ID of user creating the new user
     * @return array Result of user creation
     */
    public function createUser($userData, $createdBy) {
        $result = [
            'success' => false,
            'message' => '',
            'user_id' => null
        ];
        
        // Get creator's role
        $this->db->query("SELECT r.name as role_name 
                         FROM users u 
                         JOIN roles r ON u.role_id = r.id 
                         WHERE u.id = :id", 
                         ['id' => $createdBy]);
        
        $creator = $this->db->fetch();
        
        if (!$creator) {
            $result['message'] = "Error: No se pudo verificar el usuario creador.";
            return $result;
        }
        
        // Check if creator has permission to create users (maestro or higher)
        if (!$this->roleManager->hasRequiredRoleForUserManagement($creator['role_name'])) {
            $result['message'] = "Error: No tienes permiso para crear nuevos usuarios.";
            return $result;
        }
        
        // Get role ID for the new user
        $this->db->query("SELECT id, name FROM roles WHERE name = :name", 
                         ['name' => $userData['role']]);
        
        $role = $this->db->fetch();
        
        if (!$role) {
            $result['message'] = "Error: El rol especificado no existe.";
            return $result;
        }
        
        // Check if creator can assign this role (must be higher role)
        if (!$this->roleManager->isRoleHigher($creator['role_name'], $role['name'])) {
            $result['message'] = "Error: Solo puedes crear usuarios con un rol inferior al tuyo.";
            return $result;
        }
        
        // Validate email
        if (!Utils::validateEmail($userData['email'])) {
            $result['message'] = "Error: Email inválido.";
            return $result;
        }
        
        // Check if email is already in use
        $this->db->query("SELECT id FROM users WHERE email = :email", 
                         ['email' => $userData['email']]);
        
        if ($this->db->rowCount() > 0) {
            $result['message'] = "Error: Este email ya está en uso.";
            return $result;
        }
        
        // Check if username is already in use
        $this->db->query("SELECT id FROM users WHERE username = :username", 
                         ['username' => $userData['username']]);
        
        if ($this->db->rowCount() > 0) {
            $result['message'] = "Error: Este nombre de usuario ya está en uso.";
            return $result;
        }
        
        // Validate password
        $passwordCheck = Utils::validatePassword($userData['password']);
        if (!$passwordCheck['valid']) {
            $result['message'] = $passwordCheck['message'];
            return $result;
        }
        
        // Hash password
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Prepare social links JSON if present
        $socialLinks = isset($userData['social_links']) ? json_encode($userData['social_links']) : null;
        
        // Insert new user
        $this->db->query("INSERT INTO users (
                            role_id, username, civil_name, email, phone, 
                            location, social_links, password, notes
                         ) VALUES (
                            :role_id, :username, :civil_name, :email, :phone, 
                            :location, :social_links, :password, :notes
                         )", [
                            'role_id' => $role['id'],
                            'username' => $userData['username'],
                            'civil_name' => $userData['civil_name'],
                            'email' => $userData['email'],
                            'phone' => isset($userData['phone']) ? $userData['phone'] : null,
                            'location' => isset($userData['location']) ? $userData['location'] : null,
                            'social_links' => $socialLinks,
                            'password' => $hashedPassword,
                            'notes' => isset($userData['notes']) ? $userData['notes'] : null
                         ]);
        
        $userId = $this->db->lastInsertId();
        
        if ($userId) {
            Utils::logActivity('user_created', $createdBy, "Created user ID: $userId");
            
            $result['success'] = true;
            $result['message'] = "Usuario creado exitosamente.";
            $result['user_id'] = $userId;
        } else {
            $result['message'] = "Error al crear el usuario. Por favor, inténtalo de nuevo.";
        }
        
        return $result;
    }
    
    /**
     * Get user data by ID
     * 
     * @param int $userId User ID
     * @return array|null User data or null if not found
     */
    public function getUserById($userId) {
        $this->db->query("SELECT u.*, r.name as role_name 
                         FROM users u 
                         JOIN roles r ON u.role_id = r.id 
                         WHERE u.id = :id", 
                         ['id' => $userId]);
        
        return $this->db->fetch();
    }
    
    /**
     * Get user by email
     * 
     * @param string $email User email
     * @return array|null User data or null if not found
     */
    public function getUserByEmail($email) {
        $this->db->query("SELECT u.*, r.name as role_name 
                         FROM users u 
                         JOIN roles r ON u.role_id = r.id 
                         WHERE u.email = :email", 
                         ['email' => $email]);
        
        return $this->db->fetch();
    }
    
    /**
     * Get users with role lower than current user's role
     * 
     * @param int $currentUserId ID of current user
     * @return array List of users
     */
    public function getManageableUsers($currentUserId) {
        // Get current user's role
        $this->db->query("SELECT r.name as role_name 
                         FROM users u 
                         JOIN roles r ON u.role_id = r.id 
                         WHERE u.id = :id", 
                         ['id' => $currentUserId]);
        
        $currentUser = $this->db->fetch();
        
        if (!$currentUser || !$this->roleManager->hasRequiredRoleForUserManagement($currentUser['role_name'])) {
            return [];
        }
        
        // Get hierarchy level
        $this->db->query("SELECT hierarchy_level 
                         FROM roles 
                         WHERE name = :role", 
                         ['role' => $currentUser['role_name']]);
        
        $roleInfo = $this->db->fetch();
        
        if (!$roleInfo) {
            return [];
        }
        
        // Get users with lower roles
        $this->db->query("SELECT u.*, r.name as role_name 
                         FROM users u 
                         JOIN roles r ON u.role_id = r.id 
                         WHERE r.hierarchy_level > :hierarchy_level 
                         ORDER BY r.hierarchy_level, u.username", 
                         ['hierarchy_level' => $roleInfo['hierarchy_level']]);
        
        return $this->db->fetchAll();
    }
    
    /**
     * Update user notes (only visible to higher ranks)
     * 
     * @param int $userId ID of user to update
     * @param string $notes New notes
     * @param int $updatedBy ID of user performing the update
     * @return array Result of notes update
     */
    public function updateUserNotes($userId, $notes, $updatedBy) {
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
        
        // Get target user's role
        $this->db->query("SELECT r.name as role_name 
                         FROM users u 
                         JOIN roles r ON u.role_id = r.id 
                         WHERE u.id = :id", 
                         ['id' => $userId]);
        
        $targetUser = $this->db->fetch();
        
        if (!$updater || !$targetUser) {
            $result['message'] = "Error: Usuario no encontrado.";
            return $result;
        }
        
        // Check if updater has permission (higher role)
        if (!$this->roleManager->isRoleHigher($updater['role_name'], $targetUser['role_name'])) {
            $result['message'] = "Error: No tienes permiso para actualizar las notas de este usuario.";
            return $result;
        }
        
        // Update notes
        $this->db->query("UPDATE users SET notes = :notes WHERE id = :id", 
                         ['notes' => $notes, 'id' => $userId]);
        
        if ($this->db->rowCount() > 0) {
            Utils::logActivity('notes_updated', $updatedBy, "Updated notes for user ID: $userId");
            
            $result['success'] = true;
            $result['message'] = "Notas actualizadas exitosamente.";
        } else {
            $result['message'] = "No se realizaron cambios en las notas.";
            $result['success'] = true; // Still consider it a success if nothing changed
        }
        
        return $result;
    }
    
    /**
     * Update user profile
     * 
     * @param int $userId User ID
     * @param array $profileData Profile data to update
     * @return array Result of profile update
     */
    public function updateProfile($userId, $profileData) {
        $result = [
            'success' => false,
            'message' => ''
        ];
        
        // Fields that users are allowed to update themselves
        $allowedFields = ['email', 'phone', 'location', 'social_links'];
        
        // Validate email if present
        if (isset($profileData['email'])) {
            if (!Utils::validateEmail($profileData['email'])) {
                $result['message'] = "Por favor, introduce una dirección de email válida.";
                return $result;
            }
            
            // Check if email is already in use by another user
            $this->db->query("SELECT id FROM users WHERE email = :email AND id != :id", 
                             ['email' => $profileData['email'], 'id' => $userId]);
            
            if ($this->db->rowCount() > 0) {
                $result['message'] = "Este email ya está en uso por otro usuario.";
                return $result;
            }
        }
        
        // Build update query
        $updateFields = [];
        $params = ['id' => $userId];
        
        foreach ($allowedFields as $field) {
            if (isset($profileData[$field])) {
                if ($field === 'social_links' && is_array($profileData[$field])) {
                    $updateFields[] = "$field = :$field";
                    $params[$field] = json_encode($profileData[$field]);
                } else {
                    $updateFields[] = "$field = :$field";
                    $params[$field] = $profileData[$field];
                }
            }
        }
        
        if (empty($updateFields)) {
            $result['message'] = "No se proporcionaron campos para actualizar.";
            return $result;
        }
        
        // Execute update
        $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $this->db->query($query, $params);
        
        if ($this->db->rowCount() > 0) {
            Utils::logActivity('profile_update', $userId);
            
            $result['success'] = true;
            $result['message'] = "Perfil actualizado exitosamente.";
        } else {
            $result['message'] = "No se realizaron cambios en el perfil.";
            $result['success'] = true; // Still consider it a success if nothing changed
        }
        
        return $result;
    }
    
    /**
     * Allow a user to access specific content
     * 
     * @param string $contentType Type of content (page, post, etc.)
     * @param int $contentId Content ID
     * @param int $userId User ID
     * @param int $grantedBy ID of user granting permission
     * @return array Result of permission grant
     */
    public function grantUserContentAccess($contentType, $contentId, $userId, $grantedBy) {
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
        
        // Get target user info
        $this->db->query("SELECT username, r.name as role_name 
                         FROM users u 
                         JOIN roles r ON u.role_id = r.id 
                         WHERE u.id = :id", 
                         ['id' => $userId]);
        
        $targetUser = $this->db->fetch();
        
        if (!$granter || !$targetUser) {
            $result['message'] = "Error: Información de usuario no encontrada.";
            return $result;
        }
        
        // Check if granter has permission (maestro or higher)
        if (!$this->roleManager->hasRequiredRoleForUserManagement($granter['role_name'])) {
            $result['message'] = "Error: No tienes permiso para otorgar accesos.";
            return $result;
        }
        
        // Check if permission already exists
        $this->db->query("SELECT id FROM content_permissions 
                         WHERE content_type = :type 
                         AND content_id = :id 
                         AND user_id = :user_id", 
                         [
                             'type' => $contentType,
                             'id' => $contentId,
                             'user_id' => $userId
                         ]);
        
        if ($this->db->rowCount() > 0) {
            $result['message'] = "Este usuario ya tiene acceso a este contenido.";
            return $result;
        }
        
        // Grant permission
        $this->db->query("INSERT INTO content_permissions 
                         (content_type, content_id, user_id, role_id, granted_by) 
                         VALUES (:type, :id, :user_id, NULL, :granted_by)", 
                         [
                             'type' => $contentType,
                             'id' => $contentId,
                             'user_id' => $userId,
                             'granted_by' => $grantedBy
                         ]);
        
        if ($this->db->rowCount() > 0) {
            Utils::logActivity('permission_granted', $grantedBy, 
                              "Granted {$contentType}:{$contentId} access to user: {$targetUser['username']}");
            
            $result['success'] = true;
            $result['message'] = "Acceso otorgado exitosamente al usuario {$targetUser['username']}.";
        } else {
            $result['message'] = "Error al otorgar acceso. Por favor, inténtalo de nuevo.";
        }
        
        return $result;
    }
    
    /**
     * Revoke content access for a user
     * 
     * @param string $contentType Type of content
     * @param int $contentId Content ID
     * @param int $userId User ID
     * @param int $revokedBy User ID revoking access
     * @return array Result of operation
     */
    public function revokeUserContentAccess($contentType, $contentId, $userId, $revokedBy) {
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
        
        if (!$revoker || !$this->roleManager->hasRequiredRoleForUserManagement($revoker['role_name'])) {
            $result['message'] = "No tienes permisos para revocar accesos.";
            return $result;
        }
        
        // Get user name for logging
        $this->db->query("SELECT username FROM users WHERE id = :id", 
                         ['id' => $userId]);
        
        $user = $this->db->fetch();
        
        // Delete permission
        $this->db->query("DELETE FROM content_permissions 
                         WHERE content_type = :type 
                         AND content_id = :id 
                         AND user_id = :user_id", 
                         [
                             'type' => $contentType,
                             'id' => $contentId,
                             'user_id' => $userId
                         ]);
        
        if ($this->db->rowCount() > 0) {
            Utils::logActivity('permission_revoked', $revokedBy, 
                              "Revoked {$contentType}:{$contentId} access from user: {$user['username']}");
            
            $result['success'] = true;
            $result['message'] = "Acceso revocado exitosamente para el usuario {$user['username']}.";
        } else {
            $result['message'] = "No se encontró ningún permiso para revocar.";
        }
        
        return $result;
    }
}
