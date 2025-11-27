<?php
/**
 * Controlador de Autenticación
 */

class AuthController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function login($data) {
        if (!isset($data['email']) || !isset($data['password'])) {
            sendError('Email y contraseña son requeridos', 400);
        }
        
        $email = sanitizeInput($data['email']);
        $password = $data['password'];
        
        $stmt = $this->db->query(
            "SELECT id, cedula, nombre, apellido, email, password, rol, sucursal_id, activo 
             FROM usuarios 
             WHERE email = ? AND activo = 1",
            [$email]
        );
        
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            sendError('Credenciales inválidas', 401);
        }
        
        // Generar token JWT
        $token = $this->generateToken($user['id'], $user['rol']);
        
        // Actualizar último acceso
        $this->db->query(
            "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?",
            [$user['id']]
        );
        
        unset($user['password']);
        
        // Generar refresh token
        $refreshToken = $this->generateRefreshToken($user['id']);
        
        sendResponse([
            'token' => $token,
            'refresh_token' => $refreshToken,
            'user' => $user,
            'expires_in' => JWT_EXPIRATION
        ]);
    }
    
    public function refreshToken($data) {
        if (!isset($data['refresh_token']) || empty($data['refresh_token'])) {
            sendError('Refresh token requerido', 400);
        }
        
        $refreshToken = $data['refresh_token'];
        
        // Verificar refresh token
        $stmt = $this->db->query(
            "SELECT rt.*, u.id, u.rol, u.activo 
             FROM refresh_tokens rt
             INNER JOIN usuarios u ON rt.usuario_id = u.id
             WHERE rt.token = ? 
             AND rt.revoked = 0 
             AND rt.expires_at > NOW()
             AND u.activo = 1",
            [$refreshToken]
        );
        
        $tokenData = $stmt->fetch();
        
        if (!$tokenData) {
            sendError('Refresh token inválido o expirado', 401);
        }
        
        // Generar nuevo access token
        $newToken = $this->generateToken($tokenData['usuario_id'], $tokenData['rol']);
        
        sendResponse([
            'token' => $newToken,
            'expires_in' => JWT_EXPIRATION
        ]);
    }
    
    public function forgotPassword($data) {
        if (!isset($data['email']) || empty($data['email'])) {
            sendError('Email requerido', 400);
        }
        
        $email = sanitizeInput($data['email']);
        
        // Buscar usuario
        $stmt = $this->db->query(
            "SELECT id, nombre, email FROM usuarios WHERE email = ? AND activo = 1",
            [$email]
        );
        
        $user = $stmt->fetch();
        
        if (!$user) {
            // Por seguridad, no revelar si el email existe o no
            sendResponse(['message' => 'Si el email existe, se enviará un enlace de recuperación']);
            return;
        }
        
        // Generar token de recuperación
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hora
        
        // Guardar token
        $this->db->query(
            "INSERT INTO password_resets (usuario_id, token, expires_at) VALUES (?, ?, ?)",
            [$user['id'], $token, $expiresAt]
        );
        
        // Enviar email con el enlace
        try {
            require_once __DIR__ . '/../utils/EmailService.php';
            $emailService = new EmailService();
            $emailService->enviarRecuperacionContrasena(
                $user['email'],
                $user['nombre'],
                $token
            );
            
            sendResponse([
                'message' => 'Se ha enviado un enlace de recuperación a tu email'
            ]);
        } catch (Exception $e) {
            error_log("Error enviando email de recuperación: " . $e->getMessage());
            // En desarrollo, retornar token si falla el email
            if (defined('DEBUG') && DEBUG) {
                sendResponse([
                    'message' => 'Se ha enviado un enlace de recuperación a tu email',
                    'token' => $token // Solo en desarrollo
                ]);
            } else {
                sendResponse([
                    'message' => 'Se ha enviado un enlace de recuperación a tu email'
                ]);
            }
        }
    }
    
    public function resetPassword($data) {
        if (!isset($data['token']) || empty($data['token'])) {
            sendError('Token requerido', 400);
        }
        
        if (!isset($data['password']) || strlen($data['password']) < 6) {
            sendError('Contraseña debe tener al menos 6 caracteres', 400);
        }
        
        $token = $data['token'];
        $password = $data['password'];
        
        // Verificar token
        $stmt = $this->db->query(
            "SELECT pr.*, u.id as usuario_id 
             FROM password_resets pr
             INNER JOIN usuarios u ON pr.usuario_id = u.id
             WHERE pr.token = ? 
             AND pr.used = 0 
             AND pr.expires_at > NOW()
             AND u.activo = 1",
            [$token]
        );
        
        $resetData = $stmt->fetch();
        
        if (!$resetData) {
            sendError('Token inválido o expirado', 400);
        }
        
        // Actualizar contraseña
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $this->db->getConnection()->beginTransaction();
        
        try {
            $this->db->query(
                "UPDATE usuarios SET password = ? WHERE id = ?",
                [$passwordHash, $resetData['usuario_id']]
            );
            
            // Marcar token como usado
            $this->db->query(
                "UPDATE password_resets SET used = 1 WHERE id = ?",
                [$resetData['id']]
            );
            
            // Revocar todos los refresh tokens del usuario
            $this->db->query(
                "UPDATE refresh_tokens SET revoked = 1 WHERE usuario_id = ?",
                [$resetData['usuario_id']]
            );
            
            $this->db->getConnection()->commit();
            
            sendResponse(['message' => 'Contraseña actualizada correctamente']);
            
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("Error resetting password: " . $e->getMessage());
            sendError('Error al actualizar la contraseña', 500);
        }
    }
    
    public function changePassword($data, $user) {
        if (!isset($data['current_password']) || !isset($data['new_password'])) {
            sendError('Contraseña actual y nueva contraseña son requeridas', 400);
        }
        
        if (strlen($data['new_password']) < 6) {
            sendError('La nueva contraseña debe tener al menos 6 caracteres', 400);
        }
        
        // Verificar contraseña actual
        $stmt = $this->db->query(
            "SELECT password FROM usuarios WHERE id = ?",
            [$user['id']]
        );
        
        $userData = $stmt->fetch();
        
        if (!password_verify($data['current_password'], $userData['password'])) {
            sendError('Contraseña actual incorrecta', 400);
        }
        
        // Actualizar contraseña
        $passwordHash = password_hash($data['new_password'], PASSWORD_DEFAULT);
        $this->db->query(
            "UPDATE usuarios SET password = ? WHERE id = ?",
            [$passwordHash, $user['id']]
        );
        
        // Revocar todos los refresh tokens del usuario
        $this->db->query(
            "UPDATE refresh_tokens SET revoked = 1 WHERE usuario_id = ?",
            [$user['id']]
        );
        
        sendResponse(['message' => 'Contraseña actualizada correctamente']);
    }
    
    public function logout($user) {
        // Revocar todos los refresh tokens del usuario
        $this->db->query(
            "UPDATE refresh_tokens SET revoked = 1 WHERE usuario_id = ?",
            [$user['id']]
        );
        
        sendResponse(['message' => 'Sesión cerrada correctamente']);
    }
    
    private function generateToken($userId, $rol) {
        // Implementación mejorada de JWT con validación de firma
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = base64_encode(json_encode([
            'user_id' => $userId,
            'rol' => $rol,
            'iat' => time(),
            'exp' => time() + JWT_EXPIRATION
        ]));
        
        $signature = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
        
        return "$header.$payload.$signature";
    }
    
    private function generateRefreshToken($userId) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 604800); // 7 días
        
        $this->db->query(
            "INSERT INTO refresh_tokens (usuario_id, token, expires_at) VALUES (?, ?, ?)",
            [$userId, $token, $expiresAt]
        );
        
        return $token;
    }
}

