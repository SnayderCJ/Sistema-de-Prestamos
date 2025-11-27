-- Migración: Agregar tablas para sistema de webhooks
-- Ejecutar solo si las tablas no existen

-- Tabla de webhooks
CREATE TABLE IF NOT EXISTS `webhooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `url` varchar(500) NOT NULL,
  `eventos` json NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_activo` (`activo`),
  CONSTRAINT `fk_webhook_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de intentos de webhooks
CREATE TABLE IF NOT EXISTS `webhook_intentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webhook_id` int(11) NOT NULL,
  `evento` varchar(100) NOT NULL,
  `exitoso` tinyint(1) NOT NULL DEFAULT 0,
  `respuesta` text DEFAULT NULL,
  `fecha_intento` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_webhook` (`webhook_id`),
  KEY `idx_fecha` (`fecha_intento`),
  KEY `idx_exitoso` (`exitoso`),
  CONSTRAINT `fk_webhook_intento_webhook` FOREIGN KEY (`webhook_id`) REFERENCES `webhooks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

