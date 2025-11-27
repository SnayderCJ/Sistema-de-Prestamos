<?php
/**
 * Rutas de Caja
 */

require_once __DIR__ . '/../controllers/CajaController.php';

$controller = new CajaController();
$auth = new AuthMiddleware();
$user = $auth->authenticate();

switch ($method) {
    case 'GET':
        if ($id === 'abierta') {
            $sucursalId = $_GET['sucursal_id'] ?? $user['sucursal_id'];
            $controller->getAbierta($sucursalId);
        } elseif ($id) {
            $controller->getById($id);
        } else {
            $filters = $_GET;
            $controller->getAll($filters);
        }
        break;
    
    case 'POST':
        if ($id === 'abrir') {
            $data = getRequestBody();
            $controller->abrir($data, $user);
        } elseif ($id === 'cerrar') {
            $cajaId = $_GET['id'] ?? null;
            if (!$cajaId) {
                sendError('ID de caja requerido', 400);
            }
            $data = getRequestBody();
            $controller->cerrar($cajaId, $data, $user);
        } else {
            sendError('Acción no válida', 400);
        }
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


