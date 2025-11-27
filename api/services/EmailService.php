<?php
/**
 * Servicio de Envío de Emails
 */

class EmailService {
    private $db;
    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    private $fromEmail;
    private $fromName;
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        // Obtener configuración de email
        $stmt = $this->db->query(
            "SELECT clave, valor FROM configuracion_sistema 
             WHERE clave IN ('smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'email_from', 'email_from_name')"
        );
        $config = [];
        foreach ($stmt->fetchAll() as $row) {
            $config[$row['clave']] = $row['valor'];
        }
        
        $this->smtpHost = $config['smtp_host'] ?? 'smtp.gmail.com';
        $this->smtpPort = $config['smtp_port'] ?? 587;
        $this->smtpUser = $config['smtp_user'] ?? '';
        $this->smtpPass = $config['smtp_pass'] ?? '';
        $this->fromEmail = $config['email_from'] ?? 'noreply@imaxprestamos.com';
        $this->fromName = $config['email_from_name'] ?? 'ImaxPrestamos';
    }
    
    /**
     * Enviar email
     */
    public function enviarEmail($to, $subject, $body, $isHTML = true, $attachments = []) {
        if (empty($this->smtpUser) || empty($this->smtpPass)) {
            // Si no hay configuración SMTP, usar mail() nativo
            return $this->enviarEmailNativo($to, $subject, $body, $isHTML);
        }
        
        // Usar PHPMailer si está disponible
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            return $this->enviarEmailSMTP($to, $subject, $body, $isHTML, $attachments);
        }
        
        // Fallback a mail() nativo
        return $this->enviarEmailNativo($to, $subject, $body, $isHTML);
    }
    
    /**
     * Enviar email usando SMTP (PHPMailer)
     */
    private function enviarEmailSMTP($to, $subject, $body, $isHTML, $attachments) {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Configuración del servidor
            $mail->isSMTP();
            $mail->Host = $this->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtpUser;
            $mail->Password = $this->smtpPass;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtpPort;
            $mail->CharSet = 'UTF-8';
            
            // Remitente y destinatario
            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($to);
            
            // Contenido
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            if (!$isHTML) {
                $mail->AltBody = strip_tags($body);
            }
            
            // Adjuntos
            foreach ($attachments as $attachment) {
                $mail->addAttachment($attachment['path'], $attachment['name'] ?? '');
            }
            
            $mail->send();
            
            // Guardar en historial
            $this->guardarHistorial($to, $subject, 'enviado');
            
            return true;
        } catch (Exception $e) {
            error_log('Error enviando email: ' . $mail->ErrorInfo);
            $this->guardarHistorial($to, $subject, 'error', $mail->ErrorInfo);
            throw new Exception('Error al enviar email: ' . $mail->ErrorInfo);
        }
    }
    
    /**
     * Enviar email usando mail() nativo
     */
    private function enviarEmailNativo($to, $subject, $body, $isHTML) {
        $headers = [];
        $headers[] = 'From: ' . $this->fromName . ' <' . $this->fromEmail . '>';
        $headers[] = 'Reply-To: ' . $this->fromEmail;
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        $headers[] = 'MIME-Version: 1.0';
        
        if ($isHTML) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        } else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        }
        
        $result = mail($to, $subject, $body, implode("\r\n", $headers));
        
        if ($result) {
            $this->guardarHistorial($to, $subject, 'enviado');
        } else {
            $this->guardarHistorial($to, $subject, 'error', 'Error al enviar con mail()');
        }
        
        return $result;
    }
    
    /**
     * Guardar en historial de emails
     */
    private function guardarHistorial($to, $subject, $estado, $error = null) {
        try {
            $this->db->query(
                "INSERT INTO email_historial (destinatario, asunto, estado, error, fecha_envio)
                 VALUES (?, ?, ?, ?, NOW())",
                [$to, $subject, $estado, $error]
            );
        } catch (Exception $e) {
            error_log('Error guardando historial de email: ' . $e->getMessage());
        }
    }
    
    /**
     * Enviar notificación de pago
     */
    public function enviarNotificacionPago($pagoId) {
        $stmt = $this->db->query(
            "SELECT p.*, pr.numero_prestamo, c.nombre as cliente_nombre, c.apellido as cliente_apellido, 
                    c.email as cliente_email
             FROM pagos p
             INNER JOIN prestamos pr ON p.prestamo_id = pr.id
             INNER JOIN clientes c ON pr.cliente_id = c.id
             WHERE p.id = ?",
            [$pagoId]
        );
        $pago = $stmt->fetch();
        
        if (!$pago || empty($pago['cliente_email'])) {
            return false;
        }
        
        $subject = 'Confirmación de Pago - Préstamo ' . $pago['numero_prestamo'];
        $body = $this->generarEmailPago($pago);
        
        return $this->enviarEmail($pago['cliente_email'], $subject, $body);
    }
    
    /**
     * Generar HTML del email de pago
     */
    private function generarEmailPago($pago) {
        $fecha = date('d/m/Y', strtotime($pago['fecha_pago']));
        $monto = number_format($pago['monto'] ?? 0, 2);
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; }
        .details { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #4CAF50; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Pago Registrado Exitosamente</h1>
        </div>
        <div class="content">
            <p>Estimado/a <strong>' . htmlspecialchars($pago['cliente_nombre'] . ' ' . $pago['cliente_apellido']) . '</strong>,</p>
            <p>Le confirmamos el registro de su pago:</p>
            <div class="details">
                <h3>Detalles del Pago</h3>
                <p><strong>Préstamo:</strong> ' . htmlspecialchars($pago['numero_prestamo']) . '</p>
                <p><strong>Recibo:</strong> ' . htmlspecialchars($pago['numero_recibo'] ?? '') . '</p>
                <p><strong>Fecha:</strong> ' . $fecha . '</p>
                <p><strong>Monto Total:</strong> RD$ ' . $monto . '</p>
                <p><strong>Capital:</strong> RD$ ' . number_format($pago['capital'] ?? 0, 2) . '</p>
                <p><strong>Interés:</strong> RD$ ' . number_format($pago['interes'] ?? 0, 2) . '</p>';
        
        if (($pago['mora'] ?? 0) > 0) {
            $html .= '<p><strong>Mora:</strong> RD$ ' . number_format($pago['mora'], 2) . '</p>';
        }
        
        $html .= '</div>
            <p>Gracias por su pago puntual.</p>
            <p>Para consultas, no dude en contactarnos.</p>
        </div>
        <div class="footer">
            <p>Este es un mensaje automático, por favor no responder.</p>
            <p>© ' . date('Y') . ' ImaxPrestamos - Todos los derechos reservados</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
}

