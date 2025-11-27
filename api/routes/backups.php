<?php
/**
 * Rutas de Backups
 */

require_once __DIR__ . '/../controllers/BackupController.php';

$controller = new BackupController();
$auth = new AuthMiddleware();
$user = $auth->authenticate();

if (!$user) {
    sendError('No autorizado', 401);
    exit;
}

switch ($method) {
    case 'GET':
        if ($id && is_numeric($id)) {
            // Obtener backup específico (no implementado aún)
            $controller->getAll($user);
        } else {
            $controller->getAll($user);
        }
        break;
    
    case 'POST':
        if ($id === 'crear') {
            $controller->create($user);
        } elseif ($id === 'restaurar' && isset($_POST['backup_id'])) {
            $controller->restaurar($_POST['backup_id'], $user);
        } elseif ($id === 'limpiar') {
            $controller->limpiar($user);
        } else {
            $controller->create($user);
        }
        break;
    
    case 'DELETE':
        if ($id && is_numeric($id)) {
            $controller->delete($id, $user);
        } else {
            sendError('ID de backup requerido', 400);
        }
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}

