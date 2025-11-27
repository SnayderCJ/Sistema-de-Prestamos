<?php
/**
 * Script para enviar recordatorios de pagos próximos
 * Ejecutar diariamente mediante cron job
 * 
 * Ejemplo cron: 0 8 * * * /usr/bin/php /ruta/al/script/enviar_recordatorios_pagos.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/NotificacionService.php';

$notificacionService = new NotificacionService();
$db = Database::getInstance();

// Obtener préstamos con pagos próximos (3 días antes)
$stmt = $db->query(
    "SELECT DISTINCT p.id, p.numero_prestamo, p.supervisor_aprobador_id
     FROM prestamos p
     INNER JOIN cuotas_prestamos cp ON p.id = cp.prestamo_id
     WHERE cp.estado = 'pendiente'
     AND DATEDIFF(cp.fecha_vencimiento, CURDATE()) = 3
     AND p.supervisor_aprobador_id IS NOT NULL"
);

$prestamos = $stmt->fetchAll();

$recordatoriosEnviados = 0;

foreach ($prestamos as $prestamo) {
    try {
        $notificacionService->recordarPago($prestamo['id'], 3);
        $recordatoriosEnviados++;
    } catch (Exception $e) {
        error_log("Error enviando recordatorio para préstamo {$prestamo['id']}: " . $e->getMessage());
    }
}

echo "Recordatorios enviados: $recordatoriosEnviados\n";

