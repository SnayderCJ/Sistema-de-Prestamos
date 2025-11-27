<?php
/**
 * Controlador de Bonos a Cobradores
 */

class BonoCobradorController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function calcular($cobradorId, $periodo) {
        // Obtener cobros del período
        $fechaInicio = $this->obtenerFechaInicioPeriodo($periodo);
        $fechaFin = $this->obtenerFechaFinPeriodo($periodo);
        
        $stmt = $this->db->query(
            "SELECT SUM(monto) as total_cobrado
             FROM pagos
             WHERE usuario_id = ? 
             AND DATE(fecha_pago) BETWEEN ? AND ?",
            [$cobradorId, $fechaInicio, $fechaFin]
        );
        
        $result = $stmt->fetch();
        $montoCobrado = $result['total_cobrado'] ?? 0;
        
        // Obtener configuración de bonos
        $configStmt = $this->db->query(
            "SELECT valor FROM configuracion_sistema WHERE clave = 'bono_cobrador_porcentaje'"
        );
        $config = $configStmt->fetch();
        $porcentajeBono = $config ? (float)$config['valor'] : 2.0; // 2% por defecto
        
        $montoBono = $montoCobrado * ($porcentajeBono / 100);
        
        return [
            'cobrador_id' => $cobradorId,
            'periodo' => $periodo,
            'monto_cobrado' => $montoCobrado,
            'porcentaje' => $porcentajeBono,
            'monto_bono' => $montoBono
        ];
    }
    
    public function procesar($data) {
        $calculos = $this->calcular($data['cobrador_id'], $data['periodo']);
        
        // Verificar si ya existe bono para este período
        $stmt = $this->db->query(
            "SELECT id FROM bonos_cobradores WHERE cobrador_id = ? AND periodo = ?",
            [$data['cobrador_id'], $data['periodo']]
        );
        
        if ($stmt->fetch()) {
            sendError('Ya existe bono calculado para este período', 400);
        }
        
        $this->db->query(
            "INSERT INTO bonos_cobradores (
                cobrador_id, periodo, tipo_bono, monto_cobrado,
                porcentaje, monto_bono, fecha_calculo
            ) VALUES (?, ?, ?, ?, ?, ?, CURDATE())",
            [
                $data['cobrador_id'],
                $data['periodo'],
                $data['tipo_bono'] ?? 'por_cobro',
                $calculos['monto_cobrado'],
                $calculos['porcentaje'],
                $calculos['monto_bono']
            ]
        );
        
        $bonoId = $this->db->lastInsertId();
        $this->getById($bonoId);
    }
    
    public function getAll($filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['cobrador_id'])) {
            $where[] = "b.cobrador_id = ?";
            $params[] = $filters['cobrador_id'];
        }
        
        if (isset($filters['periodo'])) {
            $where[] = "b.periodo = ?";
            $params[] = $filters['periodo'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT b.*, 
                    u.nombre as cobrador_nombre,
                    u.apellido as cobrador_apellido
             FROM bonos_cobradores b
             LEFT JOIN usuarios u ON b.cobrador_id = u.id
             WHERE $whereClause
             ORDER BY b.fecha_calculo DESC",
            $params
        );
        
        $bonos = $stmt->fetchAll();
        sendResponse($bonos);
    }
    
    public function getById($id) {
        $stmt = $this->db->query(
            "SELECT b.*, 
                    u.nombre as cobrador_nombre,
                    u.apellido as cobrador_apellido
             FROM bonos_cobradores b
             LEFT JOIN usuarios u ON b.cobrador_id = u.id
             WHERE b.id = ?",
            [$id]
        );
        
        $bono = $stmt->fetch();
        
        if (!$bono) {
            sendError('Bono no encontrado', 404);
        }
        
        sendResponse($bono);
    }
    
    private function obtenerFechaInicioPeriodo($periodo) {
        // Formato: YYYY-MM
        if (preg_match('/^(\d{4})-(\d{2})$/', $periodo, $matches)) {
            return $matches[1] . '-' . $matches[2] . '-01';
        }
        return date('Y-m-01');
    }
    
    private function obtenerFechaFinPeriodo($periodo) {
        // Formato: YYYY-MM
        if (preg_match('/^(\d{4})-(\d{2})$/', $periodo, $matches)) {
            $year = $matches[1];
            $month = $matches[2];
            return date('Y-m-t', strtotime("$year-$month-01"));
        }
        return date('Y-m-t');
    }
}


