<?php
/**
 * Servicio de Auditoría
 */

class AuditoriaService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Registrar acción en auditoría
     */
    public function registrar($usuarioId, $accion, $tabla = null, $registroId = null, $datosAnteriores = null, $datosNuevos = null) {
        // Verificar si auditoría está activa
        $stmt = $this->db->query(
            "SELECT valor FROM configuracion_sistema WHERE clave = 'auditoria_activa'"
        );
        $config = $stmt->fetch();
        
        if (!$config || $config['valor'] != '1') {
            return; // Auditoría desactivada
        }
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $this->db->query(
            "INSERT INTO auditoria (
                usuario_id, accion, tabla, registro_id,
                datos_anteriores, datos_nuevos, ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $usuarioId,
                $accion,
                $tabla,
                $registroId,
                $datosAnteriores ? json_encode($datosAnteriores) : null,
                $datosNuevos ? json_encode($datosNuevos) : null,
                $ipAddress,
                $userAgent
            ]
        );
    }
    
    /**
     * Obtener historial de auditoría (método mejorado con paginación)
     */
    public function obtenerHistorial($filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['usuario_id'])) {
            $where[] = "a.usuario_id = ?";
            $params[] = $filters['usuario_id'];
        }
        
        if (isset($filters['accion'])) {
            $where[] = "a.accion = ?";
            $params[] = $filters['accion'];
        }
        
        if (isset($filters['tabla'])) {
            $where[] = "a.tabla = ?";
            $params[] = $filters['tabla'];
        }
        
        if (isset($filters['fecha_desde'])) {
            $where[] = "DATE(a.fecha) >= ?";
            $params[] = $filters['fecha_desde'];
        }
        
        if (isset($filters['fecha_hasta'])) {
            $where[] = "DATE(a.fecha) <= ?";
            $params[] = $filters['fecha_hasta'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Paginación
        $page = isset($filters['page']) ? (int)$filters['page'] : 1;
        $perPage = isset($filters['per_page']) ? (int)$filters['per_page'] : 50;
        $offset = ($page - 1) * $perPage;
        
        // Contar total
        $countStmt = $this->db->query(
            "SELECT COUNT(*) as total
             FROM auditoria a
             WHERE $whereClause",
            $params
        );
        $total = $countStmt->fetch()['total'];
        
        $stmt = $this->db->query(
            "SELECT a.*, 
                    u.nombre as usuario_nombre,
                    u.apellido as usuario_apellido,
                    u.email as usuario_email
             FROM auditoria a
             LEFT JOIN usuarios u ON a.usuario_id = u.id
             WHERE $whereClause
             ORDER BY a.fecha DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
        
        $registros = $stmt->fetchAll();
        
        // Decodificar JSON
        foreach ($registros as &$registro) {
            if ($registro['datos_anteriores']) {
                $registro['datos_anteriores'] = json_decode($registro['datos_anteriores'], true);
            }
            if ($registro['datos_nuevos']) {
                $registro['datos_nuevos'] = json_decode($registro['datos_nuevos'], true);
            }
            if ($registro['cambios_significativos']) {
                $registro['cambios_significativos'] = json_decode($registro['cambios_significativos'], true);
            }
            if ($registro['metadata']) {
                $registro['metadata'] = json_decode($registro['metadata'], true);
            }
        }
        
        return [
            'data' => $registros,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }
}


