<?php
/**
 * Controlador de Bancos
 */

class BancoController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll() {
        $stmt = $this->db->query(
            "SELECT * FROM bancos WHERE activo = 1 ORDER BY nombre"
        );
        
        $bancos = $stmt->fetchAll();
        sendResponse($bancos);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT * FROM bancos WHERE id = ?",
            [$id]
        );
        
        $banco = $stmt->fetch();
        
        if (!$banco) {
            sendError('Banco no encontrado', 404);
        }
        
        sendResponse($banco);
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
            "SELECT id FROM bancos WHERE codigo = ?",
            [$data['codigo']]
        );
        
        if ($stmt->fetch()) {
            sendError('Ya existe un banco con este código', 400);
        }
        
        $this->db->query(
            "INSERT INTO bancos (codigo, nombre, codigo_swift, codigo_ach, telefono, email, direccion) 
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                strtoupper($data['codigo']),
                sanitizeInput($data['nombre']),
                $data['codigo_swift'] ?? null,
                $data['codigo_ach'] ?? null,
                $data['telefono'] ?? null,
                $data['email'] ?? null,
                $data['direccion'] ?? null
            ]
        );
        
        $bancoId = $this->db->lastInsertId();
        $this->getById($bancoId);
    }
}


