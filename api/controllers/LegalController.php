<?php
/**
 * Controlador de Asientos Legales
 */

class LegalController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function crearAsiento($data) {
        $errors = [];
        
        if (!isset($data['tipo_asiento']) || empty($data['tipo_asiento'])) {
            $errors[] = 'Tipo de asiento es requerido';
        }
        
        if (!isset($data['fecha_asiento']) || empty($data['fecha_asiento'])) {
            $errors[] = 'Fecha es requerida';
        }
        
        if (!isset($data['descripcion']) || empty($data['descripcion'])) {
            $errors[] = 'Descripción es requerida';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        $this->db->query(
            "INSERT INTO asientos_legales (
                prestamo_id, tipo_asiento, numero_expediente, tribunal,
                juez, fecha_asiento, descripcion, monto, estado,
                archivo_pdf, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['prestamo_id'] ?? null,
                $data['tipo_asiento'],
                $data['numero_expediente'] ?? null,
                $data['tribunal'] ?? null,
                $data['juez'] ?? null,
                $data['fecha_asiento'],
                sanitizeInput($data['descripcion']),
                $data['monto'] ?? null,
                $data['estado'] ?? 'pendiente',
                $data['archivo_pdf'] ?? null,
                $data['observaciones'] ?? null
            ]
        );
        
        $asientoId = $this->db->lastInsertId();
        $this->getById($asientoId);
    }
    
    public function getAll($filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['prestamo_id'])) {
            $where[] = "a.prestamo_id = ?";
            $params[] = $filters['prestamo_id'];
        }
        
        if (isset($filters['tipo_asiento'])) {
            $where[] = "a.tipo_asiento = ?";
            $params[] = $filters['tipo_asiento'];
        }
        
        if (isset($filters['estado'])) {
            $where[] = "a.estado = ?";
            $params[] = $filters['estado'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT a.*, p.numero_prestamo
             FROM asientos_legales a
             LEFT JOIN prestamos p ON a.prestamo_id = p.id
             WHERE $whereClause
             ORDER BY a.fecha_asiento DESC",
            $params
        );
        
        $asientos = $stmt->fetchAll();
        sendResponse($asientos);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT a.*, p.numero_prestamo
             FROM asientos_legales a
             LEFT JOIN prestamos p ON a.prestamo_id = p.id
             WHERE a.id = ?",
            [$id]
        );
        
        $asiento = $stmt->fetch();
        
        if (!$asiento) {
            sendError('Asiento legal no encontrado', 404);
        }
        
        sendResponse($asiento);
    }
    
    public function update($id, $data) {
        $updates = [];
        $params = [];
        
        $allowedFields = [
            'tipo_asiento', 'numero_expediente', 'tribunal', 'juez',
            'fecha_asiento', 'descripcion', 'monto', 'estado',
            'archivo_pdf', 'observaciones'
        ];
        
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
            "UPDATE asientos_legales SET " . implode(', ', $updates) . " WHERE id = ?",
            $params
        );
        
        $this->getById($id);
    }
}


