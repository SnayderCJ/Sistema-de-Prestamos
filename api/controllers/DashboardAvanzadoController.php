<?php
/**
 * Controlador de Dashboard Avanzado con Gráficos y Estadísticas
 */

class DashboardAvanzadoController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener estadísticas avanzadas del dashboard
     */
    public function getEstadisticasAvanzadas($user, $filters = []) {
        // Validar fechas
        $fechaDesde = $filters['fecha_desde'] ?? date('Y-m-01');
        $fechaHasta = $filters['fecha_hasta'] ?? date('Y-m-d');
        
        // Validar formato de fechas
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
            sendError('Formato de fecha inválido. Use YYYY-MM-DD', 400);
            return;
        }
        
        // Validar que fecha desde no sea mayor que fecha hasta
        if (strtotime($fechaDesde) > strtotime($fechaHasta)) {
            sendError('La fecha desde no puede ser mayor que la fecha hasta', 400);
            return;
        }
        
        // Validar rango de fechas (máximo 2 años)
        $diasDiferencia = (strtotime($fechaHasta) - strtotime($fechaDesde)) / (60 * 60 * 24);
        if ($diasDiferencia > 730) {
            sendError('El rango de fechas no puede exceder 2 años', 400);
            return;
        }
        
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
        
        // Estadísticas generales
        $estadisticas = $this->getEstadisticasGenerales($where, $params);
        
        // Gráficos de préstamos por mes
        $prestamosPorMes = $this->getPrestamosPorMes($fechaDesde, $fechaHasta, $where, $params);
        
        // Gráficos de cobros por mes
        $cobrosPorMes = $this->getCobrosPorMes($fechaDesde, $fechaHasta);
        
        // Distribución por estado
        $distribucionEstado = $this->getDistribucionPorEstado($where, $params);
        
        // Top clientes
        $topClientes = $this->getTopClientes($where, $params);
        
        // Tendencias
        $tendencias = $this->getTendencias($fechaDesde, $fechaHasta);
        
        sendResponse([
            'estadisticas' => $estadisticas,
            'graficos' => [
                'prestamos_por_mes' => $prestamosPorMes,
                'cobros_por_mes' => $cobrosPorMes,
                'distribucion_estado' => $distribucionEstado
            ],
            'top_clientes' => $topClientes,
            'tendencias' => $tendencias,
            'periodo' => [
                'desde' => $fechaDesde,
                'hasta' => $fechaHasta
            ]
        ]);
    }
    
    /**
     * Estadísticas generales
     */
    private function getEstadisticasGenerales($where, $params) {
        // Total de préstamos
        $totalStmt = $this->db->query(
            "SELECT COUNT(*) as total FROM prestamos WHERE $where",
            $params
        );
        $total = $totalStmt->fetch()['total'];
        
        // Préstamos activos
        $activosStmt = $this->db->query(
            "SELECT COUNT(*) as cantidad, SUM(monto_aprobado) as monto_total
             FROM prestamos 
             WHERE $where AND estado = 'vigente'",
            $params
        );
        $activos = $activosStmt->fetch();
        
        // Préstamos vencidos
        $vencidosStmt = $this->db->query(
            "SELECT COUNT(*) as cantidad, SUM(monto_aprobado) as monto_total
             FROM prestamos 
             WHERE $where AND estado = 'vencido'",
            $params
        );
        $vencidos = $vencidosStmt->fetch();
        
        // Tasa de recuperación
        $recuperacionStmt = $this->db->query(
            "SELECT 
                (SELECT SUM(monto) FROM pagos) as total_cobrado,
                (SELECT SUM(monto_aprobado) FROM prestamos WHERE $where) as total_prestado",
            $params
        );
        $recuperacion = $recuperacionStmt->fetch();
        $tasaRecuperacion = $recuperacion['total_prestado'] > 0 
            ? ($recuperacion['total_cobrado'] / $recuperacion['total_prestado']) * 100 
            : 0;
        
        return [
            'total_prestamos' => $total,
            'prestamos_activos' => [
                'cantidad' => $activos['cantidad'],
                'monto_total' => $activos['monto_total'] ?? 0
            ],
            'prestamos_vencidos' => [
                'cantidad' => $vencidos['cantidad'],
                'monto_total' => $vencidos['monto_total'] ?? 0
            ],
            'tasa_recuperacion' => round($tasaRecuperacion, 2)
        ];
    }
    
    /**
     * Préstamos por mes
     */
    private function getPrestamosPorMes($fechaDesde, $fechaHasta, $where, $params) {
        $paramsMes = array_merge($params, [$fechaDesde, $fechaHasta]);
        
        $stmt = $this->db->query(
            "SELECT 
                DATE_FORMAT(fecha_creacion, '%Y-%m') as mes,
                COUNT(*) as cantidad,
                SUM(monto_aprobado) as monto_total
             FROM prestamos
             WHERE $where AND DATE(fecha_creacion) BETWEEN ? AND ?
             GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m')
             ORDER BY mes ASC",
            $paramsMes
        );
        
        return $stmt->fetchAll();
    }
    
    /**
     * Cobros por mes
     */
    private function getCobrosPorMes($fechaDesde, $fechaHasta) {
        $stmt = $this->db->query(
            "SELECT 
                DATE_FORMAT(fecha_pago, '%Y-%m') as mes,
                COUNT(*) as cantidad,
                SUM(monto) as monto_total,
                SUM(capital) as capital_total,
                SUM(interes) as interes_total,
                SUM(mora) as mora_total
             FROM pagos
             WHERE DATE(fecha_pago) BETWEEN ? AND ?
             GROUP BY DATE_FORMAT(fecha_pago, '%Y-%m')
             ORDER BY mes ASC",
            [$fechaDesde, $fechaHasta]
        );
        
        return $stmt->fetchAll();
    }
    
    /**
     * Distribución por estado
     */
    private function getDistribucionPorEstado($where, $params) {
        $stmt = $this->db->query(
            "SELECT 
                estado,
                COUNT(*) as cantidad,
                SUM(monto_aprobado) as monto_total
             FROM prestamos
             WHERE $where
             GROUP BY estado
             ORDER BY cantidad DESC",
            $params
        );
        
        return $stmt->fetchAll();
    }
    
    /**
     * Top clientes
     */
    private function getTopClientes($where, $params) {
        $stmt = $this->db->query(
            "SELECT 
                c.id,
                c.cedula,
                c.nombre,
                c.apellido,
                COUNT(p.id) as total_prestamos,
                SUM(p.monto_aprobado) as monto_total,
                SUM(COALESCE(pagos.monto_pagado, 0)) as monto_pagado
             FROM clientes c
             INNER JOIN prestamos p ON c.id = p.cliente_id
             LEFT JOIN (
                 SELECT prestamo_id, SUM(monto) as monto_pagado
                 FROM pagos
                 GROUP BY prestamo_id
             ) pagos ON p.id = pagos.prestamo_id
             WHERE EXISTS (
                 SELECT 1 FROM prestamos p2 
                 WHERE p2.cliente_id = c.id AND $where
             )
             GROUP BY c.id, c.cedula, c.nombre, c.apellido
             ORDER BY monto_total DESC
             LIMIT 10",
            $params
        );
        
        return $stmt->fetchAll();
    }
    
    /**
     * Tendencias
     */
    private function getTendencias($fechaDesde, $fechaHasta) {
        // Comparar mes actual vs mes anterior
        $mesActual = date('Y-m');
        $mesAnterior = date('Y-m', strtotime('-1 month'));
        
        // Préstamos mes actual
        $actualStmt = $this->db->query(
            "SELECT COUNT(*) as cantidad, SUM(monto_aprobado) as monto
             FROM prestamos
             WHERE DATE_FORMAT(fecha_creacion, '%Y-%m') = ?",
            [$mesActual]
        );
        $actual = $actualStmt->fetch();
        
        // Préstamos mes anterior
        $anteriorStmt = $this->db->query(
            "SELECT COUNT(*) as cantidad, SUM(monto_aprobado) as monto
             FROM prestamos
             WHERE DATE_FORMAT(fecha_creacion, '%Y-%m') = ?",
            [$mesAnterior]
        );
        $anterior = $anteriorStmt->fetch();
        
        // Calcular variación
        $variacionCantidad = $anterior['cantidad'] > 0 
            ? (($actual['cantidad'] - $anterior['cantidad']) / $anterior['cantidad']) * 100 
            : 0;
        $variacionMonto = $anterior['monto'] > 0 
            ? (($actual['monto'] - $anterior['monto']) / $anterior['monto']) * 100 
            : 0;
        
        return [
            'prestamos' => [
                'mes_actual' => $actual,
                'mes_anterior' => $anterior,
                'variacion_cantidad' => round($variacionCantidad, 2),
                'variacion_monto' => round($variacionMonto, 2)
            ]
        ];
    }
    
    /**
     * Obtener datos para gráfico de cartera
     */
    public function getDatosCartera($user, $filters = []) {
        $where = "1=1";
        $params = [];
        
        if ($user['rol'] === 'supervisor') {
            $where .= " AND supervisor_aprobador_id = ?";
            $params[] = $user['id'];
        }
        
        $stmt = $this->db->query(
            "SELECT 
                p.estado,
                COUNT(*) as cantidad,
                SUM(p.monto_aprobado) as monto_total,
                SUM(COALESCE(pagos.monto_pagado, 0)) as monto_pagado,
                SUM(p.monto_aprobado) - SUM(COALESCE(pagos.monto_pagado, 0)) as saldo_pendiente
             FROM prestamos p
             LEFT JOIN (
                 SELECT prestamo_id, SUM(monto) as monto_pagado
                 FROM pagos
                 GROUP BY prestamo_id
             ) pagos ON p.id = pagos.prestamo_id
             WHERE $where
             GROUP BY p.estado",
            $params
        );
        
        sendResponse($stmt->fetchAll());
    }
}

