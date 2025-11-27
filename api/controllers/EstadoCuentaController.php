<?php
/**
 * Controlador de Estados de Cuenta
 */

class EstadoCuentaController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        $errors = [];
        
        if (!isset($data['cliente_id']) || !$data['cliente_id']) {
            $errors[] = 'Cliente es requerido';
        }
        
        if (!isset($data['banco']) || empty($data['banco'])) {
            $errors[] = 'Banco es requerido';
        }
        
        if (!isset($data['numero_cuenta']) || empty($data['numero_cuenta'])) {
            $errors[] = 'Número de cuenta es requerido';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        $this->db->query(
            "INSERT INTO estados_cuenta (
                cliente_id, banco, numero_cuenta, tipo_cuenta,
                saldo_promedio, fecha_consulta, archivo_pdf, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['cliente_id'],
                sanitizeInput($data['banco']),
                $data['numero_cuenta'],
                $data['tipo_cuenta'] ?? 'corriente',
                $data['saldo_promedio'] ?? null,
                $data['fecha_consulta'] ?? date('Y-m-d'),
                $data['archivo_pdf'] ?? null,
                $data['observaciones'] ?? null
            ]
        );
        
        $estadoId = $this->db->lastInsertId();
        $this->getById($estadoId);
    }
    
    public function getAll($clienteId = null) {
        $where = "1=1";
        $params = [];
        
        if ($clienteId) {
            $where = "cliente_id = ?";
            $params = [$clienteId];
        }
        
        $stmt = $this->db->query(
            "SELECT e.*, c.cedula, c.nombre, c.apellido
             FROM estados_cuenta e
             LEFT JOIN clientes c ON e.cliente_id = c.id
             WHERE $where
             ORDER BY e.fecha_consulta DESC",
            $params
        );
        
        $estados = $stmt->fetchAll();
        sendResponse($estados);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT e.*, c.cedula, c.nombre, c.apellido
             FROM estados_cuenta e
             LEFT JOIN clientes c ON e.cliente_id = c.id
             WHERE e.id = ?",
            [$id]
        );
        
        $estado = $stmt->fetch();
        
        if (!$estado) {
            sendError('Estado de cuenta no encontrado', 404);
        }
        
        sendResponse($estado);
    }
}


