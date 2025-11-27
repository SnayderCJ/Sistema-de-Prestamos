<?php
/**
 * Controlador de Caja
 */

class CajaController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function abrir($data, $user) {
        $errors = [];
        
        if (!isset($data['monto_inicial']) || $data['monto_inicial'] < 0) {
            $errors[] = 'Monto inicial inválido';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        // Verificar si ya hay una caja abierta
        $stmt = $this->db->query(
            "SELECT id FROM caja WHERE sucursal_id = ? AND estado = 'abierta'",
            [$user['sucursal_id']]
        );
        
        if ($stmt->fetch()) {
            sendError('Ya existe una caja abierta en esta sucursal', 400);
        }
        
        $this->db->query(
            "INSERT INTO caja (sucursal_id, usuario_id, monto_inicial, monto_efectivo) 
             VALUES (?, ?, ?, ?)",
            [
                $user['sucursal_id'],
                $user['id'],
                $data['monto_inicial'],
                $data['monto_inicial']
            ]
        );
        
        $cajaId = $this->db->lastInsertId();
        $this->getById($cajaId);
    }
    
    public function cerrar($id, $data, $user) {
        $stmt = $this->db->query(
            "SELECT * FROM caja WHERE id = ? AND estado = 'abierta'",
            [$id]
        );
        
        $caja = $stmt->fetch();
        
        if (!$caja) {
            sendError('Caja no encontrada o ya cerrada', 404);
        }
        
        // Verificar que el usuario tiene permisos
        if ($caja['usuario_id'] != $user['id'] && $user['rol'] !== 'admin') {
            sendError('No tiene permisos para cerrar esta caja', 403);
        }
        
        $montoFinal = $data['monto_final'] ?? $caja['monto_efectivo'];
        
        $this->db->query(
            "UPDATE caja SET 
                fecha_cierre = NOW(),
                monto_final = ?,
                monto_efectivo = ?,
                monto_cheques = ?,
                monto_transferencias = ?,
                monto_tarjetas = ?,
                estado = 'cerrada',
                observaciones = ?
             WHERE id = ?",
            [
                $montoFinal,
                $data['monto_efectivo'] ?? $caja['monto_efectivo'],
                $data['monto_cheques'] ?? 0,
                $data['monto_transferencias'] ?? 0,
                $data['monto_tarjetas'] ?? 0,
                $data['observaciones'] ?? null,
                $id
            ]
        );
        
        $this->getById($id);
    }
    
    public function getAll($filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['sucursal_id'])) {
            $where[] = "c.sucursal_id = ?";
            $params[] = $filters['sucursal_id'];
        }
        
        if (isset($filters['estado'])) {
            $where[] = "c.estado = ?";
            $params[] = $filters['estado'];
        }
        
        if (isset($filters['fecha_desde'])) {
            $where[] = "DATE(c.fecha_apertura) >= ?";
            $params[] = $filters['fecha_desde'];
        }
        
        if (isset($filters['fecha_hasta'])) {
            $where[] = "DATE(c.fecha_apertura) <= ?";
            $params[] = $filters['fecha_hasta'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT c.*, 
                    u.nombre as usuario_nombre,
                    u.apellido as usuario_apellido,
                    s.nombre as sucursal_nombre
             FROM caja c
             LEFT JOIN usuarios u ON c.usuario_id = u.id
             LEFT JOIN sucursales s ON c.sucursal_id = s.id
             WHERE $whereClause
             ORDER BY c.fecha_apertura DESC",
            $params
        );
        
        $cajas = $stmt->fetchAll();
        sendResponse($cajas);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT c.*, 
                    u.nombre as usuario_nombre,
                    u.apellido as usuario_apellido,
                    s.nombre as sucursal_nombre
             FROM caja c
             LEFT JOIN usuarios u ON c.usuario_id = u.id
             LEFT JOIN sucursales s ON c.sucursal_id = s.id
             WHERE c.id = ?",
            [$id]
        );
        
        $caja = $stmt->fetch();
        
        if (!$caja) {
            sendError('Caja no encontrada', 404);
        }
        
        sendResponse($caja);
    }
    
    public function getAbierta($sucursalId) {
        $stmt = $this->db->query(
            "SELECT * FROM caja WHERE sucursal_id = ? AND estado = 'abierta' ORDER BY fecha_apertura DESC LIMIT 1",
            [$sucursalId]
        );
        
        $caja = $stmt->fetch();
        
        if (!$caja) {
            sendError('No hay caja abierta', 404);
        }
        
        sendResponse($caja);
    }
}


