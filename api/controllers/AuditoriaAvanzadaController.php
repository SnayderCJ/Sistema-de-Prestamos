<?php
/**
 * Controlador de Auditoría Avanzada
 */

require_once __DIR__ . '/../services/AuditoriaAvanzadaService.php';
require_once __DIR__ . '/../utils/QueryOptimizer.php';

class AuditoriaAvanzadaController {
    private $auditoriaService;
    private $queryOptimizer;
    
    public function __construct() {
        $this->auditoriaService = new AuditoriaAvanzadaService();
        $this->queryOptimizer = new QueryOptimizer();
    }
    
    /**
     * Obtener historial de auditoría con filtros avanzados
     */
    public function getHistorial($filters = []) {
        $historial = $this->auditoriaService->obtenerHistorial($filters);
        sendResponse($historial);
    }
    
    /**
     * Obtener estadísticas de auditoría
     */
    public function getEstadisticas($filters = []) {
        $estadisticas = $this->auditoriaService->obtenerEstadisticas($filters);
        sendResponse($estadisticas);
    }
    
    /**
     * Exportar auditoría a CSV
     */
    public function exportarCSV($filters = []) {
        $csv = $this->auditoriaService->exportarCSV($filters);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="auditoria_' . date('Y-m-d') . '.csv"');
        echo $csv;
        exit;
    }
    
    /**
     * Analizar índices de base de datos
     */
    public function analizarIndices() {
        $analisis = $this->queryOptimizer->analizarIndices();
        sendResponse($analisis);
    }
    
    /**
     * Analizar consulta SQL
     */
    public function analizarConsulta() {
        $sql = $_POST['sql'] ?? $_GET['sql'] ?? null;
        $params = $_POST['params'] ?? $_GET['params'] ?? [];
        
        if (!$sql) {
            sendError('SQL requerido', 400);
            return;
        }
        
        if (is_string($params)) {
            $params = json_decode($params, true) ?? [];
        }
        
        $analisis = $this->queryOptimizer->analizarConsulta($sql, $params);
        sendResponse($analisis);
    }
    
    /**
     * Obtener estadísticas de rendimiento
     */
    public function getRendimiento() {
        $estadisticas = $this->queryOptimizer->obtenerEstadisticasRendimiento();
        sendResponse($estadisticas);
    }
    
    /**
     * Limpiar auditoría antigua
     */
    public function limpiar($dias = 365) {
        $auth = new AuthMiddleware();
        $user = $auth->requireRole(['admin']);
        
        $eliminados = $this->auditoriaService->limpiarAuditoriaAntigua($dias);
        
        sendResponse([
            'mensaje' => "Se eliminaron $eliminados registros de auditoría",
            'dias' => $dias,
            'registros_eliminados' => $eliminados
        ]);
    }
}

