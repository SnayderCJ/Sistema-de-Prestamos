<?php
/**
 * Servicio de Reportes
 */

class ReporteService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Generar reporte de préstamos
     */
    public function generarReportePrestamos($filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['fecha_desde'])) {
            $where[] = "DATE(p.fecha_creacion) >= ?";
            $params[] = $filters['fecha_desde'];
        }
        
        if (isset($filters['fecha_hasta'])) {
            $where[] = "DATE(p.fecha_creacion) <= ?";
            $params[] = $filters['fecha_hasta'];
        }
        
        if (isset($filters['estado'])) {
            $where[] = "p.estado = ?";
            $params[] = $filters['estado'];
        }
        
        if (isset($filters['sucursal_id'])) {
            $where[] = "p.sucursal_id = ?";
            $params[] = $filters['sucursal_id'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT 
                p.*,
                c.cedula as cliente_cedula,
                c.nombre as cliente_nombre,
                c.apellido as cliente_apellido,
                s.nombre as sucursal_nombre,
                t.nombre as tasa_nombre,
                (SELECT SUM(monto_pagado) FROM pagos WHERE prestamo_id = p.id) as monto_pagado,
                (SELECT SUM(mora) FROM cuotas_prestamos WHERE prestamo_id = p.id) as mora_total
             FROM prestamos p
             LEFT JOIN clientes c ON p.cliente_id = c.id
             LEFT JOIN sucursales s ON p.sucursal_id = s.id
             LEFT JOIN tasas_interes t ON p.tasa_interes_id = t.id
             WHERE $whereClause
             ORDER BY p.fecha_creacion DESC",
            $params
        );
        
        $prestamos = $stmt->fetchAll();
        
        // Calcular totales
        $totalPrestamos = count($prestamos);
        $totalMonto = array_sum(array_column($prestamos, 'monto_aprobado'));
        $totalPagado = array_sum(array_column($prestamos, 'monto_pagado'));
        $totalMora = array_sum(array_column($prestamos, 'mora_total'));
        
        return [
            'prestamos' => $prestamos,
            'resumen' => [
                'total_prestamos' => $totalPrestamos,
                'total_monto' => $totalMonto,
                'total_pagado' => $totalPagado,
                'total_pendiente' => $totalMonto - $totalPagado,
                'total_mora' => $totalMora
            ],
            'filtros' => $filters,
            'fecha_generacion' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Generar reporte de cobros
     */
    public function generarReporteCobros($filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['fecha_desde'])) {
            $where[] = "DATE(p.fecha_pago) >= ?";
            $params[] = $filters['fecha_desde'];
        }
        
        if (isset($filters['fecha_hasta'])) {
            $where[] = "DATE(p.fecha_pago) <= ?";
            $params[] = $filters['fecha_hasta'];
        }
        
        if (isset($filters['sucursal_id'])) {
            $where[] = "p.sucursal_id = ?";
            $params[] = $filters['sucursal_id'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT 
                p.*,
                pr.numero_prestamo,
                c.cedula as cliente_cedula,
                c.nombre as cliente_nombre,
                c.apellido as cliente_apellido,
                u.nombre as usuario_nombre,
                u.apellido as usuario_apellido,
                s.nombre as sucursal_nombre
             FROM pagos p
             LEFT JOIN prestamos pr ON p.prestamo_id = pr.id
             LEFT JOIN clientes c ON pr.cliente_id = c.id
             LEFT JOIN usuarios u ON p.usuario_id = u.id
             LEFT JOIN sucursales s ON p.sucursal_id = s.id
             WHERE $whereClause
             ORDER BY p.fecha_pago DESC",
            $params
        );
        
        $pagos = $stmt->fetchAll();
        
        $totalCobros = array_sum(array_column($pagos, 'monto'));
        $totalCapital = array_sum(array_column($pagos, 'capital'));
        $totalInteres = array_sum(array_column($pagos, 'interes'));
        $totalMora = array_sum(array_column($pagos, 'mora'));
        
        return [
            'pagos' => $pagos,
            'resumen' => [
                'total_pagos' => count($pagos),
                'total_cobros' => $totalCobros,
                'total_capital' => $totalCapital,
                'total_interes' => $totalInteres,
                'total_mora' => $totalMora
            ],
            'filtros' => $filters,
            'fecha_generacion' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Generar reporte de mora
     */
    public function generarReporteMora($filters = []) {
        $where = ["cp.estado = 'vencida'"];
        $params = [];
        
        if (isset($filters['fecha_desde'])) {
            $where[] = "cp.fecha_vencimiento >= ?";
            $params[] = $filters['fecha_desde'];
        }
        
        if (isset($filters['fecha_hasta'])) {
            $where[] = "cp.fecha_vencimiento <= ?";
            $params[] = $filters['fecha_hasta'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT 
                cp.*,
                p.numero_prestamo,
                c.cedula as cliente_cedula,
                c.nombre as cliente_nombre,
                c.apellido as cliente_apellido,
                c.telefono,
                DATEDIFF(CURDATE(), cp.fecha_vencimiento) as dias_vencido
             FROM cuotas_prestamos cp
             LEFT JOIN prestamos p ON cp.prestamo_id = p.id
             LEFT JOIN clientes c ON p.cliente_id = c.id
             WHERE $whereClause
             ORDER BY cp.fecha_vencimiento ASC",
            $params
        );
        
        $cuotas = $stmt->fetchAll();
        
        $totalMora = array_sum(array_column($cuotas, 'mora'));
        $totalCuotas = count($cuotas);
        
        return [
            'cuotas_vencidas' => $cuotas,
            'resumen' => [
                'total_cuotas_vencidas' => $totalCuotas,
                'total_mora' => $totalMora,
                'promedio_dias_vencido' => $totalCuotas > 0 ? round(array_sum(array_column($cuotas, 'dias_vencido')) / $totalCuotas, 2) : 0
            ],
            'filtros' => $filters,
            'fecha_generacion' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Generar reporte de clientes
     */
    public function generarReporteClientes($filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['estado_credito'])) {
            $where[] = "c.estado_credito = ?";
            $params[] = $filters['estado_credito'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT 
                c.*,
                (SELECT COUNT(*) FROM prestamos WHERE cliente_id = c.id) as total_prestamos,
                (SELECT COUNT(*) FROM prestamos WHERE cliente_id = c.id AND estado = 'vigente') as prestamos_activos,
                (SELECT SUM(monto_aprobado) FROM prestamos WHERE cliente_id = c.id AND estado = 'vigente') as deuda_total
             FROM clientes c
             WHERE $whereClause
             ORDER BY c.fecha_creacion DESC",
            $params
        );
        
        $clientes = $stmt->fetchAll();
        
        return [
            'clientes' => $clientes,
            'resumen' => [
                'total_clientes' => count($clientes),
                'clientes_activos' => count(array_filter($clientes, fn($c) => $c['estado_credito'] === 'activo')),
                'clientes_bloqueados' => count(array_filter($clientes, fn($c) => $c['estado_credito'] === 'bloqueado'))
            ],
            'filtros' => $filters,
            'fecha_generacion' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Generar reporte de dashboard
     */
    public function generarReporteDashboard($filters = []) {
        $fechaDesde = $filters['fecha_desde'] ?? date('Y-m-01');
        $fechaHasta = $filters['fecha_hasta'] ?? date('Y-m-d');
        
        // Préstamos del período
        $prestamosStmt = $this->db->query(
            "SELECT COUNT(*) as total, SUM(monto_aprobado) as monto_total
             FROM prestamos
             WHERE DATE(fecha_creacion) BETWEEN ? AND ?",
            [$fechaDesde, $fechaHasta]
        );
        $prestamos = $prestamosStmt->fetch();
        
        // Cobros del período
        $cobrosStmt = $this->db->query(
            "SELECT SUM(monto) as total
             FROM pagos
             WHERE DATE(fecha_pago) BETWEEN ? AND ?",
            [$fechaDesde, $fechaHasta]
        );
        $cobros = $cobrosStmt->fetch();
        
        // Mora del período
        $moraStmt = $this->db->query(
            "SELECT SUM(mora) as total
             FROM cuotas_prestamos
             WHERE fecha_vencimiento BETWEEN ? AND ? AND estado = 'vencida'",
            [$fechaDesde, $fechaHasta]
        );
        $mora = $moraStmt->fetch();
        
        return [
            'periodo' => [
                'desde' => $fechaDesde,
                'hasta' => $fechaHasta
            ],
            'prestamos' => $prestamos,
            'cobros' => $cobros,
            'mora' => $mora,
            'fecha_generacion' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Generar PDF usando TCPDF
     */
    public function generarPDF($tipo, $filters = []) {
        require_once __DIR__ . '/../utils/PDFGenerator.php';
        
        $reporte = null;
        
        try {
            switch ($tipo) {
                case 'prestamos':
                    $reporte = $this->generarReportePrestamos($filters);
                    $generator = new PDFGenerator('Reporte de Préstamos', 'L');
                    return $generator->generarReportePrestamos($reporte);
                case 'cobros':
                    $reporte = $this->generarReporteCobros($filters);
                    $generator = new PDFGenerator('Reporte de Cobros', 'L');
                    return $generator->generarReporteCobros($reporte);
                case 'mora':
                    $reporte = $this->generarReporteMora($filters);
                    $generator = new PDFGenerator('Reporte de Mora', 'L');
                    return $generator->generarReporteMora($reporte);
                default:
                    throw new Exception('Tipo de reporte no válido');
            }
        } catch (Exception $e) {
            // Fallback: retornar JSON si TCPDF no está disponible
            error_log("Error generando PDF: " . $e->getMessage());
            $reporte = $this->generarReportePrestamos($filters);
            return json_encode($reporte, JSON_PRETTY_PRINT);
        }
    }
    
    /**
     * Generar Excel usando PhpSpreadsheet
     */
    public function generarExcel($tipo, $filters = []) {
        require_once __DIR__ . '/../utils/ExcelGenerator.php';
        
        $reporte = null;
        
        try {
            switch ($tipo) {
                case 'prestamos':
                    $reporte = $this->generarReportePrestamos($filters);
                    if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                        $generator = new ExcelGenerator('Préstamos');
                        return $generator->generarReportePrestamos($reporte);
                    }
                    // Fallback a CSV
                    return $this->generarCSV($reporte, 'prestamos');
                case 'cobros':
                    $reporte = $this->generarReporteCobros($filters);
                    if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                        $generator = new ExcelGenerator('Cobros');
                        return $generator->generarReporteCobros($reporte);
                    }
                    return $this->generarCSV($reporte, 'cobros');
                case 'mora':
                    $reporte = $this->generarReporteMora($filters);
                    if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                        $generator = new ExcelGenerator('Mora');
                        return $generator->generarReporteMora($reporte);
                    }
                    return $this->generarCSV($reporte, 'mora');
                default:
                    throw new Exception('Tipo de reporte no válido');
            }
        } catch (Exception $e) {
            error_log("Error generando Excel: " . $e->getMessage());
            $reporte = $this->generarReportePrestamos($filters);
            return $this->generarCSV($reporte, $tipo);
        }
    }
    
    /**
     * Generar CSV como fallback
     */
    private function generarCSV($reporte, $tipo) {
        $csv = "Reporte: $tipo\n";
        $csv .= "Fecha: " . date('Y-m-d H:i:s') . "\n\n";
        
        if (isset($reporte['prestamos'])) {
            $csv .= "Número,Cliente,Monto,Cuota,Estado\n";
            foreach ($reporte['prestamos'] as $prestamo) {
                $cliente = ($prestamo['cliente_nombre'] ?? '') . ' ' . ($prestamo['cliente_apellido'] ?? '');
                $csv .= sprintf(
                    "%s,%s,%s,%s,%s\n",
                    $prestamo['numero_prestamo'] ?? '',
                    $cliente,
                    $prestamo['monto_aprobado'] ?? 0,
                    $prestamo['cuota_mensual'] ?? 0,
                    $prestamo['estado'] ?? ''
                );
            }
        } elseif (isset($reporte['pagos'])) {
            $csv .= "Recibo,Préstamo,Cliente,Monto,Fecha\n";
            foreach ($reporte['pagos'] as $pago) {
                $cliente = ($pago['cliente_nombre'] ?? '') . ' ' . ($pago['cliente_apellido'] ?? '');
                $csv .= sprintf(
                    "%s,%s,%s,%s,%s\n",
                    $pago['numero_recibo'] ?? '',
                    $pago['numero_prestamo'] ?? '',
                    $cliente,
                    $pago['monto'] ?? 0,
                    $pago['fecha_pago'] ?? ''
                );
            }
        }
        
        return $csv;
    }
}


