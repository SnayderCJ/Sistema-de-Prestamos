<?php
/**
 * Rutas de Legal
 */

require_once __DIR__ . '/../controllers/LegalController.php';

$controller = new LegalController();
$auth = new AuthMiddleware();
$user = $auth->requireRole(['admin', 'legal']);

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
        $controller->crearAsiento($data);
        break;
    
    case 'PUT':
        if (!$id) {
            sendError('ID de asiento requerido', 400);
        }
        $data = getRequestBody();
        $controller->update($id, $data);
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


