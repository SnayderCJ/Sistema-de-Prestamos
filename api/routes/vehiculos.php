<?php
/**
 * Rutas de Vehículos
 */

require_once __DIR__ . '/../controllers/VehiculoController.php';
require_once __DIR__ . '/../middleware/auth.php';

$controller = new VehiculoController();
$auth = new AuthMiddleware();
$user = $auth->authenticate();

if (!$user) {
    sendError('No autorizado', 401);
    exit;
}

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

    case 'PUT':
        if (!$id) {
            sendError('ID de vehículo es requerido', 400);
            exit;
        }
        $data = getRequestBody();
        $controller->update($id, $data, $user);
        break;

    case 'DELETE':
        if (!$id) {
            sendError('ID de vehículo es requerido', 400);
            exit;
        }
        $controller->delete($id, $user);
        break;

    default:
        sendError('Método no permitido', 405);
        break;
}
