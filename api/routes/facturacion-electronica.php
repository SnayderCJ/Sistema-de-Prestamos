<?php
/**
 * Rutas de Facturación Electrónica
 */

require_once __DIR__ . '/../controllers/FacturacionElectronicaController.php';
require_once __DIR__ . '/../middleware/auth.php';

$controller = new FacturacionElectronicaController();

// Aplicar autenticación a todas las rutas
$user = authenticate();

// Obtener método y ruta
$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['path'] ?? '';

// Parsear ruta
$pathParts = explode('/', trim($path, '/'));

if ($method === 'POST' && $pathParts[0] === 'generar') {
    // POST /facturacion-electronica/generar/{comprobante_id}
    $comprobanteId = $pathParts[1] ?? null;
    
    if (!$comprobanteId) {
        sendError('ID de comprobante requerido', 400);
    }
    
    $controller->generar($comprobanteId);
    
} elseif ($method === 'POST' && $pathParts[0] === 'firmar') {
    // POST /facturacion-electronica/firmar/{comprobante_id}
    $comprobanteId = $pathParts[1] ?? null;
    
    if (!$comprobanteId) {
        sendError('ID de comprobante requerido', 400);
    }
    
    $controller->firmar($comprobanteId);
    
} elseif ($method === 'POST' && $pathParts[0] === 'enviar-dgii') {
    // POST /facturacion-electronica/enviar-dgii/{comprobante_id}
    $comprobanteId = $pathParts[1] ?? null;
    
    if (!$comprobanteId) {
        sendError('ID de comprobante requerido', 400);
    }
    
    $controller->enviarDGII($comprobanteId);
    
} elseif ($method === 'GET' && $pathParts[0] === 'validar-firma') {
    // GET /facturacion-electronica/validar-firma/{comprobante_id}
    $comprobanteId = $pathParts[1] ?? null;
    
    if (!$comprobanteId) {
        sendError('ID de comprobante requerido', 400);
    }
    
    $controller->validarFirma($comprobanteId);
    
} elseif ($method === 'GET' && $pathParts[0] === 'info-certificado') {
    // GET /facturacion-electronica/info-certificado
    $controller->infoCertificado();
    
} elseif ($method === 'GET' && $pathParts[0] === 'descargar-xml') {
    // GET /facturacion-electronica/descargar-xml/{comprobante_id}?tipo=original|firmado
    $comprobanteId = $pathParts[1] ?? null;
    $tipo = $_GET['tipo'] ?? 'firmado';
    
    if (!$comprobanteId) {
        sendError('ID de comprobante requerido', 400);
    }
    
    $controller->descargarXML($comprobanteId, $tipo);
    
} elseif ($method === 'GET' && $pathParts[0] === 'qr') {
    // GET /facturacion-electronica/qr/{comprobante_id}
    $comprobanteId = $pathParts[1] ?? null;
    
    if (!$comprobanteId) {
        sendError('ID de comprobante requerido', 400);
    }
    
    $controller->obtenerQR($comprobanteId);
    
} elseif ($method === 'GET' && $pathParts[0] === 'logs') {
    // GET /facturacion-electronica/logs/{comprobante_id}
    $comprobanteId = $pathParts[1] ?? null;
    
    if (!$comprobanteId) {
        sendError('ID de comprobante requerido', 400);
    }
    
    $controller->obtenerLogs($comprobanteId);
    
} else {
    sendError('Ruta no encontrada', 404);
}

