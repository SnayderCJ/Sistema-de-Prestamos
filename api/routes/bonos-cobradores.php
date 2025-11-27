<?php
/**
 * Rutas de Bonos a Cobradores
 */

require_once __DIR__ . '/../controllers/BonoCobradorController.php';

$controller = new BonoCobradorController();
$auth = new AuthMiddleware();
$user = $auth->authenticate();

switch ($method) {
    case 'GET':
        if ($id === 'calcular') {
            $cobradorId = $_GET['cobrador_id'] ?? null;
            $periodo = $_GET['periodo'] ?? null;
            if (!$cobradorId || !$periodo) {
                sendError('Cobrador y período requeridos', 400);
            }
            $calculos = $controller->calcular($cobradorId, $periodo);
            sendResponse($calculos);
        } elseif ($id) {
            $controller->getById($id);
        } else {
            $filters = $_GET;
            $controller->getAll($filters);
        }
        break;
    
    case 'POST':
        $data = getRequestBody();
        $controller->procesar($data);
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}


