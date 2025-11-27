<?php
/**
 * Controlador de Cheques Empresariales
 */

class ChequeEmpresarialController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        $errors = [];
        
        if (!isset($data['prestamo_id']) || !$data['prestamo_id']) {
            $errors[] = 'Préstamo es requerido';
        }
        
        if (!isset($data['numero_cheque']) || empty($data['numero_cheque'])) {
            $errors[] = 'Número de cheque es requerido';
        }
        
        if (!isset($data['banco']) || empty($data['banco'])) {
            $errors[] = 'Banco es requerido';
        }
        
        if (!isset($data['monto']) || $data['monto'] <= 0) {
            $errors[] = 'Monto inválido';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        // Verificar que el número de cheque no esté duplicado
        $stmt = $this->db->query(
            "SELECT id FROM cheques_empresariales WHERE numero_cheque = ?",
            [$data['numero_cheque']]
        );
        
        if ($stmt->fetch()) {
            sendError('Ya existe un cheque con este número', 400);
        }
        
        $this->db->query(
            "INSERT INTO cheques_empresariales (
                prestamo_id, numero_cheque, banco, numero_cuenta,
                monto, fecha_emision, fecha_vencimiento, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['prestamo_id'],
                $data['numero_cheque'],
                sanitizeInput($data['banco']),
                $data['numero_cuenta'] ?? null,
                $data['monto'],
                $data['fecha_emision'] ?? date('Y-m-d'),
                $data['fecha_vencimiento'],
                $data['observaciones'] ?? null
            ]
        );
        
        $chequeId = $this->db->lastInsertId();
        $this->getById($chequeId);
    }
    
    public function getAll($prestamoId = null) {
        $where = "1=1";
        $params = [];
        
        if ($prestamoId) {
            $where = "prestamo_id = ?";
            $params = [$prestamoId];
        }
        
        $stmt = $this->db->query(
            "SELECT c.*, p.numero_prestamo
             FROM cheques_empresariales c
             LEFT JOIN prestamos p ON c.prestamo_id = p.id
             WHERE $where
             ORDER BY c.fecha_emision DESC",
            $params
        );
        
        $cheques = $stmt->fetchAll();
        sendResponse($cheques);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT c.*, p.numero_prestamo
             FROM cheques_empresariales c
             LEFT JOIN prestamos p ON c.prestamo_id = p.id
             WHERE c.id = ?",
            [$id]
        );
        
        $cheque = $stmt->fetch();
        
        if (!$cheque) {
            sendError('Cheque no encontrado', 404);
        }
        
        sendResponse($cheque);
    }
    
    public function marcarCobrado($id, $data) {
        $this->db->query(
            "UPDATE cheques_empresariales SET 
                estado = 'cobrado',
                fecha_cobro = ?
             WHERE id = ?",
            [$data['fecha_cobro'] ?? date('Y-m-d'), $id]
        );
        
        $this->getById($id);
    }
}


