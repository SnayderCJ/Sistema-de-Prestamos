<?php
/**
 * Rutas de Desembolsos
 */

require_once __DIR__ . '/../controllers/DesembolsoController.php';

$controller = new DesembolsoController();
$auth = new AuthMiddleware();
$user = $auth->authenticate();

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
        $controller->create($data, $user);
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


