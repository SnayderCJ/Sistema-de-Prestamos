<?php
/**
 * Controlador de Tipos de Comprobantes
 */

class TipoComprobanteController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAll() {
        $stmt = $this->db->query(
            "SELECT * FROM tipos_comprobantes WHERE activo = 1 ORDER BY codigo"
        );
        
        $tipos = $stmt->fetchAll();
        sendResponse($tipos);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT * FROM tipos_comprobantes WHERE id = ?",
            [$id]
        );
        
        $tipo = $stmt->fetch();
        
        if (!$tipo) {
            sendError('Tipo de comprobante no encontrado', 404);
        }
        
        sendResponse($tipo);
    }
}


