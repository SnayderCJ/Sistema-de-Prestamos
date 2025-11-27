<?php
/**
 * ImaxPrestamos - API REST
 * Sistema de Gestión de Préstamos
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/middleware/rateLimit.php';
require_once __DIR__ . '/utils/response.php';
require_once __DIR__ . '/utils/validators.php';

// Autoloader para controladores
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/controllers/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Obtener la ruta solicitada
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];
$path = str_replace(dirname($script_name), '', parse_url($request_uri, PHP_URL_PATH));
$path = trim($path, '/');
$segments = explode('/', $path);

// Remover 'api' si está presente
if (isset($segments[0]) && $segments[0] === 'api') {
    array_shift($segments);
}

$method = $_SERVER['REQUEST_METHOD'];
$resource = $segments[0] ?? '';
$id = $segments[1] ?? null;

try {
    // Aplicar rate limiting (excepto en rutas públicas de auth)
    if (!($resource === 'auth' && $method === 'POST' && ($id === 'login' || $id === 'forgot-password'))) {
        $rateLimit = new RateLimitMiddleware();
        $rateLimit->check();
    }
    
    // Rutas públicas
    if ($resource === 'auth' && ($method === 'POST' || $method === 'PUT')) {
        require_once __DIR__ . '/routes/auth.php';
        exit;
    }

    // Verificar autenticación para rutas protegidas
    $auth = new AuthMiddleware();
    $user = $auth->authenticate();

    if (!$user) {
        sendError('No autorizado', 401);
        exit;
    }

    // Enrutamiento
    switch ($resource) {
        case 'prestamos':
            require_once __DIR__ . '/routes/prestamos.php';
            break;
        
        case 'clientes':
            require_once __DIR__ . '/routes/clientes.php';
            break;
        
        case 'consultas':
            require_once __DIR__ . '/routes/consultas.php';
            break;
        
        case 'tasas':
            require_once __DIR__ . '/routes/tasas.php';
            break;
        
        case 'rutas':
            require_once __DIR__ . '/routes/rutas.php';
            break;
        
        case 'pagos':
            require_once __DIR__ . '/routes/pagos.php';
            break;
        
        case 'analisis':
            require_once __DIR__ . '/routes/analisis.php';
            break;
        
        case 'dashboard':
            require_once __DIR__ . '/routes/dashboard.php';
            break;
        
        case 'dashboard-avanzado':
            require_once __DIR__ . '/routes/dashboard-avanzado.php';
            break;
        
        case 'usuarios':
            require_once __DIR__ . '/routes/usuarios.php';
            break;
        
        case 'reportes':
            require_once __DIR__ . '/routes/reportes.php';
            break;
        
        case 'auditoria':
            require_once __DIR__ . '/routes/auditoria.php';
            break;
        
        case 'exportacion':
            require_once __DIR__ . '/routes/exportacion.php';
            break;
        
        case 'notificaciones':
            require_once __DIR__ . '/routes/notificaciones.php';
            break;
        
        case 'permisos':
            require_once __DIR__ . '/routes/permisos.php';
            break;
        
        case 'webhooks':
            require_once __DIR__ . '/routes/webhooks.php';
            break;
        
        case 'whatsapp':
            // Webhook de WhatsApp es público
            if ($id === 'webhook') {
                require_once __DIR__ . '/routes/whatsapp.php';
            } else {
                // Otras rutas requieren autenticación
                require_once __DIR__ . '/routes/whatsapp.php';
            }
            break;
        
        case 'recordatorios':
            require_once __DIR__ . '/routes/recordatorios.php';
            break;
        
        case 'backups':
            require_once __DIR__ . '/routes/backups.php';
            break;
        
        case 'configuracion':
            require_once __DIR__ . '/routes/configuracion.php';
            break;
        
        case 'garantes':
            require_once __DIR__ . '/routes/garantes.php';
            break;
        
        case 'contratos':
            require_once __DIR__ . '/routes/contratos.php';
            break;
        
        case 'recibos':
            require_once __DIR__ . '/routes/recibos.php';
            break;
        
        case 'reenganche':
            require_once __DIR__ . '/routes/reenganche.php';
            break;
        
        case 'caja':
            require_once __DIR__ . '/routes/caja.php';
            break;
        
        case 'desembolsos':
            require_once __DIR__ . '/routes/desembolsos.php';
            break;
        
        case 'vehiculos':
            require_once __DIR__ . '/routes/vehiculos.php';
            break;
        
        case 'ordenes-incautacion':
            require_once __DIR__ . '/routes/ordenes-incautacion.php';
            break;
        
        case 'nomina':
            require_once __DIR__ . '/routes/nomina.php';
            break;
        
        case 'bonos-cobradores':
            require_once __DIR__ . '/routes/bonos-cobradores.php';
            break;
        
        case 'cheques-empresariales':
            require_once __DIR__ . '/routes/cheques-empresariales.php';
            break;
        
        case 'hipotecas':
            require_once __DIR__ . '/routes/hipotecas.php';
            break;
        
        case 'estados-cuenta':
            require_once __DIR__ . '/routes/estados-cuenta.php';
            break;
        
        case 'contabilidad':
            require_once __DIR__ . '/routes/contabilidad.php';
            break;
        
        case 'reportes-dgii':
            require_once __DIR__ . '/routes/reportes-dgii.php';
            break;
        
        case 'legal':
            require_once __DIR__ . '/routes/legal.php';
            break;
        
        case 'plazos-atraso':
            require_once __DIR__ . '/routes/plazos-atraso.php';
            break;
        
        case 'empleados':
            require_once __DIR__ . '/routes/empleados.php';
            break;
        
        case 'departamentos':
            require_once __DIR__ . '/routes/departamentos.php';
            break;
        
        case 'financiamientos-vehiculos':
            require_once __DIR__ . '/routes/financiamientos-vehiculos.php';
            break;
        
        case 'importaciones-vehiculos':
            require_once __DIR__ . '/routes/importaciones-vehiculos.php';
            break;
        
        case 'comprobantes-fiscales':
            require_once __DIR__ . '/routes/comprobantes-fiscales.php';
            break;
        
        case 'facturacion-electronica':
            require_once __DIR__ . '/routes/facturacion-electronica.php';
            break;
        
        case 'cooperativas':
            require_once __DIR__ . '/routes/cooperativas.php';
            break;
        
        case 'bancos':
            require_once __DIR__ . '/routes/bancos.php';
            break;
        
        case 'monedas':
            require_once __DIR__ . '/routes/monedas.php';
            break;
        
        case 'impuestos':
            require_once __DIR__ . '/routes/impuestos.php';
            break;
        
        case 'tipos-comprobantes':
            require_once __DIR__ . '/routes/tipos-comprobantes.php';
            break;
        
        case 'ventas':
            require_once __DIR__ . '/routes/ventas.php';
            break;
        
        case 'compras':
            require_once __DIR__ . '/routes/compras.php';
            break;
        
        case 'articulos':
            require_once __DIR__ . '/routes/articulos.php';
            break;
        
        case 'codeudores':
            require_once __DIR__ . '/routes/codeudores.php';
            break;
        
        case 'proveedores':
            require_once __DIR__ . '/routes/proveedores.php';
            break;
        
        case 'categorias-articulos':
            require_once __DIR__ . '/routes/categorias-articulos.php';
            break;
        
        default:
            sendError('Recurso no encontrado', 404);
            break;
    }
} catch (Exception $e) {
    error_log("Error en API: " . $e->getMessage());
    sendError('Error interno del servidor: ' . $e->getMessage(), 500);
}

