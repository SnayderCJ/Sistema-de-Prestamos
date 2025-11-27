<?php
/**
 * Middleware de Rate Limiting
 */

class RateLimitMiddleware {
    private $db;
    private $maxRequests;
    private $timeWindow; // en segundos
    
    public function __construct($maxRequests = 100, $timeWindow = 3600) {
        $this->db = Database::getInstance();
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
    }
    
    public function check($ipAddress = null) {
        $ipAddress = $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        // Obtener límite de configuración
        $stmt = $this->db->query(
            "SELECT valor FROM configuracion_sistema WHERE clave = 'rate_limit_requests'"
        );
        $config = $stmt->fetch();
        $maxRequests = $config ? (int)$config['valor'] : $this->maxRequests;
        
        // Limpiar registros antiguos
        $this->db->query(
            "DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)",
            [$this->timeWindow]
        );
        
        // Contar requests en la ventana de tiempo
        $stmt = $this->db->query(
            "SELECT COUNT(*) as total 
             FROM rate_limits 
             WHERE ip_address = ? 
             AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)",
            [$ipAddress, $this->timeWindow]
        );
        
        $result = $stmt->fetch();
        $requestCount = $result['total'] ?? 0;
        
        if ($requestCount >= $maxRequests) {
            sendError('Límite de requests excedido. Intente más tarde.', 429);
            exit;
        }
        
        // Registrar request
        $this->db->query(
            "INSERT INTO rate_limits (ip_address) VALUES (?)",
            [$ipAddress]
        );
        
        return true;
    }
}


