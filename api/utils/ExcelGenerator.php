<?php
/**
 * Generador de Excel usando PhpSpreadsheet
 */

// Verificar si PhpSpreadsheet está disponible
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

// Si PhpSpreadsheet no está disponible, usar clase básica
if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    // En producción, instalar: composer require phpoffice/phpspreadsheet
    // Por ahora, generar CSV como fallback
}

class ExcelGenerator {
    private $spreadsheet;
    private $sheet;
    private $row;
    
    public function __construct($title = 'Reporte') {
        // Verificar que PhpSpreadsheet esté disponible
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            throw new Exception('PhpSpreadsheet no está disponible. Instale con: composer require phpoffice/phpspreadsheet');
        }
        
        $this->spreadsheet = new Spreadsheet();
        $this->sheet = $this->spreadsheet->getActiveSheet();
        $this->sheet->setTitle($title);
        $this->row = 1;
    }
    
    /**
     * Generar Excel de reporte de préstamos
     */
    public function generarReportePrestamos($data) {
        // Título
        $this->sheet->setCellValue('A1', 'REPORTE DE PRÉSTAMOS');
        $this->sheet->mergeCells('A1:F1');
        $this->sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $this->sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $this->row = 3;
        
        // Información del reporte
        $this->sheet->setCellValue('A' . $this->row, 'Fecha de generación: ' . ($data['fecha_generacion'] ?? date('Y-m-d H:i:s')));
        $this->row++;
        
        if (!empty($data['filtros'])) {
            $this->sheet->setCellValue('A' . $this->row, 'Filtros aplicados:');
            $this->row++;
            foreach ($data['filtros'] as $key => $value) {
                $this->sheet->setCellValue('B' . $this->row, ucfirst(str_replace('_', ' ', $key)) . ': ' . $value);
                $this->row++;
            }
        }
        
        $this->row++;
        
        // Resumen
        if (isset($data['resumen'])) {
            $this->agregarResumen($data['resumen']);
        }
        
        $this->row += 2;
        
        // Encabezados de tabla
        $headers = ['Número', 'Cliente', 'Cédula', 'Monto Aprobado', 'Cuota Mensual', 'Estado', 'Monto Pagado', 'Saldo'];
        $this->agregarEncabezados($headers);
        
        // Datos
        if (isset($data['prestamos']) && count($data['prestamos']) > 0) {
            foreach ($data['prestamos'] as $prestamo) {
                $this->row++;
                $cliente = ($prestamo['cliente_nombre'] ?? '') . ' ' . ($prestamo['cliente_apellido'] ?? '');
                $saldo = ($prestamo['monto_aprobado'] ?? 0) - ($prestamo['monto_pagado'] ?? 0);
                
                $this->sheet->setCellValue('A' . $this->row, $prestamo['numero_prestamo'] ?? '');
                $this->sheet->setCellValue('B' . $this->row, $cliente);
                $this->sheet->setCellValue('C' . $this->row, $prestamo['cliente_cedula'] ?? '');
                $this->sheet->setCellValue('D' . $this->row, $prestamo['monto_aprobado'] ?? 0);
                $this->sheet->setCellValue('E' . $this->row, $prestamo['cuota_mensual'] ?? 0);
                $this->sheet->setCellValue('F' . $this->row, $prestamo['estado'] ?? '');
                $this->sheet->setCellValue('G' . $this->row, $prestamo['monto_pagado'] ?? 0);
                $this->sheet->setCellValue('H' . $this->row, $saldo);
                
                // Formato de números
                $this->sheet->getStyle('D' . $this->row)->getNumberFormat()->setFormatCode('#,##0.00');
                $this->sheet->getStyle('E' . $this->row)->getNumberFormat()->setFormatCode('#,##0.00');
                $this->sheet->getStyle('G' . $this->row)->getNumberFormat()->setFormatCode('#,##0.00');
                $this->sheet->getStyle('H' . $this->row)->getNumberFormat()->setFormatCode('#,##0.00');
            }
        }
        
        // Ajustar ancho de columnas
        $this->ajustarAnchoColumnas(['A' => 20, 'B' => 30, 'C' => 15, 'D' => 15, 'E' => 15, 'F' => 15, 'G' => 15, 'H' => 15]);
        
        return $this->generarArchivo();
    }
    
    /**
     * Generar Excel de reporte de cobros
     */
    public function generarReporteCobros($data) {
        $this->sheet->setCellValue('A1', 'REPORTE DE COBROS');
        $this->sheet->mergeCells('A1:E1');
        $this->sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $this->sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $this->row = 3;
        
        $this->sheet->setCellValue('A' . $this->row, 'Fecha de generación: ' . ($data['fecha_generacion'] ?? date('Y-m-d H:i:s')));
        $this->row += 2;
        
        if (isset($data['resumen'])) {
            $this->agregarResumenCobros($data['resumen']);
        }
        
        $this->row += 2;
        
        $headers = ['Recibo', 'Préstamo', 'Cliente', 'Monto', 'Capital', 'Interés', 'Mora', 'Fecha'];
        $this->agregarEncabezados($headers);
        
        if (isset($data['pagos']) && count($data['pagos']) > 0) {
            foreach ($data['pagos'] as $pago) {
                $this->row++;
                $cliente = ($pago['cliente_nombre'] ?? '') . ' ' . ($pago['cliente_apellido'] ?? '');
                
                $this->sheet->setCellValue('A' . $this->row, $pago['numero_recibo'] ?? '');
                $this->sheet->setCellValue('B' . $this->row, $pago['numero_prestamo'] ?? '');
                $this->sheet->setCellValue('C' . $this->row, $cliente);
                $this->sheet->setCellValue('D' . $this->row, $pago['monto'] ?? 0);
                $this->sheet->setCellValue('E' . $this->row, $pago['capital'] ?? 0);
                $this->sheet->setCellValue('F' . $this->row, $pago['interes'] ?? 0);
                $this->sheet->setCellValue('G' . $this->row, $pago['mora'] ?? 0);
                $this->sheet->setCellValue('H' . $this->row, $pago['fecha_pago'] ?? '');
                
                // Formato
                foreach (['D', 'E', 'F', 'G'] as $col) {
                    $this->sheet->getStyle($col . $this->row)->getNumberFormat()->setFormatCode('#,##0.00');
                }
            }
        }
        
        $this->ajustarAnchoColumnas(['A' => 20, 'B' => 20, 'C' => 30, 'D' => 15, 'E' => 15, 'F' => 15, 'G' => 15, 'H' => 15]);
        
        return $this->generarArchivo();
    }
    
    /**
     * Generar Excel de reporte de mora
     */
    public function generarReporteMora($data) {
        $this->sheet->setCellValue('A1', 'REPORTE DE MORA');
        $this->sheet->mergeCells('A1:D1');
        $this->sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $this->sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $this->row = 3;
        
        $this->sheet->setCellValue('A' . $this->row, 'Fecha de generación: ' . ($data['fecha_generacion'] ?? date('Y-m-d H:i:s')));
        $this->row += 2;
        
        if (isset($data['resumen'])) {
            $this->agregarResumenMora($data['resumen']);
        }
        
        $this->row += 2;
        
        $headers = ['Préstamo', 'Cliente', 'Días Vencido', 'Mora'];
        $this->agregarEncabezados($headers);
        
        if (isset($data['cuotas_vencidas']) && count($data['cuotas_vencidas']) > 0) {
            foreach ($data['cuotas_vencidas'] as $cuota) {
                $this->row++;
                $cliente = ($cuota['cliente_nombre'] ?? '') . ' ' . ($cuota['cliente_apellido'] ?? '');
                
                $this->sheet->setCellValue('A' . $this->row, $cuota['numero_prestamo'] ?? '');
                $this->sheet->setCellValue('B' . $this->row, $cliente);
                $this->sheet->setCellValue('C' . $this->row, $cuota['dias_vencido'] ?? 0);
                $this->sheet->setCellValue('D' . $this->row, $cuota['mora'] ?? 0);
                
                $this->sheet->getStyle('D' . $this->row)->getNumberFormat()->setFormatCode('#,##0.00');
            }
        }
        
        $this->ajustarAnchoColumnas(['A' => 20, 'B' => 40, 'C' => 15, 'D' => 15]);
        
        return $this->generarArchivo();
    }
    
    private function agregarEncabezados($headers) {
        $col = 'A';
        foreach ($headers as $header) {
            $this->sheet->setCellValue($col . $this->row, $header);
            $this->sheet->getStyle($col . $this->row)->getFont()->setBold(true);
            $this->sheet->getStyle($col . $this->row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFCCCCCC');
            $this->sheet->getStyle($col . $this->row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col++;
        }
        $this->row++;
    }
    
    private function agregarResumen($resumen) {
        $this->sheet->setCellValue('A' . $this->row, 'RESUMEN');
        $this->sheet->getStyle('A' . $this->row)->getFont()->setBold(true);
        $this->row++;
        
        $items = [
            'Total Préstamos' => $resumen['total_prestamos'] ?? 0,
            'Total Monto' => $resumen['total_monto'] ?? 0,
            'Total Pagado' => $resumen['total_pagado'] ?? 0,
            'Total Pendiente' => $resumen['total_pendiente'] ?? 0,
            'Total Mora' => $resumen['total_mora'] ?? 0
        ];
        
        foreach ($items as $label => $value) {
            $this->sheet->setCellValue('A' . $this->row, $label . ':');
            $this->sheet->setCellValue('B' . $this->row, $value);
            if (is_numeric($value) && $value > 0) {
                $this->sheet->getStyle('B' . $this->row)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $this->row++;
        }
    }
    
    private function agregarResumenCobros($resumen) {
        $this->sheet->setCellValue('A' . $this->row, 'RESUMEN');
        $this->sheet->getStyle('A' . $this->row)->getFont()->setBold(true);
        $this->row++;
        
        $items = [
            'Total Pagos' => $resumen['total_pagos'] ?? 0,
            'Total Cobros' => $resumen['total_cobros'] ?? 0,
            'Total Capital' => $resumen['total_capital'] ?? 0,
            'Total Interés' => $resumen['total_interes'] ?? 0,
            'Total Mora' => $resumen['total_mora'] ?? 0
        ];
        
        foreach ($items as $label => $value) {
            $this->sheet->setCellValue('A' . $this->row, $label . ':');
            $this->sheet->setCellValue('B' . $this->row, $value);
            if (is_numeric($value) && $value > 0) {
                $this->sheet->getStyle('B' . $this->row)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $this->row++;
        }
    }
    
    private function agregarResumenMora($resumen) {
        $this->sheet->setCellValue('A' . $this->row, 'RESUMEN');
        $this->sheet->getStyle('A' . $this->row)->getFont()->setBold(true);
        $this->row++;
        
        $items = [
            'Total Cuotas Vencidas' => $resumen['total_cuotas_vencidas'] ?? 0,
            'Total Mora' => $resumen['total_mora'] ?? 0,
            'Promedio Días Vencido' => $resumen['promedio_dias_vencido'] ?? 0
        ];
        
        foreach ($items as $label => $value) {
            $this->sheet->setCellValue('A' . $this->row, $label . ':');
            $this->sheet->setCellValue('B' . $this->row, $value);
            if (is_numeric($value) && $value > 0) {
                $this->sheet->getStyle('B' . $this->row)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $this->row++;
        }
    }
    
    private function ajustarAnchoColumnas($widths) {
        foreach ($widths as $col => $width) {
            $this->sheet->getColumnDimension($col)->setWidth($width);
        }
    }
    
    private function generarArchivo() {
        $writer = new Xlsx($this->spreadsheet);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }
}

