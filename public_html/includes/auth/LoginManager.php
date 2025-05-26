<?php
/**
 * LoginManager Class
 * 
 * Manages user authentication and session
 */

require_once __DIR__ . '/../db/Database.php';
require_once __DIR__ . '/../Utils.php';

class LoginManager {
    private static $instance = null;
    private $db;
    private $currentUser = null;
    
    /**
     * Constructor - private for singleton pattern
     */
    private function __construct() {
        $this->db = Database::getInstance();
        
        // Start secure session
        Utils::secureSessionStart();
        
        // Check if user is already logged in
        if (isset($_SESSION['user_id'])) {
            $this->loadUser($_SESSION['user_id']);
        }
    }
    
    /**
     * Get LoginManager instance (Singleton pattern)
     * 
     * @return LoginManager
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Login a user
     * 
     * @param string $email User email
     * @param string $password User password
     * @param bool $remember Remember login with cookie
     * @return array Result of login attempt
     */
    public function login($email, $password, $remember = false) {
        $result = [
            'success' => false,
            'message' => '',
            'redirect' => '',
            'user' => null
        ];
        
        // Validate email
        if (!Utils::validateEmail($email)) {
            $result['message'] = "Por favor, introduce una dirección de email válida.";
            return $result;
        }
        
        // Get user by email
        $this->db->query("SELECT u.*, r.name as role_name 
                         FROM users u 
                         JOIN roles r ON u.role_id = r.id 
                         WHERE u.email = :email", 
                         ['email' => $email]);
        
        $user = $this->db->fetch();
        
        // Check if user exists
        if (!$user) {
            Utils::logActivity('failed_login', null, "Email no encontrado: $email");
            
            // Generic message for security
            $result['message'] = "Credenciales inválidas. Por favor, inténtalo de nuevo.";
            return $result;
        }
        
        // Check if account is locked
        if ($user['account_locked']) {
            $lockTime = strtotime($user['last_attempt_time']);
            $currentTime = time();
            
            // Check if lockout period has passed
            if (($currentTime - $lockTime) > LOCKOUT_TIME) {
                // Reset login attempts
                $this->db->query("UPDATE users SET login_attempts = 0, account_locked = 0 
                                 WHERE id = :id", 
                                 ['id' => $user['id']]);
            } else {
                $timeLeft = LOCKOUT_TIME - ($currentTime - $lockTime);
                $minutes = ceil($timeLeft / 60);
                
                Utils::logActivity('failed_login', $user['id'], "Cuenta bloqueada");
                
                $result['message'] = "Tu cuenta está temporalmente bloqueada. Por favor, inténtalo de nuevo dentro de $minutes minutos.";
                return $result;
            }
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            // Increment login attempts
            $attempts = $user['login_attempts'] + 1;
            $locked = ($attempts >= MAX_LOGIN_ATTEMPTS) ? 1 : 0;
            
            $this->db->query("UPDATE users SET login_attempts = :attempts, 
                             last_attempt_time = NOW(), account_locked = :locked 
                             WHERE id = :id", 
                             ['attempts' => $attempts, 'locked' => $locked, 'id' => $user['id']]);
            
            Utils::logActivity('failed_login', $user['id'], "Contraseña incorrecta");
            
            // Show warning about attempts left
            $attemptsLeft = MAX_LOGIN_ATTEMPTS - $attempts;
            
            if ($attemptsLeft <= 0) {
                $result['message'] = "Has excedido el número máximo de intentos. Tu cuenta ha sido bloqueada temporalmente.";
            } else {
                $result['message'] = "Credenciales inválidas. Te quedan $attemptsLeft intentos.";
            }
            
            return $result;
        }
        
        // Reset login attempts
        $this->db->query("UPDATE users SET login_attempts = 0, account_locked = 0 
                         WHERE id = :id", 
                         ['id' => $user['id']]);
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role_name'];
        $_SESSION['last_activity'] = time();
        
        // Set cookie if remember me is checked
        if ($remember) {
            $token = Utils::generateToken();
            $expiry = time() + COOKIE_LIFETIME;
            
            // Store token in database (in a real application, you'd have a separate remember tokens table)
            // For simplicity, we're just storing it in the session here
            $_SESSION['remember_token'] = $token;
            
            // Set cookie
            setcookie('remember', $token, $expiry, '/', '', false, true);
        }
        
        // Log successful login
        Utils::logActivity('login', $user['id']);
        
        $this->currentUser = $user;
        
        // Determine redirect based on role
        $redirectUrl = $this->getRoleHomepage($user['role_name']);
        
        $result['success'] = true;
        $result['message'] = "Inicio de sesión exitoso. Bienvenido, " . $user['username'] . "!";
        $result['redirect'] = $redirectUrl;
        $result['user'] = $user;
        
        return $result;
    }
    
    /**
     * Log out the current user
     * 
     * @return bool True on success
     */
    public function logout() {
        if ($this->isLoggedIn()) {
            Utils::logActivity('logout', $this->getCurrentUser()['id']);
        }
        
        // Clear session
        $_SESSION = [];
        
        // Delete remember cookie
        if (isset($_COOKIE['remember'])) {
            setcookie('remember', '', time() - 3600, '/', '', false, true);
        }
        
        // Destroy session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            session_destroy();
        }
        
        $this->currentUser = null;
        
        return true;
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool True if logged in
     */
    public function isLoggedIn() {
        return $this->currentUser !== null;
    }
    
    /**
     * Get current user data
     * 
     * @return array|null User data or null if not logged in
     */
    public function getCurrentUser() {
        return $this->currentUser;
    }
    
    /**
     * Get current user role
     * 
     * @return string|null Role name or null if not logged in
     */
    public function getUserRole() {
        return $this->isLoggedIn() ? $this->currentUser['role_name'] : null;
    }
    
    /**
     * Load user data by ID
     * 
     * @param int $userId User ID
     * @return bool True if user was loaded
     */
    private function loadUser($userId) {
        $this->db->query("SELECT u.*, r.name as role_name 
                         FROM users u 
                         JOIN roles r ON u.role_id = r.id 
                         WHERE u.id = :id", 
                         ['id' => $userId]);
        
        $user = $this->db->fetch();
        
        if ($user) {
            $this->currentUser = $user;
            return true;
        }
        
        return false;
    }
    
    /**
     * Get homepage URL for a specific role
     * 
     * @param string $role Role name
     * @return string Homepage URL
     */
    public function getRoleHomepage($role) {
        // Define homepage URLs for each role
        $homepages = [
            'god' => '/pages/god/dashboard.php',
            'kk' => '/pages/kk/dashboard.php',
            'sumo' => '/pages/sumo/dashboard.php',
            'sacerdote' => '/pages/sacerdote/dashboard.php',
            'maestro' => '/pages/maestro/dashboard.php',
            'iniciado' => '/pages/iniciado/dashboard.php',
            'novicio' => '/pages/novicio/dashboard.php',
            'adepto' => '/pages/adepto/dashboard.php'
        ];
        
        return isset($homepages[$role]) ? $homepages[$role] : '/index.php';
    }
}
