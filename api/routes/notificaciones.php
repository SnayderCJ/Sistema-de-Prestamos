<?php
/**
 * Rutas de Notificaciones
 */

require_once __DIR__ . '/../controllers/NotificacionController.php';

$controller = new NotificacionController();
$auth = new AuthMiddleware();
$user = $auth->authenticate();

if (!$user) {
    sendError('No autorizado', 401);
    exit;
}

$data = getRequestBody();

switch ($method) {
    case 'GET':
        if ($id === 'cantidad-no-leidas') {
            $controller->obtenerCantidadNoLeidas($user);
        } else {
            $filtros = $_GET;
            $controller->obtenerNotificaciones($user, $filtros);
        }
        break;
    
    case 'POST':
        if ($id === 'registrar-dispositivo') {
            $controller->registrarDispositivo($data, $user);
        } elseif ($id === 'enviar-prueba') {
            $controller->enviarPrueba($data, $user);
        } else {
            sendError('Acción no válida', 400);
        }
        break;
    
    case 'PUT':
        if ($id === 'marcar-leida') {
            if (!isset($data['notificacion_id'])) {
                sendError('ID de notificación requerido', 400);
            } else {
                $controller->marcarComoLeida($data['notificacion_id'], $user);
            }
        } elseif ($id === 'marcar-todas-leidas') {
            $controller->marcarTodasComoLeidas($user);
        } elseif ($id === 'desactivar-dispositivo') {
            $controller->desactivarDispositivo($data, $user);
        } else {
            sendError('Acción no válida', 400);
        }
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}

