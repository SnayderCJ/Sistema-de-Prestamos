<?php
/**
 * Rutas de Órdenes de Incautación
 */

require_once __DIR__ . '/../controllers/OrdenIncautacionController.php';

$controller = new OrdenIncautacionController();
$auth = new AuthMiddleware();
$user = $auth->requireRole(['admin', 'supervisor']);

switch ($method) {
    case 'GET':
        if ($id) {
            $controller->getById($id);
        } else {
            $filters = $_GET;
            $controller->getAll($filters);
        }
        break;
    
    case 'POST':
        $data = getRequestBody();
        $controller->create($data);
        break;
    
    case 'PUT':
        if (!$id) {
            sendError('ID de orden requerido', 400);
        }
        if ($id === 'ejecutar') {
            $ordenId = $_GET['id'] ?? null;
            if (!$ordenId) {
                sendError('ID de orden requerido', 400);
            }
            $data = getRequestBody();
            $controller->ejecutar($ordenId, $data);
        } else {
            sendError('Acción no válida', 400);
        }
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


