<?php
/**
 * Rutas de Dashboard Avanzado
 */

require_once __DIR__ . '/../controllers/DashboardAvanzadoController.php';

$controller = new DashboardAvanzadoController();
$auth = new AuthMiddleware();
$user = $auth->authenticate();

switch ($method) {
    case 'GET':
        if ($id === 'estadisticas-avanzadas') {
            $filters = $_GET;
            $controller->getEstadisticasAvanzadas($user, $filters);
        } elseif ($id === 'cartera') {
            $filters = $_GET;
            $controller->getDatosCartera($user, $filters);
        } else {
            $filters = $_GET;
            $controller->getEstadisticasAvanzadas($user, $filters);
        }
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}

