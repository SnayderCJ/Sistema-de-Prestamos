<?php
/**
 * Rutas de Tipos de Comprobantes
 */

require_once __DIR__ . '/../controllers/TipoComprobanteController.php';

$controller = new TipoComprobanteController();
$auth = new AuthMiddleware();
$user = $auth->authenticate();

switch ($method) {
    case 'GET':
        if ($id) {
            $controller->getById($id);
        } else {
            $controller->getAll();
        }
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


