<?php
/**
 * Rutas de Impuestos
 */

require_once __DIR__ . '/../controllers/ImpuestoController.php';

$controller = new ImpuestoController();
$auth = new AuthMiddleware();
$user = $auth->requireRole(['admin', 'contador']);

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
    
    case 'PUT':
        if (!$id) {
            sendError('ID de impuesto requerido', 400);
        }
        $data = getRequestBody();
        $controller->update($id, $data);
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


