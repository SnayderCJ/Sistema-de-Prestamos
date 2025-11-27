<?php
/**
 * Controlador de Dashboard
 */

class DashboardController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getDashboard($user) {
        $estadisticas = $this->getEstadisticas($user);
        $prestamosVencidos = $this->getPrestamosVencidos($user);
        $cobrosHoy = $this->getCobrosHoy($user);
        
        sendResponse([
            'estadisticas' => $estadisticas,
            'prestamos_vencidos' => $prestamosVencidos,
            'cobros_hoy' => $cobrosHoy
        ]);
    }
    
    public function getEstadisticas($user) {
        $where = "1=1";
        $params = [];
        
        // Filtros por rol
        if ($user['rol'] === 'supervisor') {
            $where .= " AND supervisor_aprobador_id = ?";
            $params[] = $user['id'];
        } elseif ($user['rol'] === 'analista') {
            $where .= " AND usuario_creador_id = ?";
            $params[] = $user['id'];
        }
        
        // Total de préstamos
        $totalPrestamosStmt = $this->db->query(
            "SELECT COUNT(*) as total FROM prestamos WHERE $where",
            $params
        );
        $totalPrestamos = $totalPrestamosStmt->fetch()['total'];
        
        // Préstamos activos
        $prestamosActivosStmt = $this->db->query(
            "SELECT COUNT(*) as total, SUM(monto_aprobado) as monto_total 
             FROM prestamos 
             WHERE $where AND estado = 'vigente'",
            $params
        );
        $prestamosActivos = $prestamosActivosStmt->fetch();
        
        // Préstamos vencidos
        $prestamosVencidosStmt = $this->db->query(
            "SELECT COUNT(*) as total, SUM(monto_aprobado) as monto_total 
             FROM prestamos 
             WHERE $where AND estado = 'vencido'",
            $params
        );
        $prestamosVencidos = $prestamosVencidosStmt->fetch();
        
        // Cobros del mes
        $cobrosMesStmt = $this->db->query(
            "SELECT SUM(monto) as total 
             FROM pagos 
             WHERE MONTH(fecha_pago) = MONTH(CURDATE()) 
             AND YEAR(fecha_pago) = YEAR(CURDATE())",
            []
        );
        $cobrosMes = $cobrosMesStmt->fetch()['total'] ?? 0;
        
        // Mora total
        $moraStmt = $this->db->query(
            "SELECT SUM(mora) as total 
             FROM cuotas_prestamos cp
             LEFT JOIN prestamos p ON cp.prestamo_id = p.id
             WHERE $where AND cp.estado = 'vencida'",
            $params
        );
        $moraTotal = $moraStmt->fetch()['total'] ?? 0;
        
        return [
            'total_prestamos' => $totalPrestamos,
            'prestamos_activos' => [
                'cantidad' => $prestamosActivos['total'],
                'monto_total' => $prestamosActivos['monto_total'] ?? 0
            ],
            'prestamos_vencidos' => [
                'cantidad' => $prestamosVencidos['total'],
                'monto_total' => $prestamosVencidos['monto_total'] ?? 0
            ],
            'cobros_mes' => $cobrosMes,
            'mora_total' => $moraTotal
        ];
    }
    
    public function getPrestamosVencidos($user) {
        $where = "p.estado = 'vencido'";
        $params = [];
        
        if ($user['rol'] === 'supervisor') {
            $where .= " AND p.supervisor_aprobador_id = ?";
            $params[] = $user['id'];
        } elseif ($user['rol'] === 'analista') {
            $where .= " AND p.usuario_creador_id = ?";
            $params[] = $user['id'];
        }
        
        $stmt = $this->db->query(
            "SELECT p.*, 
                    c.cedula as cliente_cedula,
                    c.nombre as cliente_nombre,
                    c.apellido as cliente_apellido,
                    c.telefono,
                    (SELECT SUM(mora) FROM cuotas_prestamos WHERE prestamo_id = p.id) as mora_total
             FROM prestamos p
             LEFT JOIN clientes c ON p.cliente_id = c.id
             WHERE $where
             ORDER BY p.fecha_vencimiento ASC
             LIMIT 20",
            $params
        );
        
        return $stmt->fetchAll();
    }
    
    public function getCobrosHoy($user) {
        $where = "DATE(p.fecha_pago) = CURDATE()";
        $params = [];
        
        if ($user['rol'] === 'supervisor') {
            $where .= " AND p.sucursal_id = ?";
            $params[] = $user['sucursal_id'];
        }
        
        $stmt = $this->db->query(
            "SELECT p.*, 
                    pr.numero_prestamo,
                    c.cedula as cliente_cedula,
                    c.nombre as cliente_nombre,
                    c.apellido as cliente_apellido
             FROM pagos p
             LEFT JOIN prestamos pr ON p.prestamo_id = pr.id
             LEFT JOIN clientes c ON pr.cliente_id = c.id
             WHERE $where
             ORDER BY p.fecha_pago DESC",
            $params
        );
        
        return $stmt->fetchAll();
    }
}


