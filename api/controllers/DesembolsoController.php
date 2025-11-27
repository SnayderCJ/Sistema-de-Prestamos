<?php
/**
 * Controlador de Desembolsos
 */

class DesembolsoController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data, $user) {
        $errors = [];
        
        if (!isset($data['prestamo_id']) || !$data['prestamo_id']) {
            $errors[] = 'Préstamo es requerido';
        }
        
        if (!isset($data['monto']) || $data['monto'] <= 0) {
            $errors[] = 'Monto inválido';
        }
        
        if (!isset($data['tipo_desembolso'])) {
            $errors[] = 'Tipo de desembolso es requerido';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        // Verificar que el préstamo existe y está aprobado
        $prestamoStmt = $this->db->query(
            "SELECT id, monto_aprobado, estado FROM prestamos WHERE id = ?",
            [$data['prestamo_id']]
        );
        
        $prestamo = $prestamoStmt->fetch();
        
        if (!$prestamo) {
            sendError('Préstamo no encontrado', 404);
        }
        
        if ($prestamo['estado'] !== 'aprobado') {
            sendError('El préstamo debe estar aprobado para desembolsar', 400);
        }
        
        // Verificar que hay caja abierta
        $cajaStmt = $this->db->query(
            "SELECT id FROM caja WHERE sucursal_id = ? AND estado = 'abierta'",
            [$user['sucursal_id']]
        );
        
        $caja = $cajaStmt->fetch();
        
        if (!$caja) {
            sendError('No hay caja abierta', 400);
        }
        
        $this->db->getConnection()->beginTransaction();
        
        try {
            // Crear desembolso
            $this->db->query(
                "INSERT INTO desembolsos (
                    prestamo_id, caja_id, tipo_desembolso, monto,
                    numero_comprobante, banco, numero_cuenta, usuario_id, observaciones
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['prestamo_id'],
                    $caja['id'],
                    $data['tipo_desembolso'],
                    $data['monto'],
                    $data['numero_comprobante'] ?? null,
                    $data['banco'] ?? null,
                    $data['numero_cuenta'] ?? null,
                    $user['id'],
                    $data['observaciones'] ?? null
                ]
            );
            
            $desembolsoId = $this->db->lastInsertId();
            
            // Actualizar monto en caja según tipo
            $campoCaja = 'monto_efectivo';
            if ($data['tipo_desembolso'] === 'transferencia') {
                $campoCaja = 'monto_transferencias';
            } elseif ($data['tipo_desembolso'] === 'cheque') {
                $campoCaja = 'monto_cheques';
            } elseif ($data['tipo_desembolso'] === 'tarjeta') {
                $campoCaja = 'monto_tarjetas';
            }
            
            $this->db->query(
                "UPDATE caja SET $campoCaja = $campoCaja - ? WHERE id = ?",
                [$data['monto'], $caja['id']]
            );
            
            // Actualizar estado del préstamo a desembolsado
            $this->db->query(
                "UPDATE prestamos SET estado = 'vigente' WHERE id = ?",
                [$data['prestamo_id']]
            );
            
            $this->db->getConnection()->commit();
            
            $this->getById($desembolsoId);
            
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("Error creando desembolso: " . $e->getMessage());
            sendError('Error al crear el desembolso', 500);
        }
    }
    
    public function getAll($filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['prestamo_id'])) {
            $where[] = "d.prestamo_id = ?";
            $params[] = $filters['prestamo_id'];
        }
        
        if (isset($filters['fecha_desde'])) {
            $where[] = "DATE(d.fecha_desembolso) >= ?";
            $params[] = $filters['fecha_desde'];
        }
        
        if (isset($filters['fecha_hasta'])) {
            $where[] = "DATE(d.fecha_desembolso) <= ?";
            $params[] = $filters['fecha_hasta'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT d.*, 
                    p.numero_prestamo,
                    c.numero_caja,
                    u.nombre as usuario_nombre,
                    u.apellido as usuario_apellido
             FROM desembolsos d
             LEFT JOIN prestamos p ON d.prestamo_id = p.id
             LEFT JOIN caja c ON d.caja_id = c.id
             LEFT JOIN usuarios u ON d.usuario_id = u.id
             WHERE $whereClause
             ORDER BY d.fecha_desembolso DESC",
            $params
        );
        
        $desembolsos = $stmt->fetchAll();
        sendResponse($desembolsos);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT d.*, 
                    p.numero_prestamo,
                    c.numero_caja
             FROM desembolsos d
             LEFT JOIN prestamos p ON d.prestamo_id = p.id
             LEFT JOIN caja c ON d.caja_id = c.id
             WHERE d.id = ?",
            [$id]
        );
        
        $desembolso = $stmt->fetch();
        
        if (!$desembolso) {
            sendError('Desembolso no encontrado', 404);
        }
        
        sendResponse($desembolso);
    }
}


