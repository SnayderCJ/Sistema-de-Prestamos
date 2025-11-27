<?php
/**
 * Controlador de Exportación de Datos
 */

require_once __DIR__ . '/../services/ExportacionService.php';

class ExportacionController {
    private $exportacionService;
    
    public function __construct() {
        $this->exportacionService = new ExportacionService();
    }
    
    /**
     * Exportar préstamos a CSV
     */
    public function exportarPrestamos($filters = []) {
        $csv = $this->exportacionService->exportarPrestamosCSV($filters);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="prestamos_' . date('Y-m-d') . '.csv"');
        echo $csv;
        exit;
    }
    
    /**
     * Exportar pagos a CSV
     */
    public function exportarPagos($filters = []) {
        $csv = $this->exportacionService->exportarPagosCSV($filters);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="pagos_' . date('Y-m-d') . '.csv"');
        echo $csv;
        exit;
    }
    
    /**
     * Exportar clientes a CSV
     */
    public function exportarClientes($filters = []) {
        $csv = $this->exportacionService->exportarClientesCSV($filters);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="clientes_' . date('Y-m-d') . '.csv"');
        echo $csv;
        exit;
    }
}

