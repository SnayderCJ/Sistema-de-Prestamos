<?php
/**
 * Rutas de Tasas de Interés
 */

require_once __DIR__ . '/../controllers/TasaController.php';

$controller = new TasaController();
$auth = new AuthMiddleware();
$user = $auth->authenticate();

switch ($method) {
    case 'GET':
        if ($id) {
            $controller->getById($id);
        } elseif (isset($_GET['aplicable'])) {
            $monto = $_GET['monto'] ?? null;
            $plazo = $_GET['plazo'] ?? null;
            if (!$monto || !$plazo) {
                sendError('Monto y plazo son requeridos', 400);
            }
            $controller->getTasaAplicable($monto, $plazo);
        } else {
            $controller->getAll();
        }
        break;
    
    case 'POST':
        $auth->requireRole(['admin']);
        $data = getRequestBody();
        $controller->create($data);
        break;
    
    case 'PUT':
        $auth->requireRole(['admin']);
        if (!$id) {
            sendError('ID de tasa requerido', 400);
        }
        $data = getRequestBody();
        $controller->update($id, $data);
        break;
    
    case 'DELETE':
        $auth->requireRole(['admin']);
        if (!$id) {
            sendError('ID de tasa requerido', 400);
        }
        $controller->delete($id);
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


