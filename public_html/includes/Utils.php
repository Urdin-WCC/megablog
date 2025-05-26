<?php
/**
 * Utils Class
 * 
 * Contains utility functions for the authentication system
 */

class Utils {
    
    /**
     * Sanitize data to prevent XSS
     * 
     * @param mixed $data Data to be sanitized
     * @return mixed Sanitized data
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitize($value);
            }
        } else {
            $data = htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }
        
        return $data;
    }
    
    /**
     * Generate a secure random token
     * 
     * @param int $length Length of the token
     * @return string Generated token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Validate email format
     * 
     * @param string $email Email to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Check if a password meets the minimum requirements
     * 
     * @param string $password Password to check
     * @return array Result with status and message
     */
    public static function validatePassword($password) {
        $result = [
            'valid' => false,
            'message' => ''
        ];
        
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $result['message'] = "La contraseña debe tener al menos " . PASSWORD_MIN_LENGTH . " caracteres.";
            return $result;
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $result['message'] = "La contraseña debe contener al menos una letra mayúscula.";
            return $result;
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $result['message'] = "La contraseña debe contener al menos una letra minúscula.";
            return $result;
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $result['message'] = "La contraseña debe contener al menos un número.";
            return $result;
        }
        
        $result['valid'] = true;
        return $result;
    }
    
    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    public static function getClientIp() {
        $ipAddress = '';
        
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // To check if IP is passed from proxy
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
        } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipAddress = 'UNKNOWN';
        }
        
        return $ipAddress;
    }
    
    /**
     * Get user agent string
     * 
     * @return string User agent
     */
    public static function getUserAgent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'UNKNOWN';
    }
    
    /**
     * Send an email
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body
     * @param array $headers Additional headers
     * @return bool True on success, false on failure
     */
    public static function sendEmail($to, $subject, $body, $headers = []) {
        // This is a simplified email function
        // In production, you should use a proper email library like PHPMailer
        
        $defaultHeaders = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>'
        ];
        
        $allHeaders = array_merge($defaultHeaders, $headers);
        
        return mail($to, $subject, $body, implode("\r\n", $allHeaders));
    }
    
    /**
     * Redirect to a URL
     * 
     * @param string $url URL to redirect to
     * @param int $statusCode HTTP status code for redirection (default: 302)
     */
    public static function redirect($url, $statusCode = 302) {
        header('Location: ' . $url, true, $statusCode);
        exit;
    }
    
    /**
     * Secure session start
     */
    public static function secureSessionStart() {
        // Check if session has already started
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters - only if session not started
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            
            // Use a secure cookie in HTTPS environments
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', 1);
            }
            
            // Start session
            session_start();
        }
    }
    
    /**
     * Compare two roles to determine hierarchy
     * 
     * @param string $role1 First role
     * @param string $role2 Second role
     * @return int -1 if role1 is higher, 1 if role2 is higher, 0 if equal
     */
    public static function compareRoles($role1, $role2) {
        $roles = USER_ROLES;
        
        $index1 = array_search($role1, $roles);
        $index2 = array_search($role2, $roles);
        
        if ($index1 === false || $index2 === false) {
            return 0; // Invalid roles
        }
        
        if ($index1 < $index2) {
            return -1; // role1 is higher
        } else if ($index1 > $index2) {
            return 1; // role2 is higher
        }
        
        return 0; // Equal roles
    }
    
    /**
     * Check if one role is higher than another
     * 
     * @param string $role1 First role to check
     * @param string $role2 Second role to compare against
     * @return bool True if role1 is higher than role2, false otherwise
     */
    public static function isRoleHigher($role1, $role2) {
        return self::compareRoles($role1, $role2) === -1;
    }
    
    /**
     * Log an event in the activity_log table
     * 
     * @param string $action Action being logged
     * @param int|null $userId ID of the user (null for failed logins)
     * @param string|null $additionalInfo Additional information
     * @return bool True on success, false on failure
     */
    public static function logActivity($action, $userId = null, $additionalInfo = null) {
        try {
            $db = Database::getInstance();
            
            $params = [
                'action' => $action,
                'ip_address' => self::getClientIp(),
                'user_agent' => self::getUserAgent()
            ];
            
            if ($userId !== null) {
                $params['user_id'] = $userId;
            }
            
            if ($additionalInfo !== null) {
                $params['description'] = $additionalInfo;
            }
            
            $columns = array_keys($params);
            $placeholders = array_map(function($item) { return ':' . $item; }, $columns);
            
            $query = "INSERT INTO activity_log (" . implode(', ', $columns) . ") 
                     VALUES (" . implode(', ', $placeholders) . ")";
            
            return $db->query($query, $params);
            
        } catch (Exception $e) {
            if (DEBUG) {
                error_log('Log activity error: ' . $e->getMessage());
            }
            return false;
        }
    }
}
