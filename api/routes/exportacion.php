<?php
/**
 * Rutas de Exportación
 */

require_once __DIR__ . '/../controllers/ExportacionController.php';

$controller = new ExportacionController();
$auth = new AuthMiddleware();
$user = $auth->authenticate();

switch ($method) {
    case 'GET':
        $filters = $_GET;
        
        if ($id === 'prestamos') {
            $controller->exportarPrestamos($filters);
        } elseif ($id === 'pagos') {
            $controller->exportarPagos($filters);
        } elseif ($id === 'clientes') {
            $controller->exportarClientes($filters);
        } else {
            sendError('Tipo de exportación no válido', 400);
        }
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}

