<?php
/**
 * Rutas de Reenganche
 */

require_once __DIR__ . '/../controllers/ReengancheController.php';

$controller = new ReengancheController();
$auth = new AuthMiddleware();
$user = $auth->requireRole(['supervisor', 'admin']);

switch ($method) {
    case 'GET':
        if ($id === 'elegibles') {
            $controller->getElegibles();
        } else {
            sendError('Acción no válida', 400);
        }
        break;
    
    case 'POST':
        $data = getRequestBody();
        $controller->procesar($data, $user);
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


