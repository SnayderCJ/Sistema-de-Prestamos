<?php
/**
 * Rutas de Configuración
 */

require_once __DIR__ . '/../controllers/ConfiguracionController.php';

$controller = new ConfiguracionController();
$auth = new AuthMiddleware();
$user = $auth->authenticate();

if (!$user) {
    sendError('No autorizado', 401);
    exit;
}

$data = getRequestBody();

switch ($method) {
    case 'GET':
        if ($id) {
            $controller->getByClave($id, $user);
        } else {
            $controller->getAll($user);
        }
        break;
    
    case 'POST':
        $controller->create($data, $user);
        break;
    
    case 'PUT':
        $controller->update($data, $user);
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}

