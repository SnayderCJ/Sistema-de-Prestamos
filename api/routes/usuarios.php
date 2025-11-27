<?php
/**
 * Rutas de Usuarios
 */

require_once __DIR__ . '/../controllers/UsuarioController.php';

$controller = new UsuarioController();
$auth = new AuthMiddleware();
$user = $auth->requireRole(['admin']);

switch ($method) {
    case 'GET':
        if ($id) {
            $controller->getById($id);
        } else {
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['per_page'] ?? 20;
            $controller->getAll($page, $perPage);
        }
        break;
    
    case 'POST':
        $data = getRequestBody();
        $controller->create($data);
        break;
    
    case 'PUT':
        if (!$id) {
            sendError('ID de usuario requerido', 400);
        }
        $data = getRequestBody();
        $controller->update($id, $data);
        break;
    
    case 'DELETE':
        if (!$id) {
            sendError('ID de usuario requerido', 400);
        }
        $controller->delete($id);
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


