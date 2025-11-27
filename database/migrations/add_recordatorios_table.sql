-- Tabla para controlar recordatorios enviados
CREATE TABLE IF NOT EXISTS `recordatorios_enviados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prestamo_id` int(11) NOT NULL,
  `fecha_envio` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `prestamo_fecha` (`prestamo_id`, `fecha_envio`),
  KEY `idx_prestamo` (`prestamo_id`),
  KEY `idx_fecha` (`fecha_envio`),
  CONSTRAINT `fk_recordatorio_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

