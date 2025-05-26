<?php
/**
 * Auth Class
 * 
 * Main entry point for authentication system
 * Acts as a facade pattern integrating all authentication components
 */

require_once __DIR__ . '/../db/Database.php';
require_once __DIR__ . '/../Utils.php';
require_once __DIR__ . '/LoginManager.php';
require_once __DIR__ . '/RoleManager.php';
require_once __DIR__ . '/UserManager.php';
require_once __DIR__ . '/PasswordManager.php';

class Auth {
    private static $instance = null;
    
    private $loginManager;
    private $roleManager;
    private $userManager;
    private $passwordManager;
    
    /**
     * Constructor - private for singleton pattern
     */
    private function __construct() {
        $this->loginManager = LoginManager::getInstance();
        $this->roleManager = RoleManager::getInstance();
        $this->userManager = UserManager::getInstance();
        $this->passwordManager = PasswordManager::getInstance();
    }
    
    /**
     * Get Auth instance (Singleton pattern)
     * 
     * @return Auth
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * LOGIN AND SESSION MANAGEMENT
     * LoginManager methods
     */
    
    /**
     * Login a user
     * 
     * @param string $email User email
     * @param string $password User password
     * @param bool $remember Remember login with cookie
     * @return array Result of login attempt
     */
    public function login($email, $password, $remember = false) {
        return $this->loginManager->login($email, $password, $remember);
    }
    
    /**
     * Log out the current user
     * 
     * @return bool True on success
     */
    public function logout() {
        return $this->loginManager->logout();
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool True if logged in
     */
    public function isLoggedIn() {
        return $this->loginManager->isLoggedIn();
    }
    
    /**
     * Get current user data
     * 
     * @return array|null User data or null if not logged in
     */
    public function getCurrentUser() {
        return $this->loginManager->getCurrentUser();
    }
    
    /**
     * Get current user role
     * 
     * @return string|null Role name or null if not logged in
     */
    public function getUserRole() {
        return $this->loginManager->getUserRole();
    }
    
    /**
     * Get homepage URL for a specific role
     * 
     * @param string $role Role name
     * @return string Homepage URL
     */
    public function getRoleHomepage($role) {
        return $this->loginManager->getRoleHomepage($role);
    }
    
    /**
     * ROLE MANAGEMENT
     * RoleManager methods
     */
    
    /**
     * Check if one role is higher than another
     * 
     * @param string $role1 First role to check
     * @param string $role2 Second role to compare against
     * @return bool True if role1 is higher than role2, false otherwise
     */
    public function isRoleHigher($role1, $role2) {
        return $this->roleManager->isRoleHigher($role1, $role2);
    }
    
    /**
     * Check if a role is sufficient for user management
     * 
     * @param string $role Role to check
     * @return bool True if role allows user management
     */
    public function hasRequiredRoleForUserManagement($role) {
        return $this->roleManager->hasRequiredRoleForUserManagement($role);
    }
    
    /**
     * Update user role
     * 
     * @param int $userId ID of user to update
     * @param string $newRole New role to assign
     * @param int $updatedBy ID of user performing the update
     * @return array Result of role update
     */
    public function updateUserRole($userId, $newRole, $updatedBy) {
        return $this->roleManager->updateUserRole($userId, $newRole, $updatedBy);
    }
    
    /**
     * Get all available roles
     * 
     * @return array List of roles
     */
    public function getAllRoles() {
        return $this->roleManager->getAllRoles();
    }
    
    /**
     * Get roles that a user can assign
     * 
     * @param string $userRole Current user's role
     * @return array List of assignable roles
     */
    public function getAssignableRoles($userRole) {
        return $this->roleManager->getAssignableRoles($userRole);
    }
    
    /**
     * Check if a role can access specific content
     * 
     * @param string $role Role name
     * @param string $contentType Type of content 
     * @param int $contentId Content ID
     * @return bool True if role has access
     */
    public function canRoleAccess($role, $contentType, $contentId) {
        return $this->roleManager->canRoleAccess($role, $contentType, $contentId);
    }
    
    /**
     * Grant role content access
     * 
     * @param string $contentType Type of content
     * @param int $contentId Content ID
     * @param int $roleId Role ID
     * @param int $grantedBy ID of user granting permission
     * @return array Result of permission grant
     */
    public function grantRoleContentAccess($contentType, $contentId, $roleId, $grantedBy) {
        return $this->roleManager->grantRoleContentAccess($contentType, $contentId, $roleId, $grantedBy);
    }
    
    /**
     * Revoke role content access
     * 
     * @param string $contentType Type of content
     * @param int $contentId Content ID
     * @param int $roleId Role ID
     * @param int $revokedBy User ID revoking access
     * @return array Result of operation
     */
    public function revokeRoleContentAccess($contentType, $contentId, $roleId, $revokedBy) {
        return $this->roleManager->revokeRoleContentAccess($contentType, $contentId, $roleId, $revokedBy);
    }
    
    /**
     * USER MANAGEMENT
     * UserManager methods
     */
    
    /**
     * Create a new user
     * 
     * @param array $userData User data
     * @param int $createdBy ID of user creating the new user
     * @return array Result of user creation
     */
    public function createUser($userData, $createdBy) {
        return $this->userManager->createUser($userData, $createdBy);
    }
    
    /**
     * Get user by ID
     * 
     * @param int $userId User ID
     * @return array|null User data or null if not found
     */
    public function getUserById($userId) {
        return $this->userManager->getUserById($userId);
    }
    
    /**
     * Get user by email
     * 
     * @param string $email User email
     * @return array|null User data or null if not found
     */
    public function getUserByEmail($email) {
        return $this->userManager->getUserByEmail($email);
    }
    
    /**
     * Get users with role lower than current user's role
     * 
     * @param int $currentUserId ID of current user
     * @return array List of users
     */
    public function getManageableUsers($currentUserId) {
        return $this->userManager->getManageableUsers($currentUserId);
    }
    
    /**
     * Update user notes
     * 
     * @param int $userId ID of user to update
     * @param string $notes New notes
     * @param int $updatedBy ID of user performing the update
     * @return array Result of notes update
     */
    public function updateUserNotes($userId, $notes, $updatedBy) {
        return $this->userManager->updateUserNotes($userId, $notes, $updatedBy);
    }
    
    /**
     * Update user profile
     * 
     * @param int $userId User ID
     * @param array $profileData Profile data to update
     * @return array Result of profile update
     */
    public function updateProfile($userId, $profileData) {
        return $this->userManager->updateProfile($userId, $profileData);
    }
    
    /**
     * Grant user content access
     * 
     * @param string $contentType Type of content
     * @param int $contentId Content ID
     * @param int $userId User ID
     * @param int $grantedBy ID of user granting permission
     * @return array Result of permission grant
     */
    public function grantUserContentAccess($contentType, $contentId, $userId, $grantedBy) {
        return $this->userManager->grantUserContentAccess($contentType, $contentId, $userId, $grantedBy);
    }
    
    /**
     * Revoke user content access
     * 
     * @param string $contentType Type of content
     * @param int $contentId Content ID
     * @param int $userId User ID
     * @param int $revokedBy User ID revoking access
     * @return array Result of operation 
     */
    public function revokeUserContentAccess($contentType, $contentId, $userId, $revokedBy) {
        return $this->userManager->revokeUserContentAccess($contentType, $contentId, $userId, $revokedBy);
    }
    
    /**
     * PASSWORD MANAGEMENT
     * PasswordManager methods
     */
    
    /**
     * Initialize password reset process
     * 
     * @param string $email User email
     * @return array Result of password reset initialization
     */
    public function initPasswordReset($email) {
        return $this->passwordManager->initPasswordReset($email);
    }
    
    /**
     * Verify password reset token
     * 
     * @param string $token Reset token
     * @return array Result of token verification
     */
    public function verifyResetToken($token) {
        return $this->passwordManager->verifyResetToken($token);
    }
    
    /**
     * Complete password reset
     * 
     * @param string $token Reset token
     * @param string $newPassword New password
     * @param string $confirmPassword Confirmation of new password
     * @return array Result of password reset
     */
    public function completePasswordReset($token, $newPassword, $confirmPassword) {
        return $this->passwordManager->completePasswordReset($token, $newPassword, $confirmPassword);
    }
    
    /**
     * Update user password
     * 
     * @param int $userId User ID
     * @param string $currentPassword Current password
     * @param string $newPassword New password
     * @param string $confirmPassword Confirmation of new password
     * @return array Result of password update
     */
    public function updatePassword($userId, $currentPassword, $newPassword, $confirmPassword) {
        return $this->passwordManager->updatePassword($userId, $currentPassword, $newPassword, $confirmPassword);
    }
}
