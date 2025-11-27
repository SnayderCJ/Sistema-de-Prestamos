<?php
/**
 * Rutas de Análisis de Préstamos
 */

require_once __DIR__ . '/../controllers/AnalisisController.php';

$controller = new AnalisisController();
$auth = new AuthMiddleware();
$user = $auth->requireRole(['analista', 'supervisor', 'admin']);

switch ($method) {
    case 'GET':
        if ($id) {
            $controller->getById($id);
        } else {
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['per_page'] ?? 20;
            $filters = $_GET;
            $controller->getAll($page, $perPage, $filters);
        }
        break;
    
    case 'POST':
        $data = getRequestBody();
        $controller->create($data, $user);
        break;
    
    case 'PUT':
        if (!$id) {
            sendError('ID de análisis requerido', 400);
        }
        $data = getRequestBody();
        $controller->update($id, $data, $user);
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


