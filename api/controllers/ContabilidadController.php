<?php
/**
 * Controlador de Contabilidad
 */

class ContabilidadController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function crearAsiento($data, $user) {
        $errors = [];
        
        if (!isset($data['fecha']) || empty($data['fecha'])) {
            $errors[] = 'Fecha es requerida';
        }
        
        if (!isset($data['concepto']) || empty($data['concepto'])) {
            $errors[] = 'Concepto es requerido';
        }
        
        if (!isset($data['cuenta_contable']) || empty($data['cuenta_contable'])) {
            $errors[] = 'Cuenta contable es requerida';
        }
        
        if ((!isset($data['debe']) || $data['debe'] == 0) && 
            (!isset($data['haber']) || $data['haber'] == 0)) {
            $errors[] = 'Debe o Haber debe tener un valor';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        $numeroAsiento = $this->generarNumeroAsiento();
        
        $this->db->query(
            "INSERT INTO asientos_contables (
                numero_asiento, fecha, tipo, concepto,
                debe, haber, cuenta_contable, referencia, usuario_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $numeroAsiento,
                $data['fecha'],
                $data['tipo'] ?? 'diario',
                sanitizeInput($data['concepto']),
                $data['debe'] ?? 0,
                $data['haber'] ?? 0,
                $data['cuenta_contable'],
                $data['referencia'] ?? null,
                $user['id']
            ]
        );
        
        $asientoId = $this->db->lastInsertId();
        $this->getAsientoById($asientoId);
    }
    
    public function getAllAsientos($filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['fecha_desde'])) {
            $where[] = "a.fecha >= ?";
            $params[] = $filters['fecha_desde'];
        }
        
        if (isset($filters['fecha_hasta'])) {
            $where[] = "a.fecha <= ?";
            $params[] = $filters['fecha_hasta'];
        }
        
        if (isset($filters['cuenta_contable'])) {
            $where[] = "a.cuenta_contable = ?";
            $params[] = $filters['cuenta_contable'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT a.*, u.nombre as usuario_nombre
             FROM asientos_contables a
             LEFT JOIN usuarios u ON a.usuario_id = u.id
             WHERE $whereClause
             ORDER BY a.fecha DESC, a.numero_asiento DESC",
            $params
        );
        
        $asientos = $stmt->fetchAll();
        sendResponse($asientos);
    }
    
    public function getAsientoById($id) {
        $stmt = $this->db->query(
            "SELECT a.*, u.nombre as usuario_nombre
             FROM asientos_contables a
             LEFT JOIN usuarios u ON a.usuario_id = u.id
             WHERE a.id = ?",
            [$id]
        );
        
        $asiento = $stmt->fetch();
        
        if (!$asiento) {
            sendError('Asiento no encontrado', 404);
        }
        
        sendResponse($asiento);
    }
    
    private function generarNumeroAsiento() {
        $year = date('Y');
        $stmt = $this->db->query(
            "SELECT COUNT(*) as total FROM asientos_contables WHERE YEAR(fecha) = ?",
            [$year]
        );
        $count = $stmt->fetch()['total'];
        $numero = str_pad($count + 1, 6, '0', STR_PAD_LEFT);
        return "AS-$year-$numero";
    }
}


