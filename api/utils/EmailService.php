<?php
/**
 * Servicio de Envío de Emails
 */

class EmailService {
    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    private $fromEmail;
    private $fromName;
    
    public function __construct() {
        // Obtener configuración de email
        $db = Database::getInstance();
        
        $stmt = $db->query(
            "SELECT clave, valor FROM configuracion_sistema 
             WHERE clave IN ('smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'email_from', 'email_from_name')"
        );
        
        $configs = $stmt->fetchAll();
        $config = [];
        foreach ($configs as $c) {
            $config[$c['clave']] = $c['valor'];
        }
        
        $this->smtpHost = $config['smtp_host'] ?? 'localhost';
        $this->smtpPort = $config['smtp_port'] ?? 587;
        $this->smtpUser = $config['smtp_user'] ?? '';
        $this->smtpPass = $config['smtp_pass'] ?? '';
        $this->fromEmail = $config['email_from'] ?? 'noreply@erp-prestamos.com';
        $this->fromName = $config['email_from_name'] ?? 'ERP Prestamos';
    }
    
    /**
     * Enviar email
     */
    public function enviar($to, $subject, $body, $isHTML = true) {
        $headers = [];
        $headers[] = "From: {$this->fromName} <{$this->fromEmail}>";
        $headers[] = "Reply-To: {$this->fromEmail}";
        $headers[] = "X-Mailer: PHP/" . phpversion();
        
        if ($isHTML) {
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/html; charset=UTF-8";
        } else {
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
        }
        
        $headersString = implode("\r\n", $headers);
        
        return mail($to, $subject, $body, $headersString);
    }
    
    /**
     * Enviar email de recuperación de contraseña
     */
    public function enviarRecuperacionContrasena($email, $nombre, $token) {
        $resetUrl = BASE_URL . "/reset-password?token=" . $token;
        
        $subject = "Recuperación de Contraseña - ERP Prestamos";
        $body = $this->getTemplateRecuperacion($nombre, $resetUrl);
        
        return $this->enviar($email, $subject, $body);
    }
    
    /**
     * Enviar notificación de préstamo
     */
    public function enviarNotificacionPrestamo($email, $nombre, $prestamo) {
        $subject = "Nuevo Préstamo Aprobado - #{$prestamo['numero_prestamo']}";
        $body = $this->getTemplatePrestamo($nombre, $prestamo);
        
        return $this->enviar($email, $subject, $body);
    }
    
    /**
     * Template de recuperación de contraseña
     */
    private function getTemplateRecuperacion($nombre, $resetUrl) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .button { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Recuperación de Contraseña</h1>
                </div>
                <div class='content'>
                    <p>Hola <strong>{$nombre}</strong>,</p>
                    <p>Has solicitado recuperar tu contraseña. Haz clic en el siguiente enlace para crear una nueva contraseña:</p>
                    <p style='text-align: center;'>
                        <a href='{$resetUrl}' class='button'>Recuperar Contraseña</a>
                    </p>
                    <p>Este enlace expirará en 1 hora.</p>
                    <p>Si no solicitaste este cambio, ignora este email.</p>
                </div>
                <div class='footer'>
                    <p>ERP Prestamos - Sistema de Gestión</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Template de notificación de préstamo
     */
    private function getTemplatePrestamo($nombre, $prestamo) {
        $monto = number_format($prestamo['monto_aprobado'], 2);
        $cuota = number_format($prestamo['cuota_mensual'], 2);
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #28a745; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .info-box { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #28a745; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Préstamo Aprobado</h1>
                </div>
                <div class='content'>
                    <p>Hola <strong>{$nombre}</strong>,</p>
                    <p>Tu préstamo ha sido aprobado exitosamente:</p>
                    <div class='info-box'>
                        <p><strong>Número de Préstamo:</strong> {$prestamo['numero_prestamo']}</p>
                        <p><strong>Monto Aprobado:</strong> RD$ {$monto}</p>
                        <p><strong>Cuota Mensual:</strong> RD$ {$cuota}</p>
                        <p><strong>Plazo:</strong> {$prestamo['plazo_meses']} meses</p>
                    </div>
                    <p>Puedes acceder al sistema para ver más detalles.</p>
                </div>
                <div class='footer'>
                    <p>ERP Prestamos - Sistema de Gestión</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

