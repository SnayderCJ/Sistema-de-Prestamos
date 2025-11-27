<?php
/**
 * Rutas de Contabilidad
 */

require_once __DIR__ . '/../controllers/ContabilidadController.php';

$controller = new ContabilidadController();
$auth = new AuthMiddleware();
$user = $auth->requireRole(['admin', 'contador']);

switch ($method) {
    case 'GET':
        if ($id === 'asientos') {
            $filters = $_GET;
            $controller->getAllAsientos($filters);
        } elseif ($id) {
            $controller->getAsientoById($id);
        } else {
            sendError('Recurso no especificado', 400);
        }
        break;
    
    case 'POST':
        if ($id === 'asiento') {
            $data = getRequestBody();
            $controller->crearAsiento($data, $user);
        } else {
            sendError('Acción no válida', 400);
        }
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


