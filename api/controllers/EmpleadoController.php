<?php
/**
 * Controlador de Empleados (Gestión Humana)
 * Según leyes de República Dominicana
 */

class EmpleadoController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll($filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['departamento_id'])) {
            $where[] = "e.departamento_id = ?";
            $params[] = $filters['departamento_id'];
        }
        
        if (isset($filters['estado'])) {
            $where[] = "e.estado = ?";
            $params[] = $filters['estado'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT e.*, 
                    u.nombre, u.apellido, u.email, u.telefono,
                    d.nombre as departamento_nombre
             FROM empleados e
             LEFT JOIN usuarios u ON e.usuario_id = u.id
             LEFT JOIN departamentos d ON e.departamento_id = d.id
             WHERE $whereClause
             ORDER BY u.apellido, u.nombre",
            $params
        );
        
        $empleados = $stmt->fetchAll();
        sendResponse($empleados);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT e.*, 
                    u.nombre, u.apellido, u.email, u.telefono,
                    d.nombre as departamento_nombre
             FROM empleados e
             LEFT JOIN usuarios u ON e.usuario_id = u.id
             LEFT JOIN departamentos d ON e.departamento_id = d.id
             WHERE e.id = ?",
            [$id]
        );
        
        $empleado = $stmt->fetch();
        
        if (!$empleado) {
            sendError('Empleado no encontrado', 404);
        }
        
        sendResponse($empleado);
    }
    
    public function create($data) {
        $errors = [];
        
        if (!isset($data['usuario_id']) || !$data['usuario_id']) {
            $errors[] = 'Usuario es requerido';
        }
        
        if (!isset($data['cargo']) || empty($data['cargo'])) {
            $errors[] = 'Cargo es requerido';
        }
        
        if (!isset($data['fecha_ingreso']) || empty($data['fecha_ingreso'])) {
            $errors[] = 'Fecha de ingreso es requerida';
        }
        
        if (!isset($data['salario_base']) || $data['salario_base'] <= 0) {
            $errors[] = 'Salario base inválido';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        // Verificar que el usuario no sea ya empleado
        $stmt = $this->db->query(
            "SELECT id FROM empleados WHERE usuario_id = ?",
            [$data['usuario_id']]
        );
        
        if ($stmt->fetch()) {
            sendError('Este usuario ya es empleado', 400);
        }
        
        $this->db->query(
            "INSERT INTO empleados (
                usuario_id, departamento_id, cargo, fecha_ingreso,
                salario_base, tipo_contrato, horas_semanales,
                afp_numero, ars_numero, tss_numero, estado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['usuario_id'],
                $data['departamento_id'] ?? null,
                sanitizeInput($data['cargo']),
                $data['fecha_ingreso'],
                $data['salario_base'],
                $data['tipo_contrato'] ?? 'indefinido',
                $data['horas_semanales'] ?? 40,
                $data['afp_numero'] ?? null,
                $data['ars_numero'] ?? null,
                $data['tss_numero'] ?? null,
                $data['estado'] ?? 'activo'
            ]
        );
        
        $empleadoId = $this->db->lastInsertId();
        $this->getById($empleadoId);
    }
    
    public function update($id, $data) {
        $updates = [];
        $params = [];
        
        $allowedFields = [
            'departamento_id', 'cargo', 'fecha_salida', 'salario_base',
            'tipo_contrato', 'horas_semanales', 'afp_numero', 'ars_numero',
            'tss_numero', 'estado'
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
            "UPDATE empleados SET " . implode(', ', $updates) . " WHERE id = ?",
            $params
        );
        
        $this->getById($id);
    }
}


