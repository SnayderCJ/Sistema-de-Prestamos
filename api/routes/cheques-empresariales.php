<?php
/**
 * Rutas de Cheques Empresariales
 */

require_once __DIR__ . '/../controllers/ChequeEmpresarialController.php';

$controller = new ChequeEmpresarialController();
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
    
    case 'PUT':
        if (!$id) {
            sendError('ID de cheque requerido', 400);
        }
        if (isset($data['marcar_cobrado'])) {
            $controller->marcarCobrado($id, $data);
        } else {
            sendError('Acción no válida', 400);
        }
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


