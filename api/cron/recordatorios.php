<?php
/**
 * Script de Cron para Recordatorios Automáticos
 * 
 * Ejecutar diariamente:
 * 0 9 * * * /usr/bin/php /ruta/al/proyecto/api/cron/recordatorios.php
 * 
 * Esto enviará recordatorios a las 9:00 AM todos los días
 */

// Solo permitir ejecución desde línea de comandos o con token
if (php_sapi_name() !== 'cli' && (!isset($_GET['token']) || $_GET['token'] !== 'TOKEN_SECRETO_AQUI')) {
    http_response_code(403);
    die('Acceso denegado');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/RecordatorioService.php';

try {
    $recordatorioService = new RecordatorioService();
    $resultado = $recordatorioService->procesarRecordatorios();
    
    echo "Recordatorios procesados:\n";
    echo "- Enviados: " . $resultado['enviados'] . "\n";
    echo "- Errores: " . $resultado['errores'] . "\n";
    echo "- Total procesados: " . $resultado['total'] . "\n";
    
    // Log en archivo
    $log = date('Y-m-d H:i:s') . " - Recordatorios: " . json_encode($resultado) . "\n";
    file_put_contents(__DIR__ . '/../logs/recordatorios.log', $log, FILE_APPEND);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    error_log("Error en cron de recordatorios: " . $e->getMessage());
    exit(1);
}

