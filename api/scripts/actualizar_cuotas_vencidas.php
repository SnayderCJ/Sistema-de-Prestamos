<?php
/**
 * ImaxPrestamos - Script para Actualizar Cuotas Vencidas y Calcular Mora
 * Ejecutar diariamente mediante cron
 * 
 * Cron: 0 1 * * * /usr/bin/php /ruta/al/proyecto/api/scripts/actualizar_cuotas_vencidas.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/PrestamoService.php';

$db = Database::getInstance();
$prestamoService = new PrestamoService();

echo "🔄 Iniciando actualización de cuotas vencidas...\n";

try {
    // Actualizar todas las cuotas vencidas
    $prestamoService->actualizarCuotasVencidas();
    
    // Actualizar estado de préstamos
    $db->query(
        "UPDATE prestamos p
         SET p.estado = 'vencido'
         WHERE p.estado = 'vigente'
         AND EXISTS (
             SELECT 1 FROM cuotas_prestamos cp
             WHERE cp.prestamo_id = p.id
             AND cp.estado = 'vencida'
             AND cp.fecha_vencimiento < CURDATE()
         )"
    );
    
    // Contar cuotas actualizadas
    $stmt = $db->query(
        "SELECT COUNT(*) as total FROM cuotas_prestamos 
         WHERE estado = 'vencida' AND fecha_vencimiento < CURDATE()"
    );
    $total = $stmt->fetch()['total'];
    
    echo "✅ Actualización completada\n";
    echo "📊 Cuotas vencidas: $total\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    error_log("Error actualizando cuotas vencidas: " . $e->getMessage());
    exit(1);
}

