<?php
/**
 * PasswordManager Class
 * 
 * Handles password-related operations like reset, recovery, and changes
 */

require_once __DIR__ . '/../db/Database.php';
require_once __DIR__ . '/../Utils.php';

class PasswordManager {
    private static $instance = null;
    private $db;
    
    /**
     * Constructor - private for singleton pattern
     */
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get PasswordManager instance (Singleton pattern)
     * 
     * @return PasswordManager
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize password reset process
     * 
     * @param string $email User email
     * @return array Result of password reset initialization
     */
    public function initPasswordReset($email) {
        $result = [
            'success' => false,
            'message' => ''
        ];
        
        // Validate email
        if (!Utils::validateEmail($email)) {
            $result['message'] = "Por favor, introduce una dirección de email válida.";
            return $result;
        }
        
        // Get user by email
        $this->db->query("SELECT id, username FROM users WHERE email = :email", 
                         ['email' => $email]);
        
        $user = $this->db->fetch();
        
        if (!$user) {
            // For security, don't reveal that the email doesn't exist
            $result['success'] = true;
            $result['message'] = "Si tu email está registrado, recibirás un correo con instrucciones para restablecer tu contraseña.";
            return $result;
        }
        
        // Generate token
        $token = Utils::generateToken();
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration
        
        // Store token in database
        $this->db->query("INSERT INTO password_resets (user_id, token, expires_at) 
                         VALUES (:user_id, :token, :expires_at)", 
                         [
                             'user_id' => $user['id'],
                             'token' => $token,
                             'expires_at' => $expires
                         ]);
        
        if ($this->db->rowCount() > 0) {
            // Build reset URL
            $resetUrl = APP_URL . '/reset-password.php?token=' . urlencode($token);
            
            // Email content
            $subject = "Restablecimiento de contraseña - " . APP_NAME;
            $body = "
                <html>
                <head><title>Restablecimiento de contraseña</title></head>
                <body>
                    <h2>Restablecimiento de contraseña</h2>
                    <p>Hola {$user['username']},</p>
                    <p>Recibimos una solicitud para restablecer tu contraseña. Si no has solicitado este cambio, puedes ignorar este correo.</p>
                    <p>Para restablecer tu contraseña, haz clic en el siguiente enlace:</p>
                    <p><a href='{$resetUrl}'>{$resetUrl}</a></p>
                    <p>Este enlace expirará en 1 hora por seguridad.</p>
                    <p>Atentamente,<br>El equipo de " . APP_NAME . "</p>
                </body>
                </html>
            ";
            
            // Send email
            $emailSent = Utils::sendEmail($email, $subject, $body);
            
            if ($emailSent) {
                Utils::logActivity('password_reset_requested', $user['id']);
                
                $result['success'] = true;
                $result['message'] = "Se ha enviado un correo con instrucciones para restablecer tu contraseña.";
            } else {
                // Roll back the reset token if email fails
                $this->db->query("DELETE FROM password_resets WHERE token = :token", 
                                 ['token' => $token]);
                
                $result['message'] = "No se pudo enviar el correo. Por favor, inténtalo de nuevo más tarde.";
            }
        } else {
            $result['message'] = "Error al generar el token de restablecimiento. Por favor, inténtalo de nuevo.";
        }
        
        return $result;
    }
    
    /**
     * Verify password reset token
     * 
     * @param string $token Reset token
     * @return array Result of token verification
     */
    public function verifyResetToken($token) {
        $result = [
            'success' => false,
            'message' => '',
            'user_id' => null
        ];
        
        // Get token info
        $this->db->query("SELECT user_id, expires_at, used 
                         FROM password_resets 
                         WHERE token = :token", 
                         ['token' => $token]);
        
        $resetInfo = $this->db->fetch();
        
        if (!$resetInfo) {
            $result['message'] = "Token inválido o expirado.";
            return $result;
        }
        
        // Check if token is used
        if ($resetInfo['used']) {
            $result['message'] = "Este token ya ha sido utilizado.";
            return $result;
        }
        
        // Check if token is expired
        if (strtotime($resetInfo['expires_at']) < time()) {
            $result['message'] = "El token ha expirado. Por favor, solicita un nuevo restablecimiento de contraseña.";
            return $result;
        }
        
        $result['success'] = true;
        $result['user_id'] = $resetInfo['user_id'];
        
        return $result;
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
        $result = [
            'success' => false,
            'message' => ''
        ];
        
        // Verify token first
        $verifyResult = $this->verifyResetToken($token);
        
        if (!$verifyResult['success']) {
            return $verifyResult;
        }
        
        // Check if passwords match
        if ($newPassword !== $confirmPassword) {
            $result['message'] = "Las contraseñas no coinciden.";
            return $result;
        }
        
        // Validate password
        $passwordCheck = Utils::validatePassword($newPassword);
        if (!$passwordCheck['valid']) {
            $result['message'] = $passwordCheck['message'];
            return $result;
        }
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update user password
        $this->db->query("UPDATE users SET password = :password WHERE id = :id", 
                         [
                             'password' => $hashedPassword,
                             'id' => $verifyResult['user_id']
                         ]);
        
        if ($this->db->rowCount() > 0) {
            // Mark token as used
            $this->db->query("UPDATE password_resets SET used = 1 WHERE token = :token", 
                             ['token' => $token]);
            
            Utils::logActivity('password_changed', $verifyResult['user_id'], "Password reset via token");
            
            $result['success'] = true;
            $result['message'] = "Tu contraseña ha sido actualizada exitosamente. Ahora puedes iniciar sesión con tu nueva contraseña.";
        } else {
            $result['message'] = "Error al actualizar la contraseña. Por favor, inténtalo de nuevo.";
        }
        
        return $result;
    }
    
    /**
     * Update user password (when already logged in)
     * 
     * @param int $userId User ID
     * @param string $currentPassword Current password
     * @param string $newPassword New password
     * @param string $confirmPassword Confirmation of new password
     * @return array Result of password update
     */
    public function updatePassword($userId, $currentPassword, $newPassword, $confirmPassword) {
        $result = [
            'success' => false,
            'message' => ''
        ];
        
        // Get user data
        $this->db->query("SELECT password FROM users WHERE id = :id", 
                         ['id' => $userId]);
        
        $user = $this->db->fetch();
        
        if (!$user) {
            $result['message'] = "Usuario no encontrado.";
            return $result;
        }
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            $result['message'] = "La contraseña actual es incorrecta.";
            return $result;
        }
        
        // Check if passwords match
        if ($newPassword !== $confirmPassword) {
            $result['message'] = "Las nuevas contraseñas no coinciden.";
            return $result;
        }
        
        // Check if new password is different from current
        if ($currentPassword === $newPassword) {
            $result['message'] = "La nueva contraseña debe ser diferente a la actual.";
            return $result;
        }
        
        // Validate password
        $passwordCheck = Utils::validatePassword($newPassword);
        if (!$passwordCheck['valid']) {
            $result['message'] = $passwordCheck['message'];
            return $result;
        }
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $this->db->query("UPDATE users SET password = :password WHERE id = :id", 
                         [
                             'password' => $hashedPassword,
                             'id' => $userId
                         ]);
        
        if ($this->db->rowCount() > 0) {
            Utils::logActivity('password_changed', $userId, "Password updated by user");
            
            $result['success'] = true;
            $result['message'] = "Tu contraseña ha sido actualizada exitosamente.";
        } else {
            $result['message'] = "Error al actualizar la contraseña. Por favor, inténtalo de nuevo.";
        }
        
        return $result;
    }
}
