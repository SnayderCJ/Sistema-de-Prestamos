<?php
/**
 * Controlador de Impuestos
 */

class ImpuestoController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll($filters = []) {
        $where = ["activo = 1"];
        $params = [];
        
        if (isset($filters['aplica_a'])) {
            $where[] = "aplica_a = ?";
            $params[] = $filters['aplica_a'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT * FROM impuestos WHERE $whereClause ORDER BY nombre",
            $params
        );
        
        $impuestos = $stmt->fetchAll();
        sendResponse($impuestos);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT * FROM impuestos WHERE id = ?",
            [$id]
        );
        
        $impuesto = $stmt->fetch();
        
        if (!$impuesto) {
            sendError('Impuesto no encontrado', 404);
        }
        
        sendResponse($impuesto);
    }
    
    public function create($data) {
        $errors = [];
        
        if (!isset($data['codigo']) || empty($data['codigo'])) {
            $errors[] = 'Código es requerido';
        }
        
        if (!isset($data['nombre']) || empty($data['nombre'])) {
            $errors[] = 'Nombre es requerido';
        }
        
        if (!isset($data['valor']) || $data['valor'] < 0) {
            $errors[] = 'Valor inválido';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        // Verificar que el código no esté duplicado
        $stmt = $this->db->query(
            "SELECT id FROM impuestos WHERE codigo = ?",
            [$data['codigo']]
        );
        
        if ($stmt->fetch()) {
            sendError('Ya existe un impuesto con este código', 400);
        }
        
        $this->db->query(
            "INSERT INTO impuestos (codigo, nombre, tipo, valor, aplica_a, descripcion) 
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                strtoupper($data['codigo']),
                sanitizeInput($data['nombre']),
                $data['tipo'] ?? 'porcentaje',
                $data['valor'],
                $data['aplica_a'] ?? 'general',
                $data['descripcion'] ?? null
            ]
        );
        
        $impuestoId = $this->db->lastInsertId();
        $this->getById($impuestoId);
    }
    
    public function update($id, $data) {
        $updates = [];
        $params = [];
        
        $allowedFields = ['nombre', 'tipo', 'valor', 'aplica_a', 'activo', 'descripcion'];
        
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
            "UPDATE impuestos SET " . implode(', ', $updates) . " WHERE id = ?",
            $params
        );
        
        $this->getById($id);
    }
}


