<?php
/**
 * Controlador de Plazos de Atraso
 */

class PlazoAtrasoController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll() {
        $stmt = $this->db->query(
            "SELECT * FROM plazos_atraso WHERE activo = 1 ORDER BY dias_desde ASC"
        );
        
        $plazos = $stmt->fetchAll();
        sendResponse($plazos);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT * FROM plazos_atraso WHERE id = ?",
            [$id]
        );
        
        $plazo = $stmt->fetch();
        
        if (!$plazo) {
            sendError('Plazo no encontrado', 404);
        }
        
        sendResponse($plazo);
    }
    
    public function create($data) {
        $errors = [];
        
        if (!isset($data['nombre']) || empty($data['nombre'])) {
            $errors[] = 'Nombre es requerido';
        }
        
        if (!isset($data['dias_desde']) || $data['dias_desde'] < 0) {
            $errors[] = 'Días desde inválidos';
        }
        
        if (!isset($data['dias_hasta']) || $data['dias_hasta'] < $data['dias_desde']) {
            $errors[] = 'Días hasta inválidos';
        }
        
        if (!isset($data['accion']) || empty($data['accion'])) {
            $errors[] = 'Acción es requerida';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        $this->db->query(
            "INSERT INTO plazos_atraso (
                nombre, dias_desde, dias_hasta, cargo_porcentaje,
                cargo_fijo, accion, activo
            ) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                sanitizeInput($data['nombre']),
                $data['dias_desde'],
                $data['dias_hasta'],
                $data['cargo_porcentaje'] ?? null,
                $data['cargo_fijo'] ?? null,
                $data['accion'],
                $data['activo'] ?? 1
            ]
        );
        
        $plazoId = $this->db->lastInsertId();
        $this->getById($plazoId);
    }
    
    public function update($id, $data) {
        $updates = [];
        $params = [];
        
        $allowedFields = [
            'nombre', 'dias_desde', 'dias_hasta', 'cargo_porcentaje',
            'cargo_fijo', 'accion', 'activo'
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
            "UPDATE plazos_atraso SET " . implode(', ', $updates) . " WHERE id = ?",
            $params
        );
        
        $this->getById($id);
    }
}


