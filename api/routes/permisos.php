<?php
/**
 * Rutas de Permisos
 */

require_once __DIR__ . '/../controllers/PermisoController.php';

$controller = new PermisoController();
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
            // Obtener permisos de un usuario específico
            $controller->obtenerPermisosUsuario($id, $user);
        } elseif ($id && in_array($id, ['supervisor', 'analista', 'cobrador', 'admin', 'gerente'])) {
            // Obtener permisos de un rol
            $controller->obtenerPermisosRol($id);
        } else {
            // Obtener permisos del usuario actual
            $controller->obtenerPermisos($user);
        }
        break;
    
    case 'PUT':
        if ($id && is_numeric($id)) {
            // Actualizar permisos de un usuario
            $controller->actualizarPermisos($id, $data, $user);
        } elseif ($id && in_array($id, ['supervisor', 'analista', 'cobrador', 'admin', 'gerente'])) {
            // Actualizar permisos de un rol
            $controller->actualizarPermisosRol($id, $data, $user);
        } else {
            sendError('ID de usuario o rol requerido', 400);
        }
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}

