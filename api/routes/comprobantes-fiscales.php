<?php
/**
 * Rutas de Comprobantes Fiscales (NCF)
 */

require_once __DIR__ . '/../controllers/ComprobanteFiscalController.php';

$controller = new ComprobanteFiscalController();
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
        $controller->generar($data);
        break;
    
    case 'PUT':
        if (!$id) {
            sendError('ID de comprobante requerido', 400);
        }
        if (isset($data['anular'])) {
            $controller->anular($id, $data);
        } else {
            sendError('Acción no válida', 400);
        }
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


