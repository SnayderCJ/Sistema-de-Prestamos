<?php
/**
 * Rutas de Bancos
 */

require_once __DIR__ . '/../controllers/BancoController.php';

$controller = new BancoController();
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
    
    case 'POST':
        $data = getRequestBody();
        $controller->create($data);
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


