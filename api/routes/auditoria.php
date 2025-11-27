<?php
/**
 * Rutas de Auditoría
 */

require_once __DIR__ . '/../services/AuditoriaService.php';
require_once __DIR__ . '/../controllers/AuditoriaAvanzadaController.php';

$service = new AuditoriaService();
$controller = new AuditoriaAvanzadaController();
$auth = new AuthMiddleware();
$user = $auth->requireRole(['admin']);

switch ($method) {
    case 'GET':
        if ($id === 'estadisticas') {
            $filters = $_GET;
            $controller->getEstadisticas($filters);
        } elseif ($id === 'exportar-csv') {
            $filters = $_GET;
            $controller->exportarCSV($filters);
        } elseif ($id === 'analizar-indices') {
            $controller->analizarIndices();
        } elseif ($id === 'rendimiento') {
            $controller->getRendimiento();
        } else {
            $filters = $_GET;
            $controller->getHistorial($filters);
        }
        break;
    
    case 'POST':
        if ($id === 'analizar-consulta') {
            $controller->analizarConsulta();
        } elseif ($id === 'limpiar') {
            $dias = $_POST['dias'] ?? 365;
            $controller->limpiar($dias);
        } else {
            sendError('Acción no válida', 400);
        }
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


