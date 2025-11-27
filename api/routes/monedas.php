<?php
/**
 * Rutas de Monedas
 */

require_once __DIR__ . '/../controllers/MonedaController.php';

$controller = new MonedaController();
$auth = new AuthMiddleware();
$user = $auth->authenticate();

switch ($method) {
    case 'GET':
        if ($id) {
            $controller->getById($id);
        } else {
            $controller->getAll();
        }
        break;
    
    case 'PUT':
        if (!$id) {
            sendError('ID de moneda requerido', 400);
        }
        $data = getRequestBody();
        $controller->updateTasa($id, $data);
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


