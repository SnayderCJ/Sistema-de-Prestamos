<?php
/**
 * Controlador de Monedas
 */

class MonedaController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll() {
        $stmt = $this->db->query(
            "SELECT * FROM monedas WHERE activa = 1 ORDER BY codigo"
        );
        
        $monedas = $stmt->fetchAll();
        sendResponse($monedas);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT * FROM monedas WHERE id = ?",
            [$id]
        );
        
        $moneda = $stmt->fetch();
        
        if (!$moneda) {
            sendError('Moneda no encontrada', 404);
        }
        
        sendResponse($moneda);
    }
    
    public function updateTasa($id, $data) {
        if (!isset($data['tasa_cambio']) || $data['tasa_cambio'] <= 0) {
            sendError('Tasa de cambio inválida', 400);
        }
        
        $this->db->query(
            "UPDATE monedas SET tasa_cambio = ?, fecha_actualizacion = NOW() WHERE id = ?",
            [$data['tasa_cambio'], $id]
        );
        
        $this->getById($id);
    }
}


