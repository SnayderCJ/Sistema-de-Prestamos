<?php
/**
 * Archivo de Ejemplo de Configuración
 * Copiar este archivo a config.php y ajustar los valores
 */

// Configuración de entorno
define('ENVIRONMENT', 'development'); // development | production
define('DEBUG', true);

// Configuración de API
define('API_VERSION', 'v1');
define('JWT_SECRET', 'CAMBIAR_ESTE_SECRETO_EN_PRODUCCION_MUY_SEGURO_123456789');
define('JWT_EXPIRATION', 3600 * 24); // 24 horas

// Configuración de servicios externos
// Data Créditos
define('DATA_CREDITOS_API_URL', 'https://api.datacreditos.com');
define('DATA_CREDITOS_API_KEY', 'TU_API_KEY_AQUI');

// Junta Central Electoral (JCE)
define('JCE_API_URL', 'https://api.jce.gob.do');
define('JCE_API_KEY', 'TU_API_KEY_AQUI');

// Dirección General de Impuestos Internos (DGII)
define('DGII_API_URL', 'https://api.dgii.gov.do');
define('DGII_API_KEY', 'TU_API_KEY_AQUI');

// Configuración de tasas
define('TASA_MORA_DIARIA', 0.1); // 0.1% diario
define('DIAS_GRACIA', 5);

// Configuración de límites
define('MONTO_MINIMO_PRESTAMO', 500);
define('MONTO_MAXIMO_PRESTAMO', 500000);
define('PLAZO_MINIMO_MESES', 1);
define('PLAZO_MAXIMO_MESES', 60);

// Zona horaria
date_default_timezone_set('America/Santo_Domingo');

// Manejo de errores
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}


