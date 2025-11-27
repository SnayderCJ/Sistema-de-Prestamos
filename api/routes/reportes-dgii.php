<?php
/**
 * Rutas de Reportes DGII
 */

require_once __DIR__ . '/../controllers/ReporteDGIIController.php';
require_once __DIR__ . '/../middleware/auth.php';

$controller = new ReporteDGIIController();
$auth = new AuthMiddleware();
$user = $auth->requireRole(['admin', 'contador']);

switch ($method) {
    case 'GET':
        if ($id === 'descargar-txt') {
            $tipo = $_GET['tipo'] ?? '';
            $periodo = $_GET['periodo'] ?? date('Y-m');
            if (in_array($tipo, ['606', '607', '608', '609'])) {
                $controller->descargarTXT($tipo, $periodo);
            } else {
                sendError('Tipo de reporte no válido', 400);
            }
        } elseif ($id === 'descargar-excel') {
            $tipo = $_GET['tipo'] ?? '';
            $periodo = $_GET['periodo'] ?? date('Y-m');
            if (in_array($tipo, ['606', '607', '608', '609'])) {
                $controller->descargarExcel($tipo, $periodo);
            } else {
                sendError('Tipo de reporte no válido', 400);
            }
        } elseif ($id === 'descargar-pdf') {
            $tipo = $_GET['tipo'] ?? '';
            $periodo = $_GET['periodo'] ?? date('Y-m');
            if (in_array($tipo, ['606', '607', '608', '609'])) {
                $controller->descargarPDF($tipo, $periodo);
            } else {
                sendError('Tipo de reporte no válido', 400);
            }
        } elseif ($id) {
            $controller->getById($id);
        } else {
            $filters = $_GET;
            $controller->getAll($filters);
        }
        break;
    
    case 'POST':
        $periodo = $_GET['periodo'] ?? date('Y-m');
        if ($id === '606') {
            $controller->generar606($periodo);
        } elseif ($id === '607') {
            $controller->generar607($periodo);
        } elseif ($id === '608') {
            $controller->generar608($periodo);
        } elseif ($id === '609') {
            $controller->generar609($periodo);
        } else {
            sendError('Tipo de reporte no válido. Use: 606, 607, 608 o 609', 400);
        }
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}
