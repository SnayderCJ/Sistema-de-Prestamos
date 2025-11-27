<?php
/**
 * Servicio de Auditoría Avanzada
 */

class AuditoriaAvanzadaService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Registrar acción con más detalles
     */
    public function registrar($usuarioId, $accion, $tabla = null, $registroId = null, $datosAnteriores = null, $datosNuevos = null, $metadata = []) {
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
        $requestUri = $_SERVER['REQUEST_URI'] ?? null;
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        
        // Detectar cambios significativos
        $cambiosSignificativos = $this->detectarCambiosSignificativos($datosAnteriores, $datosNuevos);
        
        $this->db->query(
            "INSERT INTO auditoria (
                usuario_id, accion, tabla, registro_id,
                datos_anteriores, datos_nuevos, ip_address, user_agent,
                request_uri, request_method, cambios_significativos, metadata
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $usuarioId,
                $accion,
                $tabla,
                $registroId,
                $datosAnteriores ? json_encode($datosAnteriores) : null,
                $datosNuevos ? json_encode($datosNuevos) : null,
                $ipAddress,
                $userAgent,
                $requestUri,
                $requestMethod,
                $cambiosSignificativos ? json_encode($cambiosSignificativos) : null,
                !empty($metadata) ? json_encode($metadata) : null
            ]
        );
    }
    
    /**
     * Detectar cambios significativos entre datos anteriores y nuevos
     */
    private function detectarCambiosSignificativos($datosAnteriores, $datosNuevos) {
        if (!$datosAnteriores || !$datosNuevos) {
            return null;
        }
        
        $cambios = [];
        $camposImportantes = ['monto_aprobado', 'estado', 'tasa_interes_id', 'plazo_meses', 'cuota_mensual'];
        
        foreach ($camposImportantes as $campo) {
            $valorAnterior = $datosAnteriores[$campo] ?? null;
            $valorNuevo = $datosNuevos[$campo] ?? null;
            
            if ($valorAnterior !== $valorNuevo) {
                $cambios[$campo] = [
                    'anterior' => $valorAnterior,
                    'nuevo' => $valorNuevo
                ];
            }
        }
        
        return !empty($cambios) ? $cambios : null;
    }
    
    /**
     * Obtener estadísticas de auditoría
     */
    public function obtenerEstadisticas($filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['fecha_desde'])) {
            $where[] = "DATE(fecha) >= ?";
            $params[] = $filters['fecha_desde'];
        }
        
        if (isset($filters['fecha_hasta'])) {
            $where[] = "DATE(fecha) <= ?";
            $params[] = $filters['fecha_hasta'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Acciones más frecuentes
        $accionesStmt = $this->db->query(
            "SELECT accion, COUNT(*) as cantidad
             FROM auditoria
             WHERE $whereClause
             GROUP BY accion
             ORDER BY cantidad DESC
             LIMIT 10",
            $params
        );
        
        // Usuarios más activos
        $usuariosStmt = $this->db->query(
            "SELECT u.nombre, u.apellido, COUNT(*) as cantidad
             FROM auditoria a
             LEFT JOIN usuarios u ON a.usuario_id = u.id
             WHERE $whereClause
             GROUP BY a.usuario_id, u.nombre, u.apellido
             ORDER BY cantidad DESC
             LIMIT 10",
            $params
        );
        
        // Tablas más modificadas
        $tablasStmt = $this->db->query(
            "SELECT tabla, COUNT(*) as cantidad
             FROM auditoria
             WHERE $whereClause AND tabla IS NOT NULL
             GROUP BY tabla
             ORDER BY cantidad DESC
             LIMIT 10",
            $params
        );
        
        // Actividad por día
        $actividadStmt = $this->db->query(
            "SELECT DATE(fecha) as dia, COUNT(*) as cantidad
             FROM auditoria
             WHERE $whereClause
             GROUP BY DATE(fecha)
             ORDER BY dia DESC
             LIMIT 30",
            $params
        );
        
        return [
            'acciones_frecuentes' => $accionesStmt->fetchAll(),
            'usuarios_activos' => $usuariosStmt->fetchAll(),
            'tablas_modificadas' => $tablasStmt->fetchAll(),
            'actividad_por_dia' => $actividadStmt->fetchAll()
        ];
    }
    
    /**
     * Exportar auditoría a CSV
     */
    public function exportarCSV($filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['fecha_desde'])) {
            $where[] = "DATE(a.fecha) >= ?";
            $params[] = $filters['fecha_desde'];
        }
        
        if (isset($filters['fecha_hasta'])) {
            $where[] = "DATE(a.fecha) <= ?";
            $params[] = $filters['fecha_hasta'];
        }
        
        if (isset($filters['usuario_id'])) {
            $where[] = "a.usuario_id = ?";
            $params[] = $filters['usuario_id'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT 
                a.fecha,
                u.nombre as usuario_nombre,
                u.apellido as usuario_apellido,
                a.accion,
                a.tabla,
                a.registro_id,
                a.ip_address,
                a.request_uri
             FROM auditoria a
             LEFT JOIN usuarios u ON a.usuario_id = u.id
             WHERE $whereClause
             ORDER BY a.fecha DESC
             LIMIT 10000",
            $params
        );
        
        $registros = $stmt->fetchAll();
        
        // Generar CSV
        $csv = "Fecha,Usuario,Acción,Tabla,Registro ID,IP,URI\n";
        
        foreach ($registros as $registro) {
            $usuario = ($registro['usuario_nombre'] ?? '') . ' ' . ($registro['usuario_apellido'] ?? '');
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s\n",
                $registro['fecha'],
                $this->escaparCSV($usuario),
                $this->escaparCSV($registro['accion'] ?? ''),
                $this->escaparCSV($registro['tabla'] ?? ''),
                $registro['registro_id'] ?? '',
                $this->escaparCSV($registro['ip_address'] ?? ''),
                $this->escaparCSV($registro['request_uri'] ?? '')
            );
        }
        
        return $csv;
    }
    
    private function escaparCSV($value) {
        if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }
    
    /**
     * Limpiar auditoría antigua (mantener solo últimos N días)
     */
    public function limpiarAuditoriaAntigua($dias = 365) {
        $fechaLimite = date('Y-m-d', strtotime("-$dias days"));
        
        $stmt = $this->db->query(
            "DELETE FROM auditoria WHERE DATE(fecha) < ?",
            [$fechaLimite]
        );
        
        return $stmt->rowCount();
    }
}

