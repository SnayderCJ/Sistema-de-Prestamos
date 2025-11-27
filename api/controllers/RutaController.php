<?php
/**
 * Controlador de Rutas de Supervisores
 */

class RutaController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll($user, $page = 1, $perPage = 20, $filters = []) {
        $offset = ($page - 1) * $perPage;
        $where = ["1=1"];
        $params = [];
        
        // Filtros por rol
        if ($user['rol'] === 'supervisor') {
            $where[] = "r.supervisor_id = ?";
            $params[] = $user['id'];
        } elseif ($user['rol'] === 'cobrador') {
            $where[] = "r.cobrador_id = ?";
            $params[] = $user['id'];
        }
        
        // Filtros adicionales
        if (isset($filters['fecha_desde'])) {
            $where[] = "r.fecha_ruta >= ?";
            $params[] = $filters['fecha_desde'];
        }
        
        if (isset($filters['fecha_hasta'])) {
            $where[] = "r.fecha_ruta <= ?";
            $params[] = $filters['fecha_hasta'];
        }
        
        if (isset($filters['estado'])) {
            $where[] = "r.estado = ?";
            $params[] = $filters['estado'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Contar total
        $countStmt = $this->db->query(
            "SELECT COUNT(*) as total FROM rutas_supervisores r WHERE $whereClause",
            $params
        );
        $total = $countStmt->fetch()['total'];
        
        // Obtener rutas
        $stmt = $this->db->query(
            "SELECT r.*, 
                    u.nombre as supervisor_nombre,
                    u.apellido as supervisor_apellido,
                    s.nombre as sucursal_nombre,
                    (SELECT COUNT(*) FROM visitas_ruta WHERE ruta_id = r.id) as total_visitas,
                    (SELECT COUNT(*) FROM visitas_ruta WHERE ruta_id = r.id AND resultado = 'exitoso') as visitas_exitosas
             FROM rutas_supervisores r
             LEFT JOIN usuarios u ON r.supervisor_id = u.id
             LEFT JOIN sucursales s ON r.sucursal_id = s.id
             WHERE $whereClause
             ORDER BY r.fecha_ruta DESC, r.fecha_creacion DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
        
        $rutas = $stmt->fetchAll();
        
        // Obtener visitas para cada ruta
        foreach ($rutas as &$ruta) {
            $visitasStmt = $this->db->query(
                "SELECT v.*, 
                        p.numero_prestamo,
                        c.cedula as cliente_cedula,
                        c.nombre as cliente_nombre,
                        c.apellido as cliente_apellido
                 FROM visitas_ruta v
                 LEFT JOIN prestamos p ON v.prestamo_id = p.id
                 LEFT JOIN clientes c ON v.cliente_id = c.id
                 WHERE v.ruta_id = ?
                 ORDER BY v.fecha_visita",
                [$ruta['id']]
            );
            $ruta['visitas'] = $visitasStmt->fetchAll();
        }
        
        sendPaginatedResponse($rutas, $total, $page, $perPage);
    }
    
    public function getById($id, $user) {
        $stmt = $this->db->query(
            "SELECT r.*, 
                    u.nombre as supervisor_nombre,
                    u.apellido as supervisor_apellido,
                    s.nombre as sucursal_nombre
             FROM rutas_supervisores r
             LEFT JOIN usuarios u ON r.supervisor_id = u.id
             LEFT JOIN sucursales s ON r.sucursal_id = s.id
             WHERE r.id = ?",
            [$id]
        );
        
        $ruta = $stmt->fetch();
        
        if (!$ruta) {
            sendError('Ruta no encontrada', 404);
        }
        
        // Verificar permisos
        if ($user['rol'] === 'supervisor' && $ruta['supervisor_id'] != $user['id']) {
            sendError('No tiene permisos para ver esta ruta', 403);
        }
        
        // Obtener visitas
        $visitasStmt = $this->db->query(
            "SELECT v.*, 
                    p.numero_prestamo,
                    p.monto_aprobado,
                    p.saldo_pendiente,
                    c.cedula as cliente_cedula,
                    c.nombre as cliente_nombre,
                    c.apellido as cliente_apellido,
                    c.telefono,
                    c.direccion
             FROM visitas_ruta v
             LEFT JOIN prestamos p ON v.prestamo_id = p.id
             LEFT JOIN clientes c ON v.cliente_id = c.id
             WHERE v.ruta_id = ?
             ORDER BY v.fecha_visita",
            [$id]
        );
        $ruta['visitas'] = $visitasStmt->fetchAll();
        
        sendResponse($ruta);
    }
    
    public function create($data, $user) {
        $errors = [];
        
        if (!isset($data['nombre_ruta']) || empty($data['nombre_ruta'])) {
            $errors[] = 'Nombre de ruta es requerido';
        }
        
        if (!isset($data['fecha_ruta']) || empty($data['fecha_ruta'])) {
            $errors[] = 'Fecha de ruta es requerida';
        }
        
        if (!isset($data['prestamos']) || !is_array($data['prestamos']) || empty($data['prestamos'])) {
            $errors[] = 'Debe incluir al menos un préstamo en la ruta';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        $this->db->getConnection()->beginTransaction();
        
        try {
            // Crear ruta
            $cobradorId = $data['cobrador_id'] ?? null;
            
            $stmt = $this->db->query(
                "INSERT INTO rutas_supervisores (
                    supervisor_id, cobrador_id, nombre_ruta, fecha_ruta, sucursal_id, estado
                ) VALUES (?, ?, ?, ?, ?, 'programada')",
                [
                    $user['id'],
                    $cobradorId,
                    sanitizeInput($data['nombre_ruta']),
                    $data['fecha_ruta'],
                    $user['sucursal_id'] ?? 1
                ]
            );
            
            $rutaId = $this->db->lastInsertId();
            
            // Crear visitas
            foreach ($data['prestamos'] as $prestamoData) {
                $prestamoId = $prestamoData['prestamo_id'] ?? null;
                $tipoVisita = $prestamoData['tipo_visita'] ?? 'cobro';
                
                if (!$prestamoId) {
                    continue;
                }
                
                // Obtener cliente del préstamo
                $prestamoStmt = $this->db->query(
                    "SELECT cliente_id FROM prestamos WHERE id = ?",
                    [$prestamoId]
                );
                $prestamo = $prestamoStmt->fetch();
                
                if (!$prestamo) {
                    continue;
                }
                
                $this->db->query(
                    "INSERT INTO visitas_ruta (
                        ruta_id, prestamo_id, cliente_id, tipo_visita, fecha_visita
                    ) VALUES (?, ?, ?, ?, ?)",
                    [
                        $rutaId,
                        $prestamoId,
                        $prestamo['cliente_id'],
                        $tipoVisita,
                        $data['fecha_ruta'] . ' 09:00:00' // Hora por defecto
                    ]
                );
            }
            
            $this->db->getConnection()->commit();
            
            $this->getById($rutaId, $user);
            
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("Error creando ruta: " . $e->getMessage());
            sendError('Error al crear la ruta', 500);
        }
    }
    
    public function update($id, $data, $user) {
        $updates = [];
        $params = [];
        
        $allowedFields = ['estado', 'observaciones', 'fecha_inicio', 'fecha_fin'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            sendError('No hay campos para actualizar', 400);
        }
        
        $params[] = $id;
        $this->db->query(
            "UPDATE rutas_supervisores SET " . implode(', ', $updates) . " WHERE id = ?",
            $params
        );
        
        // Si se actualiza una visita
        if (isset($data['visita_id'])) {
            $this->actualizarVisita($data['visita_id'], $data);
        }
        
        $this->getById($id, $user);
    }
    
    public function delete($id, $user) {
        // Solo se pueden eliminar rutas programadas
        $stmt = $this->db->query("SELECT estado FROM rutas_supervisores WHERE id = ?", [$id]);
        $ruta = $stmt->fetch();
        
        if (!$ruta) {
            sendError('Ruta no encontrada', 404);
        }
        
        if ($ruta['estado'] !== 'programada') {
            sendError('Solo se pueden eliminar rutas programadas', 400);
        }
        
        $this->db->query("DELETE FROM rutas_supervisores WHERE id = ?", [$id]);
        
        sendResponse(['message' => 'Ruta eliminada correctamente']);
    }
    
    private function actualizarVisita($visitaId, $data) {
        $updates = [];
        $params = [];
        
        $allowedFields = ['resultado', 'monto_cobrado', 'observaciones', 'latitud', 'longitud', 'fotos', 'firma_cliente'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'fotos') {
                    $updates[] = "$field = ?";
                    $params[] = json_encode($data[$field]);
                } else {
                    $updates[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
        }
        
        if (!empty($updates)) {
            $params[] = $visitaId;
            $this->db->query(
                "UPDATE visitas_ruta SET " . implode(', ', $updates) . " WHERE id = ?",
                $params
            );
        }
    }
}

