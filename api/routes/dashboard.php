<?php
/**
 * Rutas de Dashboard
 */

require_once __DIR__ . '/../controllers/DashboardController.php';

$controller = new DashboardController();
$auth = new AuthMiddleware();
$user = $auth->authenticate();

switch ($method) {
    case 'GET':
        if ($id === 'estadisticas') {
            $controller->getEstadisticas($user);
        } elseif ($id === 'prestamos-vencidos') {
            $controller->getPrestamosVencidos($user);
        } elseif ($id === 'cobros-hoy') {
            $controller->getCobrosHoy($user);
        } else {
            $controller->getDashboard($user);
        }
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


