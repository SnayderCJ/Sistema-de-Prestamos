<?php
/**
 * Controlador de Departamentos
 */

class DepartamentoController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll() {
        $stmt = $this->db->query(
            "SELECT * FROM departamentos WHERE activo = 1 ORDER BY nombre"
        );
        
        $departamentos = $stmt->fetchAll();
        sendResponse($departamentos);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT * FROM departamentos WHERE id = ?",
            [$id]
        );
        
        $departamento = $stmt->fetch();
        
        if (!$departamento) {
            sendError('Departamento no encontrado', 404);
        }
        
        sendResponse($departamento);
    }
    
    public function create($data) {
        $errors = [];
        
        if (!isset($data['codigo']) || empty($data['codigo'])) {
            $errors[] = 'Código es requerido';
        }
        
        if (!isset($data['nombre']) || empty($data['nombre'])) {
            $errors[] = 'Nombre es requerido';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        // Verificar que el código no esté duplicado
        $stmt = $this->db->query(
            "SELECT id FROM departamentos WHERE codigo = ?",
            [$data['codigo']]
        );
        
        if ($stmt->fetch()) {
            sendError('Ya existe un departamento con este código', 400);
        }
        
        $this->db->query(
            "INSERT INTO departamentos (codigo, nombre, descripcion) VALUES (?, ?, ?)",
            [
                strtoupper($data['codigo']),
                sanitizeInput($data['nombre']),
                $data['descripcion'] ?? null
            ]
        );
        
        $departamentoId = $this->db->lastInsertId();
        $this->getById($departamentoId);
    }
    
    public function update($id, $data) {
        $updates = [];
        $params = [];
        
        $allowedFields = ['nombre', 'descripcion', 'activo'];
        
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
            "UPDATE departamentos SET " . implode(', ', $updates) . " WHERE id = ?",
            $params
        );
        
        $this->getById($id);
    }
}


