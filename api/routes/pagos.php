<?php
/**
 * Rutas de Pagos
 */

require_once __DIR__ . '/../controllers/PagoController.php';

$controller = new PagoController();
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
        $controller->create($data, $user);
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


