<?php
/**
 * Rutas de Préstamos
 */

require_once __DIR__ . '/../controllers/PrestamoController.php';

$controller = new PrestamoController();
$auth = new AuthMiddleware();
$user = $auth->authenticate();

switch ($method) {
    case 'GET':
        if ($id) {
            $controller->getById($id, $user);
        } else {
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['per_page'] ?? 20;
            $filters = $_GET;
            $controller->getAll($user, $page, $perPage, $filters);
        }
        break;
    
    case 'POST':
        $data = getRequestBody();
        $controller->create($data, $user);
        break;
    
    case 'PUT':
        if (!$id) {
            sendError('ID de préstamo requerido', 400);
        }
        $data = getRequestBody();
        $controller->update($id, $data, $user);
        break;
    
    case 'DELETE':
        if (!$id) {
            sendError('ID de préstamo requerido', 400);
        }
        $controller->delete($id, $user);
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


