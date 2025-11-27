<?php
/**
 * Rutas de WhatsApp CRM
 */

require_once __DIR__ . '/../controllers/WhatsAppController.php';

$controller = new WhatsAppController();
$auth = new AuthMiddleware();

// Webhook es público (sin autenticación)
if ($id === 'webhook' && $method === 'GET') {
    $controller->webhook();
    exit;
}

if ($id === 'webhook' && $method === 'POST') {
    $controller->webhook();
    exit;
}

// Verificar autenticación para otras rutas
$user = $auth->authenticate();

switch ($method) {
    case 'GET':
        if ($id === 'estadisticas') {
            $user = $auth->requireRole(['admin', 'supervisor']);
            $filters = $_GET;
            $controller->getEstadisticas($filters);
        } elseif ($id === 'conversaciones') {
            $filters = $_GET;
            $controller->getConversaciones($filters);
        } elseif ($id === 'historial') {
            $filters = $_GET;
            $controller->getHistorial($filters);
        } else {
            $filters = $_GET;
            $controller->getHistorial($filters);
        }
        break;
    
    case 'POST':
        $user = $auth->authenticate();
        
        if ($id === 'enviar') {
            $controller->enviarMensaje();
        } elseif ($id === 'notificacion-pago') {
            $data = json_decode(file_get_contents('php://input'), true);
            $controller->enviarNotificacionPago($data['pago_id'] ?? null);
        } elseif ($id === 'recordatorio') {
            $controller->enviarRecordatorio();
        } else {
            sendError('Acción no válida', 400);
        }
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}

