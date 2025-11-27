<?php
/**
 * Rutas de Reportes
 */

require_once __DIR__ . '/../controllers/ReporteController.php';

$controller = new ReporteController();
$auth = new AuthMiddleware();
$user = $auth->authenticate();

switch ($method) {
    case 'GET':
        $filters = $_GET;
        
        if ($id === 'prestamos') {
            $controller->getPrestamos($filters);
        } elseif ($id === 'cobros') {
            $controller->getCobros($filters);
        } elseif ($id === 'mora') {
            $controller->getMora($filters);
        } elseif ($id === 'clientes') {
            $controller->getClientes($filters);
        } elseif ($id === 'dashboard') {
            $controller->getDashboard($filters);
        } elseif ($id === 'exportar-pdf') {
            $tipo = $_GET['tipo'] ?? 'prestamos';
            $controller->exportarPDF($tipo, $filters);
        } elseif ($id === 'exportar-excel') {
            $tipo = $_GET['tipo'] ?? 'prestamos';
            $controller->exportarExcel($tipo, $filters);
        } else {
            sendError('Tipo de reporte no válido', 400);
        }
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


