<?php
/**
 * Rutas de Departamentos
 */

require_once __DIR__ . '/../controllers/DepartamentoController.php';

$controller = new DepartamentoController();
$auth = new AuthMiddleware();
$user = $auth->requireRole(['admin', 'gerente']);

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
            sendError('ID de departamento requerido', 400);
        }
        $data = getRequestBody();
        $controller->update($id, $data);
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


