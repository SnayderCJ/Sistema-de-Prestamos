<?php
/**
 * Servicio de Recordatorios Automáticos
 * Envía recordatorios de pago por WhatsApp y Email
 */

class RecordatorioService {
    private $db;
    private $whatsappService;
    private $emailService;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->whatsappService = new WhatsAppService();
        $this->emailService = new EmailService();
    }
    
    /**
     * Procesar recordatorios automáticos
     * Debe ejecutarse diariamente (cron job)
     */
    public function procesarRecordatorios() {
        // Verificar si los recordatorios están activos
        $stmt = $this->db->query(
            "SELECT valor FROM configuracion_sistema WHERE clave = 'whatsapp_recordatorios_activos'"
        );
        $whatsappActivo = $stmt->fetch()['valor'] ?? '1';
        
        $stmt = $this->db->query(
            "SELECT valor FROM configuracion_sistema WHERE clave = 'email_notificaciones_activas'"
        );
        $emailActivo = $stmt->fetch()['valor'] ?? '1';
        
        if ($whatsappActivo !== '1' && $emailActivo !== '1') {
            return ['enviados' => 0, 'mensaje' => 'Recordatorios desactivados'];
        }
        
        // Obtener préstamos con cuotas vencidas o próximas a vencer
        $prestamos = $this->obtenerPrestamosParaRecordatorio();
        
        $enviados = 0;
        $errores = 0;
        
        foreach ($prestamos as $prestamo) {
            try {
                $diasVencido = $prestamo['dias_vencido'] ?? 0;
                
                // Enviar por WhatsApp
                if ($whatsappActivo === '1' && !empty($prestamo['cliente_telefono'])) {
                    try {
                        $this->whatsappService->enviarRecordatorioPago($prestamo['id'], $diasVencido);
                        $enviados++;
                    } catch (Exception $e) {
                        error_log('Error enviando WhatsApp recordatorio: ' . $e->getMessage());
                        $errores++;
                    }
                }
                
                // Enviar por Email
                if ($emailActivo === '1' && !empty($prestamo['cliente_email'])) {
                    try {
                        $this->enviarEmailRecordatorio($prestamo, $diasVencido);
                        $enviados++;
                    } catch (Exception $e) {
                        error_log('Error enviando Email recordatorio: ' . $e->getMessage());
                        $errores++;
                    }
                }
                
                // Marcar como recordatorio enviado
                $this->marcarRecordatorioEnviado($prestamo['id']);
                
            } catch (Exception $e) {
                error_log('Error procesando recordatorio para préstamo ' . $prestamo['id'] . ': ' . $e->getMessage());
                $errores++;
            }
        }
        
        return [
            'enviados' => $enviados,
            'errores' => $errores,
            'total' => count($prestamos)
        ];
    }
    
    /**
     * Obtener préstamos que necesitan recordatorio
     */
    private function obtenerPrestamosParaRecordatorio() {
        // Préstamos con cuotas vencidas (más de 1 día)
        // O préstamos con cuotas que vencen en los próximos 3 días
        $stmt = $this->db->query(
            "SELECT DISTINCT p.id, p.numero_prestamo, p.cuota_mensual,
                    c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                    c.telefono as cliente_telefono, c.email as cliente_email,
                    MAX(DATEDIFF(CURDATE(), cp.fecha_vencimiento)) as dias_vencido,
                    MIN(DATEDIFF(cp.fecha_vencimiento, CURDATE())) as dias_para_vencer
             FROM prestamos p
             INNER JOIN clientes c ON p.cliente_id = c.id
             INNER JOIN cuotas_prestamos cp ON p.id = cp.prestamo_id
             WHERE p.estado IN ('activo', 'vigente', 'vencido')
             AND cp.estado != 'pagada'
             AND (
                 DATEDIFF(CURDATE(), cp.fecha_vencimiento) > 1
                 OR (DATEDIFF(cp.fecha_vencimiento, CURDATE()) BETWEEN 0 AND 3)
             )
             AND NOT EXISTS (
                 SELECT 1 FROM recordatorios_enviados re
                 WHERE re.prestamo_id = p.id
                 AND DATE(re.fecha_envio) = CURDATE()
             )
             GROUP BY p.id, p.numero_prestamo, p.cuota_mensual,
                      c.nombre, c.apellido, c.telefono, c.email
             HAVING dias_vencido > 0 OR dias_para_vencer <= 3
             LIMIT 100"
        );
        
        return $stmt->fetchAll();
    }
    
    /**
     * Enviar email de recordatorio (método público para uso externo)
     */
    public function enviarEmailRecordatorio($prestamo, $diasVencido) {
        $cuota = number_format($prestamo['cuota_mensual'] ?? 0, 2);
        
        if ($diasVencido > 0) {
            $subject = 'Recordatorio de Pago Vencido - Préstamo ' . $prestamo['numero_prestamo'];
            $body = $this->generarEmailRecordatorioVencido($prestamo, $diasVencido, $cuota);
        } else {
            $subject = 'Recordatorio de Pago - Préstamo ' . $prestamo['numero_prestamo'];
            $body = $this->generarEmailRecordatorio($prestamo, $cuota);
        }
        
        return $this->emailService->enviarEmail(
            $prestamo['cliente_email'],
            $subject,
            $body
        );
    }
    
    /**
     * Generar HTML del email de recordatorio
     */
    private function generarEmailRecordatorio($prestamo, $cuota) {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2196F3; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; }
        .details { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #2196F3; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📅 Recordatorio de Pago</h1>
        </div>
        <div class="content">
            <p>Estimado/a <strong>' . htmlspecialchars($prestamo['cliente_nombre'] . ' ' . $prestamo['cliente_apellido']) . '</strong>,</p>
            <p>Le recordamos que tiene una cuota pendiente del préstamo #' . htmlspecialchars($prestamo['numero_prestamo']) . '.</p>
            <div class="details">
                <h3>Detalles</h3>
                <p><strong>Cuota:</strong> RD$ ' . $cuota . '</p>
            </div>
            <p>Por favor, realice su pago a tiempo.</p>
            <p>Gracias por su atención.</p>
        </div>
        <div class="footer">
            <p>Este es un mensaje automático, por favor no responder.</p>
            <p>© ' . date('Y') . ' ImaxPrestamos</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Generar HTML del email de recordatorio vencido
     */
    private function generarEmailRecordatorioVencido($prestamo, $diasVencido, $cuota) {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f44336; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; }
        .details { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #f44336; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Recordatorio de Pago Vencido</h1>
        </div>
        <div class="content">
            <p>Estimado/a <strong>' . htmlspecialchars($prestamo['cliente_nombre'] . ' ' . $prestamo['cliente_apellido']) . '</strong>,</p>
            <p>Su préstamo #' . htmlspecialchars($prestamo['numero_prestamo']) . ' tiene una cuota vencida.</p>
            <div class="details">
                <h3>Detalles</h3>
                <p><strong>Días vencido:</strong> ' . $diasVencido . '</p>
                <p><strong>Cuota pendiente:</strong> RD$ ' . $cuota . '</p>
            </div>
            <p><strong>Por favor, realice su pago lo antes posible para evitar cargos adicionales.</strong></p>
            <p>Gracias por su atención.</p>
        </div>
        <div class="footer">
            <p>Este es un mensaje automático, por favor no responder.</p>
            <p>© ' . date('Y') . ' ImaxPrestamos</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Marcar recordatorio como enviado
     */
    private function marcarRecordatorioEnviado($prestamoId) {
        $this->db->query(
            "INSERT INTO recordatorios_enviados (prestamo_id, fecha_envio)
             VALUES (?, NOW())
             ON DUPLICATE KEY UPDATE fecha_envio = NOW()",
            [$prestamoId]
        );
    }
}

