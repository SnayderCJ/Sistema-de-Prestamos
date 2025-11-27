<?php
/**
 * Controlador de Órdenes de Incautación
 */

class OrdenIncautacionController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($data) {
        $errors = [];
        
        if (!isset($data['prestamo_id']) || !$data['prestamo_id']) {
            $errors[] = 'Préstamo es requerido';
        }
        
        if (!isset($data['oficial_actuante']) || empty($data['oficial_actuante'])) {
            $errors[] = 'Oficial actuante es requerido';
        }
        
        if (!isset($data['dias_atraso']) || $data['dias_atraso'] < 0) {
            $errors[] = 'Días de atraso inválidos';
        }
        
        if (!empty($errors)) {
            sendError('Datos inválidos', 400, $errors);
        }
        
        // Calcular cargos legales según plazos de atraso
        $cargosLegales = $this->calcularCargosLegales($data['dias_atraso'], $data['prestamo_id']);
        
        // Generar número de orden
        $numeroOrden = $this->generarNumeroOrden();
        
        $this->db->query(
            "INSERT INTO ordenes_incautacion (
                prestamo_id, vehiculo_id, numero_orden, oficial_actuante,
                fecha_orden, motivo, dias_atraso, cargos_legales, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['prestamo_id'],
                $data['vehiculo_id'] ?? null,
                $numeroOrden,
                sanitizeInput($data['oficial_actuante']),
                $data['fecha_orden'] ?? date('Y-m-d'),
                $data['motivo'] ?? 'Incumplimiento de pago',
                $data['dias_atraso'],
                $cargosLegales,
                $data['observaciones'] ?? null
            ]
        );
        
        $ordenId = $this->db->lastInsertId();
        $this->getById($ordenId);
    }
    
    public function getAll($filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['prestamo_id'])) {
            $where[] = "o.prestamo_id = ?";
            $params[] = $filters['prestamo_id'];
        }
        
        if (isset($filters['estado'])) {
            $where[] = "o.estado = ?";
            $params[] = $filters['estado'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT o.*, 
                    p.numero_prestamo,
                    v.marca as vehiculo_marca,
                    v.modelo as vehiculo_modelo
             FROM ordenes_incautacion o
             LEFT JOIN prestamos p ON o.prestamo_id = p.id
             LEFT JOIN inventario_vehiculos v ON o.vehiculo_id = v.id
             WHERE $whereClause
             ORDER BY o.fecha_creacion DESC",
            $params
        );
        
        $ordenes = $stmt->fetchAll();
        sendResponse($ordenes);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT o.*, 
                    p.numero_prestamo,
                    v.marca as vehiculo_marca,
                    v.modelo as vehiculo_modelo
             FROM ordenes_incautacion o
             LEFT JOIN prestamos p ON o.prestamo_id = p.id
             LEFT JOIN inventario_vehiculos v ON o.vehiculo_id = v.id
             WHERE o.id = ?",
            [$id]
        );
        
        $orden = $stmt->fetch();
        
        if (!$orden) {
            sendError('Orden no encontrada', 404);
        }
        
        sendResponse($orden);
    }
    
    public function ejecutar($id, $data) {
        $this->db->query(
            "UPDATE ordenes_incautacion SET 
                estado = 'ejecutada',
                fecha_ejecucion = ?,
                observaciones = ?
             WHERE id = ?",
            [
                $data['fecha_ejecucion'] ?? date('Y-m-d'),
                $data['observaciones'] ?? null,
                $id
            ]
        );
        
        // Si hay vehículo, marcarlo como incautado
        $stmt = $this->db->query(
            "SELECT vehiculo_id FROM ordenes_incautacion WHERE id = ?",
            [$id]
        );
        
        $orden = $stmt->fetch();
        
        if ($orden && $orden['vehiculo_id']) {
            $this->db->query(
                "UPDATE inventario_vehiculos SET estado = 'incautado' WHERE id = ?",
                [$orden['vehiculo_id']]
            );
        }
        
        $this->getById($id);
    }
    
    private function calcularCargosLegales($diasAtraso, $prestamoId) {
        // Obtener plazos de atraso configurados
        $stmt = $this->db->query(
            "SELECT * FROM plazos_atraso 
             WHERE dias_desde <= ? AND dias_hasta >= ? AND activo = 1
             ORDER BY dias_desde DESC LIMIT 1",
            [$diasAtraso, $diasAtraso]
        );
        
        $plazo = $stmt->fetch();
        
        if (!$plazo) {
            return 0;
        }
        
        // Obtener monto del préstamo
        $prestamoStmt = $this->db->query(
            "SELECT monto_aprobado FROM prestamos WHERE id = ?",
            [$prestamoId]
        );
        
        $prestamo = $prestamoStmt->fetch();
        
        if (!$prestamo) {
            return 0;
        }
        
        $monto = $prestamo['monto_aprobado'];
        
        // Calcular cargo
        $cargo = 0;
        
        if ($plazo['cargo_porcentaje']) {
            $cargo = $monto * ($plazo['cargo_porcentaje'] / 100);
        }
        
        if ($plazo['cargo_fijo']) {
            $cargo += $plazo['cargo_fijo'];
        }
        
        return $cargo;
    }
    
    private function generarNumeroOrden() {
        $year = date('Y');
        $stmt = $this->db->query(
            "SELECT COUNT(*) as total FROM ordenes_incautacion WHERE YEAR(fecha_creacion) = ?",
            [$year]
        );
        $count = $stmt->fetch()['total'];
        $numero = str_pad($count + 1, 6, '0', STR_PAD_LEFT);
        return "ORD-$year-$numero";
    }
}


