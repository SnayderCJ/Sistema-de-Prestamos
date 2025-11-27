<?php
/**
 * Rutas de Hipotecas
 */

require_once __DIR__ . '/../controllers/HipotecaController.php';

$controller = new HipotecaController();
$auth = new AuthMiddleware();
$user = $auth->authenticate();

switch ($method) {
    case 'GET':
        if ($id) {
            $controller->getById($id);
        } else {
            $prestamoId = $_GET['prestamo_id'] ?? null;
            $controller->getAll($prestamoId);
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


