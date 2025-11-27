<?php
/**
 * Rutas de Consultas (Cédulas, Data Créditos)
 */

require_once __DIR__ . '/../controllers/ConsultaController.php';

$controller = new ConsultaController();
$auth = new AuthMiddleware();
$user = $auth->authenticate();

switch ($method) {
    case 'GET':
        if ($id === 'cedula') {
            $cedula = $_GET['cedula'] ?? null;
            if (!$cedula) {
                sendError('Cédula requerida', 400);
            }
            $controller->consultarCedula($cedula, $user);
        } elseif ($id === 'data-creditos') {
            $cedula = $_GET['cedula'] ?? null;
            if (!$cedula) {
                sendError('Cédula requerida', 400);
            }
            $controller->consultarDataCreditos($cedula, $user);
        } elseif ($id === 'historial') {
            $page = $_GET['page'] ?? 1;
            $perPage = $_GET['per_page'] ?? 20;
            $controller->getHistorial($user, $page, $perPage);
        } elseif ($id === 'status') {
            // Endpoint para verificar estado de configuración
            $controller->getStatus();
        } else {
            sendError('Consulta no válida', 400);
        }
        break;
    
    case 'POST':
        $data = getRequestBody();
        if ($id === 'cedula') {
            $cedula = $data['cedula'] ?? null;
            if (!$cedula) {
                sendError('Cédula requerida', 400);
            }
            $controller->consultarCedula($cedula, $user);
        } elseif ($id === 'data-creditos') {
            $cedula = $data['cedula'] ?? null;
            if (!$cedula) {
                sendError('Cédula requerida', 400);
            }
            $controller->consultarDataCreditos($cedula, $user);
        } else {
            sendError('Consulta no válida', 400);
        }
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}
