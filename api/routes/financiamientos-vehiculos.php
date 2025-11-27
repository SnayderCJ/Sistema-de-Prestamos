<?php
/**
 * Rutas de Financiamientos de Vehículos
 */

require_once __DIR__ . '/../controllers/FinanciamientoVehiculoController.php';

$controller = new FinanciamientoVehiculoController();
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
        $controller->create($data);
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


