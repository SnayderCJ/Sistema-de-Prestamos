<?php
/**
 * Controlador de Reportes
 */

require_once __DIR__ . '/../services/ReporteService.php';

class ReporteController {
    private $db;
    private $reporteService;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->reporteService = new ReporteService();
    }
    
    public function getPrestamos($filters = []) {
        $reporte = $this->reporteService->generarReportePrestamos($filters);
        sendResponse($reporte);
    }
    
    public function getCobros($filters = []) {
        $reporte = $this->reporteService->generarReporteCobros($filters);
        sendResponse($reporte);
    }
    
    public function getMora($filters = []) {
        $reporte = $this->reporteService->generarReporteMora($filters);
        sendResponse($reporte);
    }
    
    public function getClientes($filters = []) {
        $reporte = $this->reporteService->generarReporteClientes($filters);
        sendResponse($reporte);
    }
    
    public function getDashboard($filters = []) {
        $reporte = $this->reporteService->generarReporteDashboard($filters);
        sendResponse($reporte);
    }
    
    public function exportarPDF($tipo, $filters = []) {
        try {
            // Validar tipo de reporte
            $tiposValidos = ['prestamos', 'cobros', 'mora'];
            if (!in_array($tipo, $tiposValidos)) {
                sendError('Tipo de reporte no válido. Tipos permitidos: ' . implode(', ', $tiposValidos), 400);
                return;
            }
            
            // Validar filtros de fecha
            if (isset($filters['fecha_desde']) && isset($filters['fecha_hasta'])) {
                $fechaDesde = strtotime($filters['fecha_desde']);
                $fechaHasta = strtotime($filters['fecha_hasta']);
                
                if ($fechaDesde === false || $fechaHasta === false) {
                    sendError('Fechas inválidas', 400);
                    return;
                }
                
                if ($fechaDesde > $fechaHasta) {
                    sendError('La fecha desde no puede ser mayor que la fecha hasta', 400);
                    return;
                }
            }
            
            $pdf = $this->reporteService->generarPDF($tipo, $filters);
            
            // Verificar si es JSON (fallback) o PDF real
            if (is_string($pdf) && (substr($pdf, 0, 1) === '{' || substr($pdf, 0, 1) === '[')) {
                // Es JSON, no PDF real
                sendError('Error: TCPDF no está instalado. Por favor, instale las dependencias: composer require tecnickcom/tcpdf', 500);
                return;
            }
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="reporte_' . $tipo . '_' . date('Y-m-d') . '.pdf"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;
            exit;
        } catch (Exception $e) {
            error_log("Error exportando PDF: " . $e->getMessage());
            sendError('Error al generar el reporte PDF: ' . $e->getMessage(), 500);
        }
    }
    
    public function exportarExcel($tipo, $filters = []) {
        try {
            // Validar tipo de reporte
            $tiposValidos = ['prestamos', 'cobros', 'mora'];
            if (!in_array($tipo, $tiposValidos)) {
                sendError('Tipo de reporte no válido. Tipos permitidos: ' . implode(', ', $tiposValidos), 400);
                return;
            }
            
            // Validar filtros de fecha
            if (isset($filters['fecha_desde']) && isset($filters['fecha_hasta'])) {
                $fechaDesde = strtotime($filters['fecha_desde']);
                $fechaHasta = strtotime($filters['fecha_hasta']);
                
                if ($fechaDesde === false || $fechaHasta === false) {
                    sendError('Fechas inválidas', 400);
                    return;
                }
                
                if ($fechaDesde > $fechaHasta) {
                    sendError('La fecha desde no puede ser mayor que la fecha hasta', 400);
                    return;
                }
            }
            
            $excel = $this->reporteService->generarExcel($tipo, $filters);
            
            // Detectar si es CSV (fallback) o Excel real
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            $extension = 'xlsx';
            
            if (substr($excel, 0, 10) === 'Reporte: ' || strpos($excel, "\n") !== false && strpos($excel, ',') !== false) {
                // Es CSV
                $contentType = 'text/csv';
                $extension = 'csv';
            }
            
            header('Content-Type: ' . $contentType);
            header('Content-Disposition: attachment; filename="reporte_' . $tipo . '_' . date('Y-m-d') . '.' . $extension . '"');
            header('Content-Length: ' . strlen($excel));
            echo $excel;
            exit;
        } catch (Exception $e) {
            error_log("Error exportando Excel: " . $e->getMessage());
            sendError('Error al generar el reporte Excel: ' . $e->getMessage(), 500);
        }
    }
}


