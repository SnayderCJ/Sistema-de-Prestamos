<?php
/**
 * Rutas de Empleados
 */

require_once __DIR__ . '/../controllers/EmpleadoController.php';

$controller = new EmpleadoController();
$auth = new AuthMiddleware();
$user = $auth->requireRole(['admin', 'gerente', 'rrhh']);

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
            sendError('ID de empleado requerido', 400);
        }
        $data = getRequestBody();
        $controller->update($id, $data);
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


