<?php
/**
 * Middleware de Autenticación
 */

class AuthMiddleware {
    
    public function authenticate() {
        $headers = getallheaders();
        $token = null;
        
        // Buscar token en headers
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }
        
        // Si no está en headers, buscar en POST/GET
        if (!$token && isset($_POST['token'])) {
            $token = $_POST['token'];
        }
        if (!$token && isset($_GET['token'])) {
            $token = $_GET['token'];
        }
        
        if (!$token) {
            return null;
        }
        
        $user = $this->validateToken($token);
        
        if ($user) {
            // Registrar actividad de sesión
            require_once __DIR__ . '/../services/SesionService.php';
            $sesionService = new SesionService();
            $sesionService->registrarActividad($user['id']);
            
            // Verificar inactividad
            $tiempoLimite = $this->obtenerTiempoLimiteInactividad();
            if ($sesionService->verificarInactividad($user['id'], $tiempoLimite)) {
                $sesionService->bloquearSesion($user['id']);
                return null; // Sesión bloqueada
            }
        }
        
        return $user;
    }
    
    private function obtenerTiempoLimiteInactividad() {
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT valor FROM configuracion_sistema WHERE clave = 'sesion_tiempo_inactividad'"
        );
        $config = $stmt->fetch();
        return $config ? (int)$config['valor'] : 30; // Default 30 minutos
    }
    
    private function validateToken($token) {
        try {
            // Decodificar JWT (implementación mejorada con validación de firma)
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }
            
            $header = $parts[0];
            $payload = $parts[1];
            $signature = $parts[2];
            
            // Validar firma
            $expectedSignature = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
            if ($signature !== $expectedSignature) {
                error_log("Token signature invalid");
                return null;
            }
            
            $payloadData = json_decode(base64_decode($payload), true);
            
            if (!$payloadData || !isset($payloadData['user_id']) || !isset($payloadData['exp'])) {
                return null;
            }
            
            // Verificar expiración
            if ($payloadData['exp'] < time()) {
                return null;
            }
            
            // Obtener usuario de la base de datos
            $db = Database::getInstance();
            $stmt = $db->query(
                "SELECT id, cedula, nombre, apellido, email, rol, sucursal_id, activo 
                 FROM usuarios 
                 WHERE id = ? AND activo = 1",
                [$payloadData['user_id']]
            );
            
            $user = $stmt->fetch();
            
            if (!$user) {
                return null;
            }
            
            return $user;
        } catch (Exception $e) {
            error_log("Error validando token: " . $e->getMessage());
            return null;
        }
    }
    
    public function requireRole($allowedRoles) {
        $user = $this->authenticate();
        
        if (!$user) {
            sendError('No autorizado', 401);
            exit;
        }
        
        if (!in_array($user['rol'], $allowedRoles)) {
            sendError('No tiene permisos para esta acción', 403);
            exit;
        }
        
        return $user;
    }
}

