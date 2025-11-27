<?php
/**
 * Controlador de Hipotecas
 */

class HipotecaController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        $errors = [];
        
        if (!isset($data['prestamo_id']) || !$data['prestamo_id']) {
            $errors[] = 'Préstamo es requerido';
        }
        
        if (!isset($data['direccion']) || empty($data['direccion'])) {
            $errors[] = 'Dirección es requerida';
        }
        
        if (!isset($data['valor_avaluo']) || $data['valor_avaluo'] <= 0) {
            $errors[] = 'Valor de avalúo inválido';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        $this->db->query(
            "INSERT INTO hipotecas (
                prestamo_id, tipo_propiedad, direccion, numero_catastral,
                area_metros, valor_avaluo, valor_garantia, fecha_avaluo,
                avaluador, numero_escritura, fecha_escritura, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['prestamo_id'],
                $data['tipo_propiedad'] ?? 'casa',
                sanitizeInput($data['direccion']),
                $data['numero_catastral'] ?? null,
                $data['area_metros'] ?? null,
                $data['valor_avaluo'],
                $data['valor_garantia'] ?? $data['valor_avaluo'],
                $data['fecha_avaluo'] ?? null,
                $data['avaluador'] ?? null,
                $data['numero_escritura'] ?? null,
                $data['fecha_escritura'] ?? null,
                $data['observaciones'] ?? null
            ]
        );
        
        $hipotecaId = $this->db->lastInsertId();
        $this->getById($hipotecaId);
    }
    
    public function getAll($prestamoId = null) {
        $where = "1=1";
        $params = [];
        
        if ($prestamoId) {
            $where = "prestamo_id = ?";
            $params = [$prestamoId];
        }
        
        $stmt = $this->db->query(
            "SELECT h.*, p.numero_prestamo
             FROM hipotecas h
             LEFT JOIN prestamos p ON h.prestamo_id = p.id
             WHERE $where
             ORDER BY h.id DESC",
            $params
        );
        
        $hipotecas = $stmt->fetchAll();
        sendResponse($hipotecas);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT h.*, p.numero_prestamo
             FROM hipotecas h
             LEFT JOIN prestamos p ON h.prestamo_id = p.id
             WHERE h.id = ?",
            [$id]
        );
        
        $hipoteca = $stmt->fetch();
        
        if (!$hipoteca) {
            sendError('Hipoteca no encontrada', 404);
        }
        
        sendResponse($hipoteca);
    }
}


