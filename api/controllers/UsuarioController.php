<?php
/**
 * Controlador de Usuarios
 */

class UsuarioController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll($page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        // Contar total
        $countStmt = $this->db->query("SELECT COUNT(*) as total FROM usuarios");
        $total = $countStmt->fetch()['total'];
        
        // Obtener usuarios
        $stmt = $this->db->query(
            "SELECT id, cedula, nombre, apellido, email, telefono, rol, sucursal_id, activo, fecha_creacion, ultimo_acceso
             FROM usuarios
             ORDER BY fecha_creacion DESC
             LIMIT ? OFFSET ?",
            [$perPage, $offset]
        );
        
        $usuarios = $stmt->fetchAll();
        
        sendPaginatedResponse($usuarios, $total, $page, $perPage);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT id, cedula, nombre, apellido, email, telefono, rol, sucursal_id, activo, fecha_creacion, ultimo_acceso
             FROM usuarios
             WHERE id = ?",
            [$id]
        );
        
        $usuario = $stmt->fetch();
        
        if (!$usuario) {
            sendError('Usuario no encontrado', 404);
        }
        
        sendResponse($usuario);
    }
    
    public function create($data) {
        $errors = [];
        
        if (!isset($data['cedula']) || !validateCedula($data['cedula'])) {
            $errors[] = 'Cédula inválida';
        }
        
        if (!isset($data['email']) || !validateEmail($data['email'])) {
            $errors[] = 'Email inválido';
        }
        
        if (!isset($data['password']) || strlen($data['password']) < 6) {
            $errors[] = 'Contraseña debe tener al menos 6 caracteres';
        }
        
        if (!isset($data['nombre']) || empty($data['nombre'])) {
            $errors[] = 'Nombre es requerido';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        // Verificar si ya existe
        $stmt = $this->db->query("SELECT id FROM usuarios WHERE email = ? OR cedula = ?", [$data['email'], $data['cedula']]);
        if ($stmt->fetch()) {
            sendError('Ya existe un usuario con este email o cédula', 400);
        }
        
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $this->db->query(
            "INSERT INTO usuarios (cedula, nombre, apellido, email, telefono, password, rol, sucursal_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['cedula'],
                sanitizeInput($data['nombre']),
                sanitizeInput($data['apellido']),
                $data['email'],
                $data['telefono'] ?? null,
                $passwordHash,
                $data['rol'] ?? 'analista',
                $data['sucursal_id'] ?? null
            ]
        );
        
        $usuarioId = $this->db->lastInsertId();
        $this->getById($usuarioId);
    }
    
    public function update($id, $data) {
        $updates = [];
        $params = [];
        
        $allowedFields = ['nombre', 'apellido', 'email', 'telefono', 'rol', 'sucursal_id', 'activo'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (isset($data['password']) && !empty($data['password'])) {
            $updates[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($updates)) {
            sendError('No hay campos para actualizar', 400);
        }
        
        $params[] = $id;
        $this->db->query(
            "UPDATE usuarios SET " . implode(', ', $updates) . " WHERE id = ?",
            $params
        );
        
        $this->getById($id);
    }
    
    public function delete($id) {
        // No eliminar, solo desactivar
        $this->db->query("UPDATE usuarios SET activo = 0 WHERE id = ?", [$id]);
        
        sendResponse(['message' => 'Usuario desactivado correctamente']);
    }
}


