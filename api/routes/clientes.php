<?php
/**
 * Rutas de Clientes
 */

require_once __DIR__ . '/../controllers/ClienteController.php';

$controller = new ClienteController();
$auth = new AuthMiddleware();
$user = $auth->authenticate();

switch ($method) {
    case 'GET':
        if ($id) {
            $controller->getById($id);
        } else {
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['per_page'] ?? 20;
            $filters = $_GET;
            $controller->getAll($page, $perPage, $filters);
        }
        break;
    
    case 'POST':
        $data = getRequestBody();
        $controller->create($data);
        break;
    
    case 'PUT':
        if (!$id) {
            sendError('ID de cliente requerido', 400);
        }
        $data = getRequestBody();
        $controller->update($id, $data);
        break;
    
    case 'DELETE':
        if (!$id) {
            sendError('ID de cliente requerido', 400);
        }
        $controller->delete($id);
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


