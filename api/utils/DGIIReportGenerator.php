<?php
/**
 * Generador de Reportes DGII en formato TXT
 * Formato según especificaciones de DGII República Dominicana
 */

class DGIIReportGenerator {
    private $db;
    private $rncEmisor;
    private $razonSocialEmisor;

    public function __construct() {
        $this->db = Database::getInstance();
        
        // Obtener datos del emisor desde configuración
        $stmt = $this->db->query(
            "SELECT valor FROM configuracion_sistema WHERE clave IN ('rnc_emisor', 'razon_social_emisor')"
        );
        $config = [];
        foreach ($stmt->fetchAll() as $row) {
            $config[$row['clave']] = $row['valor'];
        }
        
        $this->rncEmisor = $config['rnc_emisor'] ?? '';
        $this->razonSocialEmisor = $config['razon_social_emisor'] ?? '';
    }

    /**
     * Generar Reporte 606 - Ventas al Contado
     */
    public function generar606($periodo) {
        $mes = substr($periodo, 5, 2);
        $anio = substr($periodo, 0, 4);
        
        // Obtener comprobantes del período
        $stmt = $this->db->query(
            "SELECT cf.*, tc.codigo as tipo_comprobante_codigo,
                    c.cedula as cliente_cedula, c.nombre as cliente_nombre, c.apellido as cliente_apellido
             FROM comprobantes_fiscales cf
             INNER JOIN tipos_comprobantes tc ON cf.tipo_comprobante_id = tc.id
             LEFT JOIN clientes c ON cf.cliente_id = c.id
             WHERE DATE_FORMAT(cf.fecha_emision, '%Y-%m') = ?
             AND tc.codigo IN ('01', '02')
             AND cf.dgii_enviado = 0
             ORDER BY cf.fecha_emision, cf.numero_ncf",
            [$periodo]
        );
        
        $comprobantes = $stmt->fetchAll();
        
        $lines = [];
        
        // Encabezado del archivo
        $lines[] = "606"; // Tipo de reporte
        $lines[] = str_pad($this->rncEmisor, 11, '0', STR_PAD_LEFT); // RNC Emisor
        $lines[] = str_pad($this->razonSocialEmisor, 50); // Razón Social
        $lines[] = str_pad($anio, 4); // Año
        $lines[] = str_pad($mes, 2, '0', STR_PAD_LEFT); // Mes
        $lines[] = str_pad(count($comprobantes), 6, '0', STR_PAD_LEFT); // Cantidad de registros
        
        // Detalle de comprobantes
        foreach ($comprobantes as $comp) {
            $line = '';
            $line .= str_pad($comp['tipo_comprobante_codigo'], 2); // Tipo de comprobante
            $line .= str_pad($comp['numero_ncf'], 19); // NCF
            $line .= str_pad($comp['cliente_cedula'] ?? '', 11, '0', STR_PAD_LEFT); // RNC/Cédula Cliente
            $line .= str_pad(substr($comp['cliente_nombre'] . ' ' . $comp['cliente_apellido'], 0, 50), 50); // Nombre Cliente
            $line .= str_pad(date('Ymd', strtotime($comp['fecha_emision'])), 8); // Fecha (YYYYMMDD)
            $line .= str_pad(number_format($comp['monto_total'], 2, '', ''), 18, '0', STR_PAD_LEFT); // Monto Total (sin decimales)
            $line .= str_pad(number_format($comp['monto_impuestos'] ?? 0, 2, '', ''), 18, '0', STR_PAD_LEFT); // ITBIS
            $line .= str_pad('0', 18, '0', STR_PAD_LEFT); // ITBIS Retenido
            $line .= str_pad('0', 18, '0', STR_PAD_LEFT); // ISC
            $line .= str_pad('0', 18, '0', STR_PAD_LEFT); // Otros Impuestos
            $line .= str_pad('0', 18, '0', STR_PAD_LEFT); // Propina Legal
            
            $lines[] = $line;
        }
        
        return implode("\r\n", $lines);
    }

    /**
     * Generar Reporte 607 - Retenciones ISR
     */
    public function generar607($periodo) {
        $mes = substr($periodo, 5, 2);
        $anio = substr($periodo, 0, 4);
        
        // Obtener comprobantes con retención
        $stmt = $this->db->query(
            "SELECT cf.*, tc.codigo as tipo_comprobante_codigo,
                    c.cedula as cliente_cedula, c.nombre as cliente_nombre, c.apellido as cliente_apellido
             FROM comprobantes_fiscales cf
             INNER JOIN tipos_comprobantes tc ON cf.tipo_comprobante_id = tc.id
             LEFT JOIN clientes c ON cf.cliente_id = c.id
             WHERE DATE_FORMAT(cf.fecha_emision, '%Y-%m') = ?
             AND cf.tiene_retencion = 1
             AND cf.dgii_enviado = 0
             ORDER BY cf.fecha_emision, cf.numero_ncf",
            [$periodo]
        );
        
        $comprobantes = $stmt->fetchAll();
        
        $lines = [];
        
        // Encabezado del archivo
        $lines[] = "607"; // Tipo de reporte
        $lines[] = str_pad($this->rncEmisor, 11, '0', STR_PAD_LEFT); // RNC Emisor
        $lines[] = str_pad($this->razonSocialEmisor, 50); // Razón Social
        $lines[] = str_pad($anio, 4); // Año
        $lines[] = str_pad($mes, 2, '0', STR_PAD_LEFT); // Mes
        $lines[] = str_pad(count($comprobantes), 6, '0', STR_PAD_LEFT); // Cantidad de registros
        
        // Detalle de retenciones
        foreach ($comprobantes as $comp) {
            $line = '';
            $line .= str_pad($comp['tipo_comprobante_codigo'], 2); // Tipo de comprobante
            $line .= str_pad($comp['numero_ncf'], 19); // NCF
            $line .= str_pad($comp['cliente_cedula'] ?? '', 11, '0', STR_PAD_LEFT); // RNC/Cédula Cliente
            $line .= str_pad(substr($comp['cliente_nombre'] . ' ' . $comp['cliente_apellido'], 0, 50), 50); // Nombre Cliente
            $line .= str_pad(date('Ymd', strtotime($comp['fecha_emision'])), 8); // Fecha (YYYYMMDD)
            $montoTotal = number_format($comp['monto_total'], 2, '', '');
            $montoRetenido = number_format($comp['monto_retencion'] ?? 0, 2, '', '');
            
            $line .= str_pad($montoTotal, 18, '0', STR_PAD_LEFT); // Monto Total
            $line .= str_pad($montoRetenido, 18, '0', STR_PAD_LEFT); // Monto Retenido ISR
            $line .= str_pad('01', 2); // Tipo de Retención (01 = ISR)
            $line .= str_pad('0', 18, '0', STR_PAD_LEFT); // ITBIS Retenido
            $line .= str_pad('0', 18, '0', STR_PAD_LEFT); // Propina Retenida
            
            $lines[] = $line;
        }
        
        return implode("\r\n", $lines);
    }

    /**
     * Generar Reporte 608 - Ventas y Servicios
     */
    public function generar608($periodo) {
        $mes = substr($periodo, 5, 2);
        $anio = substr($periodo, 0, 4);
        
        // Obtener comprobantes del período
        $stmt = $this->db->query(
            "SELECT cf.*, tc.codigo as tipo_comprobante_codigo,
                    c.cedula as cliente_cedula, c.nombre as cliente_nombre, c.apellido as cliente_apellido
             FROM comprobantes_fiscales cf
             INNER JOIN tipos_comprobantes tc ON cf.tipo_comprobante_id = tc.id
             LEFT JOIN clientes c ON cf.cliente_id = c.id
             WHERE DATE_FORMAT(cf.fecha_emision, '%Y-%m') = ?
             AND tc.codigo IN ('01', '02', '03', '04', '14', '15')
             AND cf.dgii_enviado = 0
             ORDER BY cf.fecha_emision, cf.numero_ncf",
            [$periodo]
        );
        
        $comprobantes = $stmt->fetchAll();
        
        $lines = [];
        
        // Encabezado del archivo
        $lines[] = "608"; // Tipo de reporte
        $lines[] = str_pad($this->rncEmisor, 11, '0', STR_PAD_LEFT); // RNC Emisor
        $lines[] = str_pad($this->razonSocialEmisor, 50); // Razón Social
        $lines[] = str_pad($anio, 4); // Año
        $lines[] = str_pad($mes, 2, '0', STR_PAD_LEFT); // Mes
        $lines[] = str_pad(count($comprobantes), 6, '0', STR_PAD_LEFT); // Cantidad de registros
        
        // Detalle de comprobantes
        foreach ($comprobantes as $comp) {
            $line = '';
            $line .= str_pad($comp['tipo_comprobante_codigo'], 2); // Tipo de comprobante
            $line .= str_pad($comp['numero_ncf'], 19); // NCF
            $line .= str_pad($comp['cliente_cedula'] ?? '', 11, '0', STR_PAD_LEFT); // RNC/Cédula Cliente
            $line .= str_pad(substr($comp['cliente_nombre'] . ' ' . $comp['cliente_apellido'], 0, 50), 50); // Nombre Cliente
            $line .= str_pad(date('Ymd', strtotime($comp['fecha_emision'])), 8); // Fecha (YYYYMMDD)
            $line .= str_pad(number_format($comp['monto_subtotal'], 2, '', ''), 18, '0', STR_PAD_LEFT); // Monto Facturado
            $line .= str_pad(number_format($comp['monto_impuestos'] ?? 0, 2, '', ''), 18, '0', STR_PAD_LEFT); // ITBIS Facturado
            $line .= str_pad('0', 18, '0', STR_PAD_LEFT); // ITBIS Retenido por Terceros
            $line .= str_pad('0', 18, '0', STR_PAD_LEFT); // ITBIS Retenido por el Emisor
            $line .= str_pad('0', 18, '0', STR_PAD_LEFT); // ISC
            $line .= str_pad('0', 18, '0', STR_PAD_LEFT); // Otros Impuestos
            $line .= str_pad('0', 18, '0', STR_PAD_LEFT); // Monto Exento
            $line .= str_pad('0', 18, '0', STR_PAD_LEFT); // Monto Exonerado
            $line .= str_pad('0', 18, '0', STR_PAD_LEFT); // Propina Legal
            $line .= str_pad('0', 18, '0', STR_PAD_LEFT); // Monto Total
            
            $lines[] = $line;
        }
        
        return implode("\r\n", $lines);
    }

    /**
     * Generar Reporte 609 - Compras
     */
    public function generar609($periodo) {
        $mes = substr($periodo, 5, 2);
        $anio = substr($periodo, 0, 4);
        
        // Obtener comprobantes de compras
        $stmt = $this->db->query(
            "SELECT cf.*, tc.codigo as tipo_comprobante_codigo,
                    p.nombre as proveedor_nombre, p.rnc as proveedor_rnc, p.cedula as proveedor_cedula
             FROM comprobantes_fiscales cf
             INNER JOIN tipos_comprobantes tc ON cf.tipo_comprobante_id = tc.id
             LEFT JOIN proveedores p ON cf.proveedor_id = p.id
             WHERE DATE_FORMAT(cf.fecha_emision, '%Y-%m') = ?
             AND tc.codigo IN ('43', '44', '45')
             AND cf.dgii_enviado = 0
             ORDER BY cf.fecha_emision, cf.numero_ncf",
            [$periodo]
        );
        
        $comprobantes = $stmt->fetchAll();
        
        $lines = [];
        
        // Encabezado del archivo
        $lines[] = "609"; // Tipo de reporte
        $lines[] = str_pad($this->rncEmisor, 11, '0', STR_PAD_LEFT); // RNC Emisor
        $lines[] = str_pad($this->razonSocialEmisor, 50); // Razón Social
        $lines[] = str_pad($anio, 4); // Año
        $lines[] = str_pad($mes, 2, '0', STR_PAD_LEFT); // Mes
        $lines[] = str_pad(count($comprobantes), 6, '0', STR_PAD_LEFT); // Cantidad de registros
        
        // Detalle de compras
        foreach ($comprobantes as $comp) {
            $rncProveedor = $comp['proveedor_rnc'] ?? $comp['proveedor_cedula'] ?? '';
            
            $line = '';
            $line .= str_pad($comp['tipo_comprobante_codigo'], 2); // Tipo de comprobante
            $line .= str_pad($comp['numero_ncf'], 19); // NCF
            $line .= str_pad($rncProveedor, 11, '0', STR_PAD_LEFT); // RNC/Cédula Proveedor
            $line .= str_pad(substr($comp['proveedor_nombre'] ?? '', 0, 50), 50); // Nombre Proveedor
            $line .= str_pad(date('Ymd', strtotime($comp['fecha_emision'])), 8); // Fecha (YYYYMMDD)
            $montoFacturado = number_format($comp['monto_subtotal'], 2, '', '');
            $itbisFacturado = number_format($comp['monto_impuestos'] ?? 0, 2, '', '');
            $montoTotal = number_format($comp['monto_total'], 2, '', '');
            
            $line .= str_pad($montoFacturado, 18, '0', STR_PAD_LEFT); // Monto Facturado
            $line .= str_pad($itbisFacturado, 18, '0', STR_PAD_LEFT); // ITBIS Facturado
            $line .= str_pad('0', 18, '0', STR_PAD_LEFT); // ITBIS Retenido
            $line .= str_pad('0', 18, '0', STR_PAD_LEFT); // ISC
            $line .= str_pad('0', 18, '0', STR_PAD_LEFT); // Otros Impuestos
            $line .= str_pad('0', 18, '0', STR_PAD_LEFT); // Monto Exento
            $line .= str_pad('0', 18, '0', STR_PAD_LEFT); // Monto Exonerado
            $line .= str_pad('0', 18, '0', STR_PAD_LEFT); // Propina Legal
            $line .= str_pad($montoTotal, 18, '0', STR_PAD_LEFT); // Monto Total
            
            $lines[] = $line;
        }
        
        return implode("\r\n", $lines);
    }

    /**
     * Obtener datos del reporte (para Excel y PDF)
     */
    private function obtenerDatosReporte($tipo, $periodo) {
        $mes = substr($periodo, 5, 2);
        $anio = substr($periodo, 0, 4);
        
        switch ($tipo) {
            case '606':
                $stmt = $this->db->query(
                    "SELECT cf.*, tc.codigo as tipo_comprobante_codigo,
                            c.cedula as cliente_cedula, c.nombre as cliente_nombre, c.apellido as cliente_apellido
                     FROM comprobantes_fiscales cf
                     INNER JOIN tipos_comprobantes tc ON cf.tipo_comprobante_id = tc.id
                     LEFT JOIN clientes c ON cf.cliente_id = c.id
                     WHERE DATE_FORMAT(cf.fecha_emision, '%Y-%m') = ?
                     AND tc.codigo IN ('01', '02')
                     AND cf.dgii_enviado = 0
                     ORDER BY cf.fecha_emision, cf.numero_ncf",
                    [$periodo]
                );
                break;
            case '607':
                $stmt = $this->db->query(
                    "SELECT cf.*, tc.codigo as tipo_comprobante_codigo,
                            c.cedula as cliente_cedula, c.nombre as cliente_nombre, c.apellido as cliente_apellido
                     FROM comprobantes_fiscales cf
                     INNER JOIN tipos_comprobantes tc ON cf.tipo_comprobante_id = tc.id
                     LEFT JOIN clientes c ON cf.cliente_id = c.id
                     WHERE DATE_FORMAT(cf.fecha_emision, '%Y-%m') = ?
                     AND cf.tiene_retencion = 1
                     AND cf.dgii_enviado = 0
                     ORDER BY cf.fecha_emision, cf.numero_ncf",
                    [$periodo]
                );
                break;
            case '608':
                $stmt = $this->db->query(
                    "SELECT cf.*, tc.codigo as tipo_comprobante_codigo,
                            c.cedula as cliente_cedula, c.nombre as cliente_nombre, c.apellido as cliente_apellido
                     FROM comprobantes_fiscales cf
                     INNER JOIN tipos_comprobantes tc ON cf.tipo_comprobante_id = tc.id
                     LEFT JOIN clientes c ON cf.cliente_id = c.id
                     WHERE DATE_FORMAT(cf.fecha_emision, '%Y-%m') = ?
                     AND tc.codigo IN ('01', '02', '03', '04', '14', '15')
                     AND cf.dgii_enviado = 0
                     ORDER BY cf.fecha_emision, cf.numero_ncf",
                    [$periodo]
                );
                break;
            case '609':
                $stmt = $this->db->query(
                    "SELECT cf.*, tc.codigo as tipo_comprobante_codigo,
                            p.nombre as proveedor_nombre, p.rnc as proveedor_rnc, p.cedula as proveedor_cedula
                     FROM comprobantes_fiscales cf
                     INNER JOIN tipos_comprobantes tc ON cf.tipo_comprobante_id = tc.id
                     LEFT JOIN proveedores p ON cf.proveedor_id = p.id
                     WHERE DATE_FORMAT(cf.fecha_emision, '%Y-%m') = ?
                     AND tc.codigo IN ('43', '44', '45')
                     AND cf.dgii_enviado = 0
                     ORDER BY cf.fecha_emision, cf.numero_ncf",
                    [$periodo]
                );
                break;
            default:
                return [];
        }
        
        return $stmt->fetchAll();
    }

    /**
     * Generar reporte en Excel
     */
    public function generarExcel($tipo, $periodo) {
        // Verificar si PhpSpreadsheet está disponible
        if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
            require_once __DIR__ . '/../../vendor/autoload.php';
        }
        
        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            throw new Exception('PhpSpreadsheet no está disponible. Instale con: composer require phpoffice/phpspreadsheet');
        }
        
        $comprobantes = $this->obtenerDatosReporte($tipo, $periodo);
        $mes = substr($periodo, 5, 2);
        $anio = substr($periodo, 0, 4);
        
        $titulos = [
            '606' => 'Reporte 606 - Ventas al Contado',
            '607' => 'Reporte 607 - Retenciones ISR',
            '608' => 'Reporte 608 - Ventas y Servicios',
            '609' => 'Reporte 609 - Compras'
        ];
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($titulos[$tipo] ?? "Reporte $tipo");
        $row = 1;
        
        // Título
        $sheet->setCellValue('A' . $row, $titulos[$tipo] ?? "Reporte $tipo");
        $sheet->mergeCells('A' . $row . ':H' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $row += 2;
        
        // Información
        $sheet->setCellValue('A' . $row, 'RNC Emisor: ' . $this->rncEmisor);
        $row++;
        $sheet->setCellValue('A' . $row, 'Razón Social: ' . $this->razonSocialEmisor);
        $row++;
        $sheet->setCellValue('A' . $row, 'Período: ' . $anio . '-' . $mes);
        $row++;
        $sheet->setCellValue('A' . $row, 'Cantidad de Registros: ' . count($comprobantes));
        $row += 2;
        
        // Encabezados
        $headers = ['Tipo', 'NCF', 'RNC/Cédula', 'Nombre', 'Fecha', 'Monto Total', 'ITBIS', 'Subtotal'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $sheet->getStyle($col . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFCCCCCC');
            $col++;
        }
        $row++;
        
        // Datos
        foreach ($comprobantes as $comp) {
            $sheet->setCellValue('A' . $row, $comp['tipo_comprobante_codigo'] ?? '');
            $sheet->setCellValue('B' . $row, $comp['numero_ncf'] ?? '');
            
            if ($tipo === '609') {
                $sheet->setCellValue('C' . $row, $comp['proveedor_rnc'] ?? $comp['proveedor_cedula'] ?? '');
                $sheet->setCellValue('D' . $row, $comp['proveedor_nombre'] ?? '');
            } else {
                $sheet->setCellValue('C' . $row, $comp['cliente_cedula'] ?? '');
                $sheet->setCellValue('D' . $row, ($comp['cliente_nombre'] ?? '') . ' ' . ($comp['cliente_apellido'] ?? ''));
            }
            
            $sheet->setCellValue('E' . $row, date('d/m/Y', strtotime($comp['fecha_emision'])));
            $sheet->setCellValue('F' . $row, $comp['monto_total'] ?? 0);
            $sheet->setCellValue('G' . $row, $comp['monto_impuestos'] ?? 0);
            $sheet->setCellValue('H' . $row, ($comp['monto_total'] ?? 0) - ($comp['monto_impuestos'] ?? 0));
            
            // Formato de números
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            
            $row++;
        }
        
        // Ajustar ancho de columnas
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(40);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(15);
        
        // Generar archivo
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }

    /**
     * Generar reporte en PDF
     */
    public function generarPDF($tipo, $periodo) {
        // Verificar si TCPDF está disponible
        if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
            require_once __DIR__ . '/../../vendor/autoload.php';
        }
        
        if (!class_exists('TCPDF')) {
            throw new Exception('TCPDF no está disponible. Instale con: composer require tecnickcom/tcpdf');
        }
        
        $comprobantes = $this->obtenerDatosReporte($tipo, $periodo);
        $mes = substr($periodo, 5, 2);
        $anio = substr($periodo, 0, 4);
        
        $titulos = [
            '606' => 'Reporte 606 - Ventas al Contado',
            '607' => 'Reporte 607 - Retenciones ISR',
            '608' => 'Reporte 608 - Ventas y Servicios',
            '609' => 'Reporte 609 - Compras'
        ];
        
        $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('ERP Multicajas');
        $pdf->SetAuthor('ERP Multicajas');
        $pdf->SetTitle($titulos[$tipo] ?? "Reporte $tipo");
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->SetFont('helvetica', '', 10);
        
        $pdf->AddPage();
        
        // Encabezado
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, $titulos[$tipo] ?? "Reporte $tipo", 0, 1, 'C');
        $pdf->Ln(5);
        
        // Información
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, 'RNC Emisor: ' . $this->rncEmisor, 0, 1, 'L');
        $pdf->Cell(0, 6, 'Razón Social: ' . $this->razonSocialEmisor, 0, 1, 'L');
        $pdf->Cell(0, 6, 'Período: ' . $anio . '-' . $mes, 0, 1, 'L');
        $pdf->Cell(0, 6, 'Cantidad de Registros: ' . count($comprobantes), 0, 1, 'L');
        $pdf->Ln(5);
        
        // Tabla
        $pdf->SetFont('helvetica', 'B', 10);
        $headers = ['Tipo', 'NCF', 'RNC/Cédula', 'Nombre', 'Fecha', 'Monto Total', 'ITBIS'];
        $widths = [15, 40, 25, 50, 25, 30, 30];
        
        $pdf->SetFillColor(200, 200, 200);
        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();
        
        $pdf->SetFont('helvetica', '', 8);
        $fill = false;
        
        foreach ($comprobantes as $comp) {
            if ($pdf->GetY() > 270) {
                $pdf->AddPage();
                $pdf->SetFont('helvetica', 'B', 10);
                foreach ($headers as $i => $header) {
                    $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
                }
                $pdf->Ln();
                $pdf->SetFont('helvetica', '', 8);
            }
            
            $pdf->Cell($widths[0], 6, $comp['tipo_comprobante_codigo'] ?? '', 1, 0, 'C', $fill);
            $pdf->Cell($widths[1], 6, substr($comp['numero_ncf'] ?? '', 0, 19), 1, 0, 'L', $fill);
            
            if ($tipo === '609') {
                $pdf->Cell($widths[2], 6, substr($comp['proveedor_rnc'] ?? $comp['proveedor_cedula'] ?? '', 0, 11), 1, 0, 'L', $fill);
                $pdf->Cell($widths[3], 6, substr($comp['proveedor_nombre'] ?? '', 0, 30), 1, 0, 'L', $fill);
            } else {
                $pdf->Cell($widths[2], 6, substr($comp['cliente_cedula'] ?? '', 0, 11), 1, 0, 'L', $fill);
                $pdf->Cell($widths[3], 6, substr(($comp['cliente_nombre'] ?? '') . ' ' . ($comp['cliente_apellido'] ?? ''), 0, 30), 1, 0, 'L', $fill);
            }
            
            $pdf->Cell($widths[4], 6, date('d/m/Y', strtotime($comp['fecha_emision'])), 1, 0, 'C', $fill);
            $pdf->Cell($widths[5], 6, 'RD$ ' . number_format($comp['monto_total'] ?? 0, 2), 1, 0, 'R', $fill);
            $pdf->Cell($widths[6], 6, 'RD$ ' . number_format($comp['monto_impuestos'] ?? 0, 2), 1, 0, 'R', $fill);
            $pdf->Ln();
            
            $fill = !$fill;
        }
        
        return $pdf->Output('', 'S');
    }

    /**
     * Guardar reporte en archivo
     */
    public function guardarArchivo($tipo, $periodo, $contenido, $formato = 'txt') {
        $mes = substr($periodo, 5, 2);
        $anio = substr($periodo, 0, 4);
        
        $extension = $formato === 'excel' ? 'xlsx' : ($formato === 'pdf' ? 'pdf' : 'txt');
        $nombreArchivo = "R{$tipo}_{$this->rncEmisor}_{$anio}{$mes}.{$extension}";
        $directorio = __DIR__ . '/../../reportes_dgii/';
        
        if (!is_dir($directorio)) {
            mkdir($directorio, 0777, true);
        }
        
        $rutaArchivo = $directorio . $nombreArchivo;
        
        if ($formato === 'excel' || $formato === 'pdf') {
            file_put_contents($rutaArchivo, $contenido);
        } else {
            file_put_contents($rutaArchivo, $contenido);
        }
        
        return [
            'nombre' => $nombreArchivo,
            'ruta' => $rutaArchivo,
            'tamano' => filesize($rutaArchivo)
        ];
    }
}

