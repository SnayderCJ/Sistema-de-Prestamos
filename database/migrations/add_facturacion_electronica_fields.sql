-- Migración: Agregar campos para facturación electrónica y firma digital
-- Fecha: Diciembre 2024

-- Agregar campos a comprobantes_fiscales para facturación electrónica
ALTER TABLE `comprobantes_fiscales`
ADD COLUMN IF NOT EXISTS `xml_original` LONGTEXT NULL COMMENT 'XML original de la factura electrónica' AFTER `observaciones`,
ADD COLUMN IF NOT EXISTS `xml_firmado` LONGTEXT NULL COMMENT 'XML firmado digitalmente' AFTER `xml_original`,
ADD COLUMN IF NOT EXISTS `qr_code` TEXT NULL COMMENT 'Código QR de la factura' AFTER `xml_firmado`,
ADD COLUMN IF NOT EXISTS `fecha_generacion_xml` DATETIME NULL COMMENT 'Fecha de generación del XML' AFTER `qr_code`,
ADD COLUMN IF NOT EXISTS `estado_electronico` ENUM('pendiente', 'generado', 'firmado', 'enviado', 'aceptado', 'rechazado') DEFAULT 'pendiente' COMMENT 'Estado de la factura electrónica' AFTER `fecha_generacion_xml`,
ADD COLUMN IF NOT EXISTS `dgii_trackid` VARCHAR(100) NULL COMMENT 'Track ID de DGII' AFTER `dgii_respuesta`,
ADD COLUMN IF NOT EXISTS `firma_valida` TINYINT(1) DEFAULT 0 COMMENT 'Indica si la firma digital es válida' AFTER `dgii_trackid`,
ADD COLUMN IF NOT EXISTS `fecha_validacion_firma` DATETIME NULL COMMENT 'Fecha de validación de la firma' AFTER `firma_valida`;

-- Crear índice para búsquedas por estado electrónico
CREATE INDEX IF NOT EXISTS `idx_estado_electronico` ON `comprobantes_fiscales` (`estado_electronico`);
CREATE INDEX IF NOT EXISTS `idx_dgii_trackid` ON `comprobantes_fiscales` (`dgii_trackid`);

-- Tabla para almacenar certificados digitales (opcional, para gestión)
CREATE TABLE IF NOT EXISTS `certificados_digitales` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(200) NOT NULL COMMENT 'Nombre del certificado',
  `rnc` VARCHAR(11) NOT NULL COMMENT 'RNC asociado',
  `ruta_certificado` VARCHAR(500) NOT NULL COMMENT 'Ruta al archivo .p12',
  `fecha_vencimiento` DATE NOT NULL COMMENT 'Fecha de vencimiento del certificado',
  `activo` TINYINT(1) DEFAULT 1 COMMENT 'Certificado activo',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rnc` (`rnc`),
  KEY `idx_activo` (`activo`),
  KEY `idx_vencimiento` (`fecha_vencimiento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Certificados digitales para firma';

-- Tabla para logs de facturación electrónica
CREATE TABLE IF NOT EXISTS `logs_facturacion_electronica` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `comprobante_id` INT(11) NOT NULL,
  `accion` VARCHAR(50) NOT NULL COMMENT 'generar_xml, firmar, enviar_dgii, validar_firma, etc.',
  `estado` ENUM('exitoso', 'error', 'pendiente') DEFAULT 'pendiente',
  `mensaje` TEXT NULL COMMENT 'Mensaje de resultado',
  `datos_adicionales` JSON NULL COMMENT 'Datos adicionales del proceso',
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comprobante` (`comprobante_id`),
  KEY `idx_fecha` (`fecha`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_logs_comprobante` FOREIGN KEY (`comprobante_id`) REFERENCES `comprobantes_fiscales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs de procesos de facturación electrónica';

-- Agregar configuración del sistema para facturación electrónica
INSERT INTO `configuracion_sistema` (`clave`, `valor`, `descripcion`, `tipo`) VALUES
('certificado_digital_path', '', 'Ruta al archivo del certificado digital (.p12)', 'text'),
('certificado_digital_password', '', 'Contraseña del certificado digital', 'password'),
('private_key_path', '', 'Ruta a la clave privada (.pem)', 'text'),
('rnc_empresa', '', 'RNC de la empresa para facturación electrónica', 'text'),
('razon_social', '', 'Razón social de la empresa', 'text'),
('nombre_comercial', '', 'Nombre comercial de la empresa', 'text'),
('direccion_empresa', '', 'Dirección de la empresa', 'text'),
('telefono_empresa', '', 'Teléfono de la empresa', 'text'),
('email_empresa', '', 'Email de la empresa', 'text'),
('dgii_api_url', 'https://www.dgii.gov.do', 'URL de la API de DGII', 'text'),
('dgii_api_key', '', 'API Key de DGII', 'password'),
('facturacion_electronica_activa', '0', 'Activar facturación electrónica (1=si, 0=no)', 'boolean')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);

