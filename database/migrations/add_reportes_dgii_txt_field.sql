-- Agregar campo archivo_txt a la tabla reportes_dgii

ALTER TABLE `reportes_dgii` 
ADD COLUMN IF NOT EXISTS `archivo_txt` VARCHAR(512) NULL AFTER `archivo_xml`,
ADD COLUMN IF NOT EXISTS `fecha_generacion` DATETIME NULL AFTER `fecha_envio`;

-- Si la tabla no existe, crearla
CREATE TABLE IF NOT EXISTS `reportes_dgii` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tipo_reporte` VARCHAR(10) NOT NULL,
  `periodo` VARCHAR(7) NOT NULL,
  `estado` VARCHAR(20) NOT NULL DEFAULT 'pendiente',
  `archivo_xml` TEXT NULL,
  `archivo_txt` VARCHAR(512) NULL,
  `dgii_respuesta` TEXT NULL,
  `fecha_envio` DATETIME NULL,
  `fecha_generacion` DATETIME NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_reporte_periodo` (`tipo_reporte`, `periodo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

