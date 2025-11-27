<?php
/**
 * Rutas de Plazos de Atraso
 */

require_once __DIR__ . '/../controllers/PlazoAtrasoController.php';

$controller = new PlazoAtrasoController();
$auth = new AuthMiddleware();
$user = $auth->requireRole(['admin', 'supervisor']);

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
    
    case 'PUT':
        if (!$id) {
            sendError('ID de plazo requerido', 400);
        }
        $data = getRequestBody();
        $controller->update($id, $data);
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


