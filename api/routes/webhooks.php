<?php
/**
 * Rutas de Webhooks
 */

require_once __DIR__ . '/../controllers/WebhookController.php';

$controller = new WebhookController();
$auth = new AuthMiddleware();
$user = $auth->authenticate();

if (!$user) {
    sendError('No autorizado', 401);
    exit;
}

$data = getRequestBody();

switch ($method) {
    case 'GET':
        if ($id && is_numeric($id)) {
            if (isset($_GET['historial'])) {
                $controller->getHistorial($id, $user);
            } else {
                $controller->getById($id, $user);
            }
        } else {
            $controller->getAll($user);
        }
        break;
    
    case 'POST':
        if ($id === 'probar' && isset($data['webhook_id'])) {
            $controller->probar($data['webhook_id'], $user);
        } else {
            $controller->create($data, $user);
        }
        break;
    
    case 'PUT':
        if ($id && is_numeric($id)) {
            if (isset($data['accion']) && $data['accion'] === 'reactivar') {
                $controller->reactivar($id, $user);
            } else {
                $controller->update($id, $data, $user);
            }
        } else {
            sendError('ID de webhook requerido', 400);
        }
        break;
    
    case 'DELETE':
        if ($id && is_numeric($id)) {
            $controller->delete($id, $user);
        } else {
            sendError('ID de webhook requerido', 400);
        }
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}

