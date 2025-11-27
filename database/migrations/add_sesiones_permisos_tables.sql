-- Migración: Agregar tablas para sesiones y permisos granulares
-- Ejecutar solo si las tablas no existen

-- Tabla de sesiones
CREATE TABLE IF NOT EXISTS `sesiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `accion` varchar(50) DEFAULT 'activity',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `fecha_actividad` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_fecha` (`fecha_actividad`),
  CONSTRAINT `fk_sesion_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de bloqueos de sesión
CREATE TABLE IF NOT EXISTS `bloqueos_sesion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `motivo` enum('inactividad','sospechosa','manual') NOT NULL,
  `fecha_bloqueo` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_desbloqueo` datetime DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_activo` (`activo`),
  CONSTRAINT `fk_bloqueo_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de permisos por rol
CREATE TABLE IF NOT EXISTS `permisos_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rol` enum('supervisor','analista','cobrador','admin','gerente') NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `permitido` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rol_modulo_accion` (`rol`, `modulo`, `accion`),
  KEY `idx_rol` (`rol`),
  KEY `idx_modulo` (`modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de permisos personalizados por usuario
CREATE TABLE IF NOT EXISTS `permisos_usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `accion` varchar(50) NOT NULL,
  `permitido` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_modulo_accion` (`usuario_id`, `modulo`, `accion`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_modulo` (`modulo`),
  CONSTRAINT `fk_permiso_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar permisos por defecto para cada rol
-- Admin tiene todos los permisos
INSERT INTO `permisos_roles` (`rol`, `modulo`, `accion`, `permitido`) VALUES
-- Permisos para admin (todos permitidos)
('admin', 'prestamos', 'crear', 1),
('admin', 'prestamos', 'editar', 1),
('admin', 'prestamos', 'eliminar', 1),
('admin', 'prestamos', 'aprobar', 1),
('admin', 'prestamos', 'ver', 1),
('admin', 'clientes', 'crear', 1),
('admin', 'clientes', 'editar', 1),
('admin', 'clientes', 'eliminar', 1),
('admin', 'clientes', 'ver', 1),
('admin', 'pagos', 'crear', 1),
('admin', 'pagos', 'editar', 1),
('admin', 'pagos', 'eliminar', 1),
('admin', 'pagos', 'ver', 1),
('admin', 'reportes', 'ver', 1),
('admin', 'reportes', 'exportar', 1),
('admin', 'usuarios', 'crear', 1),
('admin', 'usuarios', 'editar', 1),
('admin', 'usuarios', 'eliminar', 1),
('admin', 'usuarios', 'ver', 1),
('admin', 'configuracion', 'editar', 1),
('admin', 'configuracion', 'ver', 1),

-- Permisos para supervisor
('supervisor', 'prestamos', 'crear', 1),
('supervisor', 'prestamos', 'editar', 1),
('supervisor', 'prestamos', 'aprobar', 1),
('supervisor', 'prestamos', 'ver', 1),
('supervisor', 'clientes', 'crear', 1),
('supervisor', 'clientes', 'editar', 1),
('supervisor', 'clientes', 'ver', 1),
('supervisor', 'pagos', 'crear', 1),
('supervisor', 'pagos', 'ver', 1),
('supervisor', 'rutas', 'crear', 1),
('supervisor', 'rutas', 'editar', 1),
('supervisor', 'rutas', 'asignar', 1),
('supervisor', 'rutas', 'ver', 1),
('supervisor', 'reportes', 'ver', 1),
('supervisor', 'reportes', 'exportar', 1),

-- Permisos para analista
('analista', 'prestamos', 'crear', 1),
('analista', 'prestamos', 'editar', 1),
('analista', 'prestamos', 'ver', 1),
('analista', 'clientes', 'crear', 1),
('analista', 'clientes', 'editar', 1),
('analista', 'clientes', 'ver', 1),
('analista', 'reportes', 'ver', 1),

-- Permisos para cobrador
('cobrador', 'pagos', 'crear', 1),
('cobrador', 'pagos', 'ver', 1),
('cobrador', 'rutas', 'ver', 1),
('cobrador', 'clientes', 'ver', 1),
('cobrador', 'prestamos', 'ver', 1)
ON DUPLICATE KEY UPDATE `permitido` = VALUES(`permitido`);

-- Agregar configuración de tiempo de inactividad
INSERT INTO `configuracion_sistema` (`clave`, `valor`, `tipo`, `descripcion`) VALUES
('sesion_tiempo_inactividad', '30', 'number', 'Tiempo en minutos para considerar sesión inactiva')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);

