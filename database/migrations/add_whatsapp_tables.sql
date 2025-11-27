-- ============================================
-- Tablas para WhatsApp CRM
-- ============================================

CREATE TABLE IF NOT EXISTS `whatsapp_historial` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` varchar(20) NOT NULL,
  `mensaje` text NOT NULL,
  `estado` enum('enviado','recibido','error') NOT NULL DEFAULT 'enviado',
  `tipo` enum('enviado','recibido','notificacion_pago','recordatorio') DEFAULT 'enviado',
  `respuesta` json DEFAULT NULL,
  `fecha_envio` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cliente_id` int(11) DEFAULT NULL,
  `prestamo_id` int(11) DEFAULT NULL,
  `pago_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_numero` (`numero`),
  KEY `idx_fecha` (`fecha_envio`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_prestamo` (`prestamo_id`),
  KEY `idx_pago` (`pago_id`),
  CONSTRAINT `fk_whatsapp_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_whatsapp_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_whatsapp_pago` FOREIGN KEY (`pago_id`) REFERENCES `pagos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de historial de emails
CREATE TABLE IF NOT EXISTS `email_historial` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `destinatario` varchar(255) NOT NULL,
  `asunto` varchar(255) NOT NULL,
  `estado` enum('enviado','error') NOT NULL DEFAULT 'enviado',
  `error` text DEFAULT NULL,
  `fecha_envio` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_destinatario` (`destinatario`),
  KEY `idx_fecha` (`fecha_envio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar campos de configuración de WhatsApp si no existen
INSERT IGNORE INTO `configuracion_sistema` (`clave`, `valor`, `descripcion`, `tipo`) VALUES
('whatsapp_api_url', 'https://graph.facebook.com/v18.0', 'URL de la API de WhatsApp Business', 'text'),
('whatsapp_api_token', '', 'Token de acceso de WhatsApp Business API', 'password'),
('whatsapp_phone_number_id', '', 'ID del número de teléfono de WhatsApp Business', 'text'),
('whatsapp_webhook_token', 'mi_token_secreto', 'Token de verificación del webhook', 'password'),
('whatsapp_notificaciones_activas', '1', 'Activar notificaciones automáticas por WhatsApp', 'boolean'),
('whatsapp_recordatorios_activos', '1', 'Activar recordatorios automáticos por WhatsApp', 'boolean'),
('email_notificaciones_activas', '1', 'Activar notificaciones automáticas por Email', 'boolean'),
('smtp_host', 'smtp.gmail.com', 'Servidor SMTP', 'text'),
('smtp_port', '587', 'Puerto SMTP', 'text'),
('smtp_user', '', 'Usuario SMTP', 'text'),
('smtp_pass', '', 'Contraseña SMTP', 'password'),
('email_from', 'noreply@imaxprestamos.com', 'Email remitente', 'text'),
('email_from_name', 'ImaxPrestamos', 'Nombre remitente', 'text');

