<?php
/**
 * Rutas de Recordatorios
 */

require_once __DIR__ . '/../controllers/RecordatorioController.php';

$controller = new RecordatorioController();
$auth = new AuthMiddleware();

switch ($method) {
    case 'POST':
        if ($id === 'procesar') {
            // Endpoint para cron job (puede requerir token especial)
            $controller->procesar();
        } elseif ($id === 'enviar') {
            $user = $auth->authenticate();
            $controller->enviarManual();
        } else {
            sendError('Acción no válida', 400);
        }
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}

