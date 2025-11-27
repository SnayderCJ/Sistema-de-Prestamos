<?php
/**
 * Generador de PDF usando TCPDF
 */

// Verificar si TCPDF está disponible
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

// Si TCPDF no está disponible, usar clase básica
if (!class_exists('TCPDF')) {
    class TCPDF {
        // Implementación básica si TCPDF no está instalado
        // En producción, instalar: composer require tecnickcom/tcpdf
    }
}

class PDFGenerator {
    private $pdf;
    
    public function __construct($title = 'Reporte', $orientation = 'L') {
        // Verificar que TCPDF esté disponible
        if (!class_exists('TCPDF')) {
            throw new Exception('TCPDF no está disponible. Instale con: composer require tecnickcom/tcpdf');
        }
        
        $this->pdf = new TCPDF($orientation, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Configuración del documento
        $this->pdf->SetCreator('Sistema de Préstamos');
        $this->pdf->SetAuthor('ERP Multicajas');
        $this->pdf->SetTitle($title);
        $this->pdf->SetSubject($title);
        
        // Remover header y footer por defecto
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        
        // Márgenes
        $this->pdf->SetMargins(15, 15, 15);
        $this->pdf->SetAutoPageBreak(TRUE, 15);
        
        // Fuente
        $this->pdf->SetFont('helvetica', '', 10);
    }
    
    /**
     * Generar PDF de reporte de préstamos
     */
    public function generarReportePrestamos($data) {
        $this->pdf->AddPage();
        
        // Encabezado
        $this->agregarEncabezado('REPORTE DE PRÉSTAMOS');
        
        // Información del reporte
        $this->agregarInfoReporte($data['filtros'] ?? [], $data['fecha_generacion'] ?? date('Y-m-d H:i:s'));
        
        // Resumen
        if (isset($data['resumen'])) {
            $this->agregarResumen($data['resumen']);
        }
        
        // Tabla de préstamos
        if (isset($data['prestamos']) && count($data['prestamos']) > 0) {
            $this->agregarTablaPrestamos($data['prestamos']);
        }
        
        return $this->pdf->Output('', 'S');
    }
    
    /**
     * Generar PDF de reporte de cobros
     */
    public function generarReporteCobros($data) {
        $this->pdf->AddPage();
        
        // Encabezado
        $this->agregarEncabezado('REPORTE DE COBROS');
        
        // Información del reporte
        $this->agregarInfoReporte($data['filtros'] ?? [], $data['fecha_generacion'] ?? date('Y-m-d H:i:s'));
        
        // Resumen
        if (isset($data['resumen'])) {
            $this->agregarResumenCobros($data['resumen']);
        }
        
        // Tabla de pagos
        if (isset($data['pagos']) && count($data['pagos']) > 0) {
            $this->agregarTablaPagos($data['pagos']);
        }
        
        return $this->pdf->Output('', 'S');
    }
    
    /**
     * Generar PDF de reporte de mora
     */
    public function generarReporteMora($data) {
        $this->pdf->AddPage();
        
        // Encabezado
        $this->agregarEncabezado('REPORTE DE MORA');
        
        // Información del reporte
        $this->agregarInfoReporte($data['filtros'] ?? [], $data['fecha_generacion'] ?? date('Y-m-d H:i:s'));
        
        // Resumen
        if (isset($data['resumen'])) {
            $this->agregarResumenMora($data['resumen']);
        }
        
        // Tabla de cuotas vencidas
        if (isset($data['cuotas_vencidas']) && count($data['cuotas_vencidas']) > 0) {
            $this->agregarTablaCuotasVencidas($data['cuotas_vencidas']);
        }
        
        return $this->pdf->Output('', 'S');
    }
    
    private function agregarEncabezado($titulo) {
        $this->pdf->SetFont('helvetica', 'B', 16);
        $this->pdf->Cell(0, 10, $titulo, 0, 1, 'C');
        $this->pdf->Ln(5);
    }
    
    private function agregarInfoReporte($filtros, $fechaGeneracion) {
        $this->pdf->SetFont('helvetica', '', 9);
        
        $info = "Fecha de generación: " . $fechaGeneracion . "\n";
        
        if (!empty($filtros)) {
            $info .= "Filtros aplicados:\n";
            foreach ($filtros as $key => $value) {
                $info .= "  - " . ucfirst(str_replace('_', ' ', $key)) . ": " . $value . "\n";
            }
        }
        
        $this->pdf->MultiCell(0, 5, $info, 0, 'L');
        $this->pdf->Ln(5);
    }
    
    private function agregarResumen($resumen) {
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(0, 8, 'RESUMEN', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 9);
        
        $y = $this->pdf->GetY();
        $x = $this->pdf->GetX();
        
        $items = [
            'Total Préstamos' => $resumen['total_prestamos'] ?? 0,
            'Total Monto' => 'RD$ ' . number_format($resumen['total_monto'] ?? 0, 2),
            'Total Pagado' => 'RD$ ' . number_format($resumen['total_pagado'] ?? 0, 2),
            'Total Pendiente' => 'RD$ ' . number_format($resumen['total_pendiente'] ?? 0, 2),
            'Total Mora' => 'RD$ ' . number_format($resumen['total_mora'] ?? 0, 2)
        ];
        
        foreach ($items as $label => $value) {
            $this->pdf->SetX($x);
            $this->pdf->Cell(80, 6, $label . ':', 0, 0, 'L');
            $this->pdf->Cell(0, 6, $value, 0, 1, 'L');
        }
        
        $this->pdf->Ln(5);
    }
    
    private function agregarResumenCobros($resumen) {
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(0, 8, 'RESUMEN', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 9);
        
        $items = [
            'Total Pagos' => $resumen['total_pagos'] ?? 0,
            'Total Cobros' => 'RD$ ' . number_format($resumen['total_cobros'] ?? 0, 2),
            'Total Capital' => 'RD$ ' . number_format($resumen['total_capital'] ?? 0, 2),
            'Total Interés' => 'RD$ ' . number_format($resumen['total_interes'] ?? 0, 2),
            'Total Mora' => 'RD$ ' . number_format($resumen['total_mora'] ?? 0, 2)
        ];
        
        foreach ($items as $label => $value) {
            $this->pdf->Cell(80, 6, $label . ':', 0, 0, 'L');
            $this->pdf->Cell(0, 6, $value, 0, 1, 'L');
        }
        
        $this->pdf->Ln(5);
    }
    
    private function agregarResumenMora($resumen) {
        $this->pdf->SetFont('helvetica', 'B', 11);
        $this->pdf->Cell(0, 8, 'RESUMEN', 0, 1, 'L');
        $this->pdf->SetFont('helvetica', '', 9);
        
        $items = [
            'Total Cuotas Vencidas' => $resumen['total_cuotas_vencidas'] ?? 0,
            'Total Mora' => 'RD$ ' . number_format($resumen['total_mora'] ?? 0, 2),
            'Promedio Días Vencido' => ($resumen['promedio_dias_vencido'] ?? 0) . ' días'
        ];
        
        foreach ($items as $label => $value) {
            $this->pdf->Cell(80, 6, $label . ':', 0, 0, 'L');
            $this->pdf->Cell(0, 6, $value, 0, 1, 'L');
        }
        
        $this->pdf->Ln(5);
    }
    
    private function agregarTablaPrestamos($prestamos) {
        $this->pdf->SetFont('helvetica', 'B', 10);
        
        // Encabezados de tabla
        $headers = ['Número', 'Cliente', 'Monto', 'Cuota', 'Estado', 'Saldo'];
        $widths = [30, 60, 35, 30, 30, 35];
        
        // Dibujar encabezados
        $this->pdf->SetFillColor(200, 200, 200);
        foreach ($headers as $i => $header) {
            $this->pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
        }
        $this->pdf->Ln();
        
        // Datos
        $this->pdf->SetFont('helvetica', '', 8);
        $this->pdf->SetFillColor(245, 245, 245);
        $fill = false;
        
        foreach ($prestamos as $prestamo) {
            if ($this->pdf->GetY() > 270) {
                $this->pdf->AddPage();
                // Redibujar encabezados
                $this->pdf->SetFont('helvetica', 'B', 10);
                foreach ($headers as $i => $header) {
                    $this->pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
                }
                $this->pdf->Ln();
                $this->pdf->SetFont('helvetica', '', 8);
            }
            
            $cliente = ($prestamo['cliente_nombre'] ?? '') . ' ' . ($prestamo['cliente_apellido'] ?? '');
            $monto = 'RD$ ' . number_format($prestamo['monto_aprobado'] ?? 0, 2);
            $cuota = 'RD$ ' . number_format($prestamo['cuota_mensual'] ?? 0, 2);
            $saldo = 'RD$ ' . number_format(($prestamo['monto_aprobado'] ?? 0) - ($prestamo['monto_pagado'] ?? 0), 2);
            
            $this->pdf->Cell($widths[0], 6, $prestamo['numero_prestamo'] ?? '', 1, 0, 'L', $fill);
            $this->pdf->Cell($widths[1], 6, substr($cliente, 0, 30), 1, 0, 'L', $fill);
            $this->pdf->Cell($widths[2], 6, $monto, 1, 0, 'R', $fill);
            $this->pdf->Cell($widths[3], 6, $cuota, 1, 0, 'R', $fill);
            $this->pdf->Cell($widths[4], 6, $prestamo['estado'] ?? '', 1, 0, 'C', $fill);
            $this->pdf->Cell($widths[5], 6, $saldo, 1, 0, 'R', $fill);
            $this->pdf->Ln();
            
            $fill = !$fill;
        }
    }
    
    private function agregarTablaPagos($pagos) {
        $this->pdf->SetFont('helvetica', 'B', 10);
        
        $headers = ['Recibo', 'Préstamo', 'Cliente', 'Monto', 'Fecha'];
        $widths = [30, 30, 70, 35, 35];
        
        $this->pdf->SetFillColor(200, 200, 200);
        foreach ($headers as $i => $header) {
            $this->pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
        }
        $this->pdf->Ln();
        
        $this->pdf->SetFont('helvetica', '', 8);
        $fill = false;
        
        foreach ($pagos as $pago) {
            if ($this->pdf->GetY() > 270) {
                $this->pdf->AddPage();
                $this->pdf->SetFont('helvetica', 'B', 10);
                foreach ($headers as $i => $header) {
                    $this->pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
                }
                $this->pdf->Ln();
                $this->pdf->SetFont('helvetica', '', 8);
            }
            
            $cliente = ($pago['cliente_nombre'] ?? '') . ' ' . ($pago['cliente_apellido'] ?? '');
            $monto = 'RD$ ' . number_format($pago['monto'] ?? 0, 2);
            $fecha = date('d/m/Y', strtotime($pago['fecha_pago'] ?? 'now'));
            
            $this->pdf->Cell($widths[0], 6, $pago['numero_recibo'] ?? '', 1, 0, 'L', $fill);
            $this->pdf->Cell($widths[1], 6, $pago['numero_prestamo'] ?? '', 1, 0, 'L', $fill);
            $this->pdf->Cell($widths[2], 6, substr($cliente, 0, 35), 1, 0, 'L', $fill);
            $this->pdf->Cell($widths[3], 6, $monto, 1, 0, 'R', $fill);
            $this->pdf->Cell($widths[4], 6, $fecha, 1, 0, 'C', $fill);
            $this->pdf->Ln();
            
            $fill = !$fill;
        }
    }
    
    private function agregarTablaCuotasVencidas($cuotas) {
        $this->pdf->SetFont('helvetica', 'B', 10);
        
        $headers = ['Préstamo', 'Cliente', 'Días Vencido', 'Mora'];
        $widths = [35, 80, 35, 40];
        
        $this->pdf->SetFillColor(200, 200, 200);
        foreach ($headers as $i => $header) {
            $this->pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
        }
        $this->pdf->Ln();
        
        $this->pdf->SetFont('helvetica', '', 8);
        $fill = false;
        
        foreach ($cuotas as $cuota) {
            if ($this->pdf->GetY() > 270) {
                $this->pdf->AddPage();
                $this->pdf->SetFont('helvetica', 'B', 10);
                foreach ($headers as $i => $header) {
                    $this->pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
                }
                $this->pdf->Ln();
                $this->pdf->SetFont('helvetica', '', 8);
            }
            
            $cliente = ($cuota['cliente_nombre'] ?? '') . ' ' . ($cuota['cliente_apellido'] ?? '');
            $mora = 'RD$ ' . number_format($cuota['mora'] ?? 0, 2);
            
            $this->pdf->Cell($widths[0], 6, $cuota['numero_prestamo'] ?? '', 1, 0, 'L', $fill);
            $this->pdf->Cell($widths[1], 6, substr($cliente, 0, 40), 1, 0, 'L', $fill);
            $this->pdf->Cell($widths[2], 6, $cuota['dias_vencido'] ?? 0, 1, 0, 'C', $fill);
            $this->pdf->Cell($widths[3], 6, $mora, 1, 0, 'R', $fill);
            $this->pdf->Ln();
            
            $fill = !$fill;
        }
    }
}

