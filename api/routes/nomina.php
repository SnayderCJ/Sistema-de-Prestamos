<?php
/**
 * Rutas de Nómina
 */

require_once __DIR__ . '/../controllers/NominaController.php';

$controller = new NominaController();
$auth = new AuthMiddleware();
$user = $auth->requireRole(['admin', 'gerente']);

switch ($method) {
    case 'GET':
        if ($id === 'calcular') {
            $empleadoId = $_GET['empleado_id'] ?? null;
            $periodo = $_GET['periodo'] ?? null;
            if (!$empleadoId || !$periodo) {
                sendError('Empleado y período requeridos', 400);
            }
            $calculos = $controller->calcular($empleadoId, $periodo);
            sendResponse($calculos);
        } elseif ($id) {
            $controller->getById($id);
        } else {
            $filters = $_GET;
            $controller->getAll($filters);
        }
        break;
    
    case 'POST':
        $data = getRequestBody();
        $controller->procesar($data);
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


