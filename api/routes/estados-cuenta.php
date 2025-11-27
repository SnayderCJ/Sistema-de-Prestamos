<?php
/**
 * Rutas de Estados de Cuenta
 */

require_once __DIR__ . '/../controllers/EstadoCuentaController.php';

$controller = new EstadoCuentaController();
$auth = new AuthMiddleware();
$user = $auth->authenticate();

switch ($method) {
    case 'GET':
        if ($id) {
            $controller->getById($id);
        } else {
            $clienteId = $_GET['cliente_id'] ?? null;
            $controller->getAll($clienteId);
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


