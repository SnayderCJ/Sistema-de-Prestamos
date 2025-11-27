<?php
/**
 * Rutas de Autenticación
 */

require_once __DIR__ . '/../controllers/AuthController.php';

$controller = new AuthController();
$data = getRequestBody();

switch ($method) {
    case 'POST':
        if ($id === 'login') {
            $controller->login($data);
        } elseif ($id === 'register') {
            $controller->register($data);
        } elseif ($id === 'refresh') {
            $controller->refreshToken($data);
        } elseif ($id === 'forgot-password') {
            $controller->forgotPassword($data);
        } elseif ($id === 'reset-password') {
            $controller->resetPassword($data);
        } else {
            sendError('Acción no válida', 400);
        }
        break;
    
    case 'PUT':
        if ($id === 'change-password') {
            require_once __DIR__ . '/../middleware/auth.php';
            $auth = new AuthMiddleware();
            $user = $auth->authenticate();
            if (!$user) {
                sendError('No autorizado', 401);
            }
            $controller->changePassword($data, $user);
        } elseif ($id === 'logout') {
            require_once __DIR__ . '/../middleware/auth.php';
            $auth = new AuthMiddleware();
            $user = $auth->authenticate();
            if (!$user) {
                sendError('No autorizado', 401);
            }
            $controller->logout($user);
        } else {
            sendError('Acción no válida', 400);
        }
        break;
    
    default:
        sendError('Método no permitido', 405);
        break;
}

