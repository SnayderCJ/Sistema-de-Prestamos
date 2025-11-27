<?php
/**
 * Script para enviar alertas de préstamos vencidos
 * Ejecutar diariamente mediante cron job
 * 
 * Ejemplo cron: 0 9 * * * /usr/bin/php /ruta/al/script/enviar_alertas_prestamos_vencidos.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/NotificacionService.php';

$notificacionService = new NotificacionService();
$db = Database::getInstance();

// Obtener préstamos vencidos que aún no han sido notificados hoy
$stmt = $db->query(
    "SELECT DISTINCT p.id, p.numero_prestamo, p.supervisor_aprobador_id, p.usuario_creador_id
     FROM prestamos p
     LEFT JOIN notificaciones n ON n.usuario_id IN (p.supervisor_aprobador_id, p.usuario_creador_id)
     AND n.datos LIKE CONCAT('%\"prestamo_id\":', p.id, '%')
     AND n.tipo = 'warning'
     AND DATE(n.fecha_creacion) = CURDATE()
     WHERE p.estado = 'vencido'
     AND (n.id IS NULL OR n.id = 0)
     AND (p.supervisor_aprobador_id IS NOT NULL OR p.usuario_creador_id IS NOT NULL)"
);

$prestamosVencidos = $stmt->fetchAll();

$notificacionesEnviadas = 0;

foreach ($prestamosVencidos as $prestamo) {
    try {
        // Enviar a supervisor si existe
        if ($prestamo['supervisor_aprobador_id']) {
            $notificacionService->alertarPrestamoVencido($prestamo['id']);
            $notificacionesEnviadas++;
        }
        
        // También enviar al creador si es diferente
        if ($prestamo['usuario_creador_id'] && 
            $prestamo['usuario_creador_id'] != $prestamo['supervisor_aprobador_id']) {
            $notificacionService->alertarPrestamoVencido($prestamo['id']);
            $notificacionesEnviadas++;
        }
    } catch (Exception $e) {
        error_log("Error enviando alerta para préstamo {$prestamo['id']}: " . $e->getMessage());
    }
}

echo "Alertas enviadas: $notificacionesEnviadas\n";

