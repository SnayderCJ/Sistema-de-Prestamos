<?php
/**
 * Servicio de Gestión de Sesiones
 */

class SesionService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Registrar actividad de sesión
     */
    public function registrarActividad($usuarioId, $accion = 'activity') {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $this->db->query(
            "INSERT INTO sesiones (usuario_id, accion, ip_address, user_agent, fecha_actividad)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE fecha_actividad = NOW()",
            [$usuarioId, $accion, $ipAddress, $userAgent]
        );
    }
    
    /**
     * Verificar si la sesión está inactiva
     */
    public function verificarInactividad($usuarioId, $tiempoLimiteMinutos = 30) {
        $stmt = $this->db->query(
            "SELECT fecha_actividad 
             FROM sesiones 
             WHERE usuario_id = ? 
             ORDER BY fecha_actividad DESC 
             LIMIT 1",
            [$usuarioId]
        );
        
        $sesion = $stmt->fetch();
        
        if (!$sesion) {
            return false; // No hay sesión registrada
        }
        
        $ultimaActividad = strtotime($sesion['fecha_actividad']);
        $tiempoLimite = time() - ($tiempoLimiteMinutos * 60);
        
        return $ultimaActividad < $tiempoLimite;
    }
    
    /**
     * Bloquear sesión por inactividad
     */
    public function bloquearSesion($usuarioId) {
        // Revocar todos los tokens del usuario
        $this->db->query(
            "UPDATE refresh_tokens SET revoked = 1 WHERE usuario_id = ?",
            [$usuarioId]
        );
        
        // Registrar bloqueo
        $this->db->query(
            "INSERT INTO bloqueos_sesion (usuario_id, motivo, fecha_bloqueo)
             VALUES (?, 'inactividad', NOW())",
            [$usuarioId]
        );
    }
    
    /**
     * Obtener sesiones activas del usuario
     */
    public function obtenerSesionesActivas($usuarioId) {
        $stmt = $this->db->query(
            "SELECT s.*, rt.token, rt.expires_at
             FROM sesiones s
             LEFT JOIN refresh_tokens rt ON s.usuario_id = rt.usuario_id AND rt.revoked = 0
             WHERE s.usuario_id = ?
             AND s.fecha_actividad > DATE_SUB(NOW(), INTERVAL 24 HOUR)
             ORDER BY s.fecha_actividad DESC",
            [$usuarioId]
        );
        
        return $stmt->fetchAll();
    }
    
    /**
     * Cerrar todas las sesiones del usuario
     */
    public function cerrarTodasLasSesiones($usuarioId) {
        // Revocar todos los tokens
        $this->db->query(
            "UPDATE refresh_tokens SET revoked = 1 WHERE usuario_id = ?",
            [$usuarioId]
        );
        
        // Eliminar sesiones
        $this->db->query(
            "DELETE FROM sesiones WHERE usuario_id = ?",
            [$usuarioId]
        );
    }
    
    /**
     * Cerrar sesión específica
     */
    public function cerrarSesion($usuarioId, $token) {
        // Revocar token específico
        $this->db->query(
            "UPDATE refresh_tokens SET revoked = 1 
             WHERE usuario_id = ? AND token = ?",
            [$usuarioId, $token]
        );
    }
}

