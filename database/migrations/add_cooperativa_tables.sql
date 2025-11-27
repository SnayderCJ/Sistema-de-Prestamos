-- Migración: Tablas para Sistema de Cooperativa
-- Fecha: Diciembre 2024

-- Tabla de Cooperativas
CREATE TABLE IF NOT EXISTS `cooperativas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(200) NOT NULL COMMENT 'Nombre de la cooperativa',
  `rnc` VARCHAR(11) NULL COMMENT 'RNC de la cooperativa',
  `direccion` TEXT NULL COMMENT 'Dirección',
  `telefono` VARCHAR(20) NULL COMMENT 'Teléfono',
  `email` VARCHAR(100) NULL COMMENT 'Email',
  `fecha_constitucion` DATE NULL COMMENT 'Fecha de constitución',
  `activa` TINYINT(1) DEFAULT 1 COMMENT 'Cooperativa activa',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activa` (`activa`),
  KEY `idx_rnc` (`rnc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cooperativas';

-- Tabla de Socios
CREATE TABLE IF NOT EXISTS `socios` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `cooperativa_id` INT(11) NOT NULL COMMENT 'ID de la cooperativa',
  `cliente_id` INT(11) NULL COMMENT 'ID del cliente (si existe)',
  `cedula` VARCHAR(11) NOT NULL COMMENT 'Cédula del socio',
  `nombre` VARCHAR(100) NOT NULL COMMENT 'Nombre del socio',
  `apellido` VARCHAR(100) NOT NULL COMMENT 'Apellido del socio',
  `telefono` VARCHAR(20) NULL COMMENT 'Teléfono',
  `email` VARCHAR(100) NULL COMMENT 'Email',
  `direccion` TEXT NULL COMMENT 'Dirección',
  `fecha_ingreso` DATE NOT NULL COMMENT 'Fecha de ingreso como socio',
  `activo` TINYINT(1) DEFAULT 1 COMMENT 'Socio activo',
  `porcentaje_utilidad` DECIMAL(5,2) NULL COMMENT 'Porcentaje de utilidad asignado (opcional)',
  `observaciones` TEXT NULL COMMENT 'Observaciones',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cooperativa_cedula` (`cooperativa_id`, `cedula`),
  KEY `idx_cooperativa` (`cooperativa_id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_activo` (`activo`),
  CONSTRAINT `fk_socio_cooperativa` FOREIGN KEY (`cooperativa_id`) REFERENCES `cooperativas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_socio_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Socios de cooperativas';

-- Tabla de Apartaciones (Aportes de Socios)
CREATE TABLE IF NOT EXISTS `apartaciones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `socio_id` INT(11) NOT NULL COMMENT 'ID del socio',
  `cooperativa_id` INT(11) NOT NULL COMMENT 'ID de la cooperativa',
  `fecha_apartacion` DATE NOT NULL COMMENT 'Fecha de la apartación',
  `monto` DECIMAL(15,2) NOT NULL COMMENT 'Monto de la apartación',
  `tipo_apartacion` ENUM('inicial', 'adicional', 'mensual', 'extraordinaria') DEFAULT 'adicional' COMMENT 'Tipo de apartación',
  `metodo_pago` VARCHAR(50) NULL COMMENT 'Método de pago (efectivo, transferencia, cheque)',
  `numero_comprobante` VARCHAR(50) NULL COMMENT 'Número de comprobante',
  `observaciones` TEXT NULL COMMENT 'Observaciones',
  `registrado_por` INT(11) NULL COMMENT 'Usuario que registró',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_socio` (`socio_id`),
  KEY `idx_cooperativa` (`cooperativa_id`),
  KEY `idx_fecha` (`fecha_apartacion`),
  KEY `idx_tipo` (`tipo_apartacion`),
  CONSTRAINT `fk_apartacion_socio` FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_apartacion_cooperativa` FOREIGN KEY (`cooperativa_id`) REFERENCES `cooperativas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_apartacion_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Apartaciones (aportes) de socios';

-- Tabla de Distribución de Utilidades
CREATE TABLE IF NOT EXISTS `distribucion_utilidades` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `cooperativa_id` INT(11) NOT NULL COMMENT 'ID de la cooperativa',
  `periodo` VARCHAR(7) NOT NULL COMMENT 'Período (YYYY-MM)',
  `anio` INT(4) NOT NULL COMMENT 'Año',
  `mes` INT(2) NULL COMMENT 'Mes (si es mensual)',
  `monto_total_utilidad` DECIMAL(15,2) NOT NULL COMMENT 'Monto total de utilidad a distribuir',
  `monto_distribuido` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Monto ya distribuido',
  `metodo_distribucion` ENUM('igual', 'porcentaje', 'por_apartaciones', 'mixto') DEFAULT 'por_apartaciones' COMMENT 'Método de distribución',
  `estado` ENUM('pendiente', 'calculado', 'aprobado', 'distribuido', 'cancelado') DEFAULT 'pendiente' COMMENT 'Estado de la distribución',
  `fecha_aprobacion` DATETIME NULL COMMENT 'Fecha de aprobación',
  `aprobado_por` INT(11) NULL COMMENT 'Usuario que aprobó',
  `observaciones` TEXT NULL COMMENT 'Observaciones',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cooperativa_periodo` (`cooperativa_id`, `periodo`),
  KEY `idx_cooperativa` (`cooperativa_id`),
  KEY `idx_periodo` (`periodo`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_distribucion_cooperativa` FOREIGN KEY (`cooperativa_id`) REFERENCES `cooperativas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_distribucion_aprobador` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Distribución de utilidades';

-- Tabla de Detalle de Distribución de Utilidades por Socio
CREATE TABLE IF NOT EXISTS `distribucion_utilidades_detalle` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `distribucion_id` INT(11) NOT NULL COMMENT 'ID de la distribución',
  `socio_id` INT(11) NOT NULL COMMENT 'ID del socio',
  `monto_utilidad` DECIMAL(15,2) NOT NULL COMMENT 'Monto de utilidad asignado',
  `porcentaje_asignado` DECIMAL(5,2) NULL COMMENT 'Porcentaje asignado',
  `monto_apartaciones_periodo` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Monto de apartaciones en el período',
  `metodo_calculo` VARCHAR(50) NULL COMMENT 'Método de cálculo usado',
  `fecha_pago` DATE NULL COMMENT 'Fecha de pago',
  `pagado` TINYINT(1) DEFAULT 0 COMMENT 'Indica si ya fue pagado',
  `comprobante_pago` VARCHAR(50) NULL COMMENT 'Número de comprobante de pago',
  `observaciones` TEXT NULL COMMENT 'Observaciones',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_distribucion` (`distribucion_id`),
  KEY `idx_socio` (`socio_id`),
  KEY `idx_pagado` (`pagado`),
  CONSTRAINT `fk_detalle_distribucion` FOREIGN KEY (`distribucion_id`) REFERENCES `distribucion_utilidades` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_detalle_socio` FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalle de distribución de utilidades por socio';

-- Tabla de Resumen de Apartaciones por Socio
CREATE TABLE IF NOT EXISTS `socios_apartaciones_resumen` (
  `socio_id` INT(11) NOT NULL COMMENT 'ID del socio',
  `cooperativa_id` INT(11) NOT NULL COMMENT 'ID de la cooperativa',
  `total_apartaciones` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total de apartaciones',
  `ultima_apartacion` DATE NULL COMMENT 'Fecha de última apartación',
  `ultima_actualizacion` DATETIME NULL COMMENT 'Última actualización',
  PRIMARY KEY (`socio_id`),
  KEY `idx_cooperativa` (`cooperativa_id`),
  CONSTRAINT `fk_resumen_socio` FOREIGN KEY (`socio_id`) REFERENCES `socios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_resumen_cooperativa` FOREIGN KEY (`cooperativa_id`) REFERENCES `cooperativas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Resumen de apartaciones por socio';

-- Insertar datos iniciales (opcional)
INSERT INTO `cooperativas` (`nombre`, `activa`) VALUES
('Cooperativa Principal', 1)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

