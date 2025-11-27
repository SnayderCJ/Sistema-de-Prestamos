<?php
/**
 * Controlador de Reenganche
 */

require_once __DIR__ . '/../services/ReengancheService.php';

class ReengancheController {
    private $db;
    private $reengancheService;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->reengancheService = new ReengancheService();
    }
    
    public function procesar($data, $user) {
        $errors = [];
        
        if (!isset($data['prestamo_original_id']) || !$data['prestamo_original_id']) {
            $errors[] = 'ID de préstamo original requerido';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        // Solo supervisores pueden aprobar reenganches
        if ($user['rol'] !== 'supervisor' && $user['rol'] !== 'admin') {
            sendError('Solo supervisores pueden procesar reenganches', 403);
        }
        
        try {
            $resultado = $this->reengancheService->procesarReenganche(
                $data['prestamo_original_id'],
                $data,
                $user
            );
            
            sendResponse($resultado);
            
        } catch (Exception $e) {
            error_log("Error procesando reenganche: " . $e->getMessage());
            sendError('Error al procesar el reenganche: ' . $e->getMessage(), 500);
        }
    }
    
    public function getElegibles() {
        // Préstamos que pueden ser reenganchados
        $stmt = $this->db->query(
            "SELECT p.*, 
                    c.cedula as cliente_cedula,
                    c.nombre as cliente_nombre,
                    c.apellido as cliente_apellido,
                    (SELECT SUM(saldo_capital) FROM cuotas_prestamos WHERE prestamo_id = p.id AND estado != 'pagada') as saldo_pendiente,
                    (SELECT SUM(mora) FROM cuotas_prestamos WHERE prestamo_id = p.id) as mora_total
             FROM prestamos p
             LEFT JOIN clientes c ON p.cliente_id = c.id
             WHERE p.estado IN ('vigente', 'vencido')
             AND p.es_reenganche = 0
             HAVING saldo_pendiente > 0
             ORDER BY p.fecha_vencimiento ASC"
        );
        
        $prestamos = $stmt->fetchAll();
        sendResponse($prestamos);
    }
}


