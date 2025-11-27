<?php
/**
 * Rutas de Cooperativas
 */

require_once __DIR__ . '/../controllers/CooperativaController.php';
require_once __DIR__ . '/../middleware/auth.php';

$controller = new CooperativaController();
$user = authenticate();

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['path'] ?? '';
$pathParts = explode('/', trim($path, '/'));

// Rutas principales
if ($method === 'GET' && empty($pathParts[0])) {
    // GET /cooperativas
    $filters = $_GET;
    $controller->getAll($filters);
    
} elseif ($method === 'GET' && $pathParts[0] === 'socios') {
    // GET /cooperativas/socios/{cooperativa_id}
    $cooperativaId = $pathParts[1] ?? null;
    if (!$cooperativaId) {
        sendError('ID de cooperativa requerido', 400);
    }
    $filters = $_GET;
    $controller->getSocios($cooperativaId, $filters);
    
} elseif ($method === 'GET' && $pathParts[0] === 'socio') {
    // GET /cooperativas/socio/{socio_id}
    $socioId = $pathParts[1] ?? null;
    if (!$socioId) {
        sendError('ID de socio requerido', 400);
    }
    $controller->getSocioById($socioId);
    
} elseif ($method === 'GET' && $pathParts[0] === 'apartaciones') {
    // GET /cooperativas/apartaciones/{socio_id}
    $socioId = $pathParts[1] ?? null;
    if (!$socioId) {
        sendError('ID de socio requerido', 400);
    }
    $filters = $_GET;
    $controller->getApartaciones($socioId, $filters);
    
} elseif ($method === 'GET' && $pathParts[0] === 'distribuciones') {
    // GET /cooperativas/distribuciones/{cooperativa_id}
    $cooperativaId = $pathParts[1] ?? null;
    if (!$cooperativaId) {
        sendError('ID de cooperativa requerido', 400);
    }
    $filters = $_GET;
    $controller->getDistribuciones($cooperativaId, $filters);
    
} elseif ($method === 'GET' && $pathParts[0] === 'distribucion') {
    // GET /cooperativas/distribucion/{distribucion_id}
    $distribucionId = $pathParts[1] ?? null;
    if (!$distribucionId) {
        sendError('ID de distribución requerido', 400);
    }
    $controller->getDistribucionById($distribucionId);
    
} elseif ($method === 'GET' && is_numeric($pathParts[0])) {
    // GET /cooperativas/{id}
    $controller->getById($pathParts[0]);
    
} elseif ($method === 'POST' && empty($pathParts[0])) {
    // POST /cooperativas
    $data = getRequestBody();
    $controller->create($data);
    
} elseif ($method === 'POST' && $pathParts[0] === 'socio') {
    // POST /cooperativas/socio/{cooperativa_id}
    $cooperativaId = $pathParts[1] ?? null;
    if (!$cooperativaId) {
        sendError('ID de cooperativa requerido', 400);
    }
    $data = getRequestBody();
    $controller->agregarSocio($cooperativaId, $data);
    
} elseif ($method === 'POST' && $pathParts[0] === 'apartacion') {
    // POST /cooperativas/apartacion/{cooperativa_id}
    $cooperativaId = $pathParts[1] ?? null;
    if (!$cooperativaId) {
        sendError('ID de cooperativa requerido', 400);
    }
    $data = getRequestBody();
    $data['registrado_por'] = $user['id'];
    $controller->registrarApartacion($cooperativaId, $data);
    
} elseif ($method === 'POST' && $pathParts[0] === 'calcular-distribucion') {
    // POST /cooperativas/calcular-distribucion/{cooperativa_id}
    $cooperativaId = $pathParts[1] ?? null;
    if (!$cooperativaId) {
        sendError('ID de cooperativa requerido', 400);
    }
    $data = getRequestBody();
    $controller->calcularDistribucionUtilidades($cooperativaId, $data);
    
} elseif ($method === 'POST' && $pathParts[0] === 'aprobar-distribucion') {
    // POST /cooperativas/aprobar-distribucion/{distribucion_id}
    $distribucionId = $pathParts[1] ?? null;
    if (!$distribucionId) {
        sendError('ID de distribución requerido', 400);
    }
    $controller->aprobarDistribucion($distribucionId, $user['id']);
    
} elseif ($method === 'POST' && $pathParts[0] === 'marcar-pago-utilidad') {
    // POST /cooperativas/marcar-pago-utilidad/{detalle_id}
    $detalleId = $pathParts[1] ?? null;
    if (!$detalleId) {
        sendError('ID de detalle requerido', 400);
    }
    $data = getRequestBody();
    $controller->marcarPagoUtilidad($detalleId, $data);
    
} elseif ($method === 'PUT' && is_numeric($pathParts[0])) {
    // PUT /cooperativas/{id}
    $data = getRequestBody();
    $controller->update($pathParts[0], $data);
    
} else {
    sendError('Ruta no encontrada', 404);
}

