<?php
/**
 * Servicio de Exportación de Datos
 */

class ExportacionService {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Exportar préstamos a CSV
     */
    public function exportarPrestamosCSV($filters = []) {
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
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT 
                p.numero_prestamo,
                c.cedula,
                c.nombre as cliente_nombre,
                c.apellido as cliente_apellido,
                p.monto_aprobado,
                p.cuota_mensual,
                p.plazo_meses,
                p.estado,
                p.fecha_creacion,
                (SELECT SUM(monto) FROM pagos WHERE prestamo_id = p.id) as monto_pagado
             FROM prestamos p
             LEFT JOIN clientes c ON p.cliente_id = c.id
             WHERE $whereClause
             ORDER BY p.fecha_creacion DESC",
            $params
        );
        
        $prestamos = $stmt->fetchAll();
        
        // Generar CSV
        $csv = "Número,Cédula,Cliente,Monto Aprobado,Cuota Mensual,Plazo,Estado,Fecha Creación,Monto Pagado\n";
        
        foreach ($prestamos as $prestamo) {
            $cliente = ($prestamo['cliente_nombre'] ?? '') . ' ' . ($prestamo['cliente_apellido'] ?? '');
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $this->escaparCSV($prestamo['numero_prestamo'] ?? ''),
                $this->escaparCSV($prestamo['cedula'] ?? ''),
                $this->escaparCSV($cliente),
                number_format($prestamo['monto_aprobado'] ?? 0, 2),
                number_format($prestamo['cuota_mensual'] ?? 0, 2),
                $prestamo['plazo_meses'] ?? 0,
                $this->escaparCSV($prestamo['estado'] ?? ''),
                $prestamo['fecha_creacion'] ?? '',
                number_format($prestamo['monto_pagado'] ?? 0, 2)
            );
        }
        
        return $csv;
    }
    
    /**
     * Exportar pagos a CSV
     */
    public function exportarPagosCSV($filters = []) {
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
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT 
                p.numero_recibo,
                pr.numero_prestamo,
                c.cedula,
                c.nombre as cliente_nombre,
                c.apellido as cliente_apellido,
                p.monto,
                p.capital,
                p.interes,
                p.mora,
                p.metodo_pago,
                p.fecha_pago
             FROM pagos p
             LEFT JOIN prestamos pr ON p.prestamo_id = pr.id
             LEFT JOIN clientes c ON pr.cliente_id = c.id
             WHERE $whereClause
             ORDER BY p.fecha_pago DESC",
            $params
        );
        
        $pagos = $stmt->fetchAll();
        
        $csv = "Recibo,Préstamo,Cédula,Cliente,Monto,Capital,Interés,Mora,Método,Fecha\n";
        
        foreach ($pagos as $pago) {
            $cliente = ($pago['cliente_nombre'] ?? '') . ' ' . ($pago['cliente_apellido'] ?? '');
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $this->escaparCSV($pago['numero_recibo'] ?? ''),
                $this->escaparCSV($pago['numero_prestamo'] ?? ''),
                $this->escaparCSV($pago['cedula'] ?? ''),
                $this->escaparCSV($cliente),
                number_format($pago['monto'] ?? 0, 2),
                number_format($pago['capital'] ?? 0, 2),
                number_format($pago['interes'] ?? 0, 2),
                number_format($pago['mora'] ?? 0, 2),
                $this->escaparCSV($pago['metodo_pago'] ?? ''),
                $pago['fecha_pago'] ?? ''
            );
        }
        
        return $csv;
    }
    
    /**
     * Exportar clientes a CSV
     */
    public function exportarClientesCSV($filters = []) {
        $where = ["1=1"];
        $params = [];
        
        if (isset($filters['estado_credito'])) {
            $where[] = "c.estado_credito = ?";
            $params[] = $filters['estado_credito'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $stmt = $this->db->query(
            "SELECT 
                c.cedula,
                c.nombre,
                c.apellido,
                c.email,
                c.telefono,
                c.direccion,
                c.estado_credito,
                (SELECT COUNT(*) FROM prestamos WHERE cliente_id = c.id) as total_prestamos,
                (SELECT SUM(monto_aprobado) FROM prestamos WHERE cliente_id = c.id AND estado = 'vigente') as deuda_total
             FROM clientes c
             WHERE $whereClause
             ORDER BY c.fecha_creacion DESC",
            $params
        );
        
        $clientes = $stmt->fetchAll();
        
        $csv = "Cédula,Nombre,Apellido,Email,Teléfono,Dirección,Estado Crédito,Total Préstamos,Deuda Total\n";
        
        foreach ($clientes as $cliente) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $this->escaparCSV($cliente['cedula'] ?? ''),
                $this->escaparCSV($cliente['nombre'] ?? ''),
                $this->escaparCSV($cliente['apellido'] ?? ''),
                $this->escaparCSV($cliente['email'] ?? ''),
                $this->escaparCSV($cliente['telefono'] ?? ''),
                $this->escaparCSV($cliente['direccion'] ?? ''),
                $this->escaparCSV($cliente['estado_credito'] ?? ''),
                $cliente['total_prestamos'] ?? 0,
                number_format($cliente['deuda_total'] ?? 0, 2)
            );
        }
        
        return $csv;
    }
    
    private function escaparCSV($value) {
        if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }
}

