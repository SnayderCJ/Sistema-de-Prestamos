-- ============================================
-- ImaxPrestamos - Base de Datos
-- Sistema de Gestión de Préstamos
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================
-- Tabla: Usuarios y Roles
-- ============================================

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cedula` varchar(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('supervisor','analista','cobrador','admin','gerente') NOT NULL DEFAULT 'analista',
  `sucursal_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_acceso` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_rol` (`rol`),
  KEY `idx_sucursal` (`sucursal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Sucursales
-- ============================================

CREATE TABLE IF NOT EXISTS `sucursales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Clientes
-- ============================================

CREATE TABLE IF NOT EXISTS `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cedula` varchar(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `telefono_alternativo` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `provincia` varchar(100) DEFAULT NULL,
  `ocupacion` varchar(100) DEFAULT NULL,
  `ingresos_mensuales` decimal(15,2) DEFAULT NULL,
  `referencia_personal_nombre` varchar(100) DEFAULT NULL,
  `referencia_personal_telefono` varchar(20) DEFAULT NULL,
  `referencia_familiar_nombre` varchar(100) DEFAULT NULL,
  `referencia_familiar_telefono` varchar(20) DEFAULT NULL,
  `score_credito` int(11) DEFAULT NULL,
  `estado_credito` enum('activo','bloqueado','en_revision') NOT NULL DEFAULT 'activo',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`),
  KEY `idx_estado_credito` (`estado_credito`),
  KEY `idx_score_credito` (`score_credito`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Garantes
-- ============================================

CREATE TABLE IF NOT EXISTS `garantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prestamo_id` int(11) NOT NULL,
  `cedula` varchar(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `provincia` varchar(100) DEFAULT NULL,
  `ocupacion` varchar(100) DEFAULT NULL,
  `ingresos_mensuales` decimal(15,2) DEFAULT NULL,
  `relacion_cliente` varchar(50) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prestamo` (`prestamo_id`),
  KEY `idx_cedula` (`cedula`),
  CONSTRAINT `fk_garante_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Contratos
-- ============================================

CREATE TABLE IF NOT EXISTS `contratos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prestamo_id` int(11) NOT NULL,
  `numero_contrato` varchar(50) NOT NULL,
  `tipo_contrato` enum('personal','prendario','hipotecario','fiador') NOT NULL DEFAULT 'personal',
  `contenido` text NOT NULL,
  `firmado` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_firma` datetime DEFAULT NULL,
  `firma_cliente` text DEFAULT NULL,
  `firma_garante` text DEFAULT NULL,
  `firma_prestamista` text DEFAULT NULL,
  `archivo_pdf` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_contrato` (`numero_contrato`),
  KEY `idx_prestamo` (`prestamo_id`),
  CONSTRAINT `fk_contrato_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Recibos
-- ============================================

CREATE TABLE IF NOT EXISTS `recibos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pago_id` int(11) NOT NULL,
  `numero_recibo` varchar(50) NOT NULL,
  `contenido` text NOT NULL,
  `impreso` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_impresion` datetime DEFAULT NULL,
  `usuario_impresion_id` int(11) DEFAULT NULL,
  `archivo_pdf` varchar(255) DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_recibo` (`numero_recibo`),
  KEY `idx_pago` (`pago_id`),
  KEY `idx_usuario_impresion` (`usuario_impresion_id`),
  CONSTRAINT `fk_recibo_pago` FOREIGN KEY (`pago_id`) REFERENCES `pagos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_recibo_usuario` FOREIGN KEY (`usuario_impresion_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Caja
-- ============================================

CREATE TABLE IF NOT EXISTS `caja` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sucursal_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_apertura` datetime NOT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `monto_inicial` decimal(15,2) NOT NULL DEFAULT 0.00,
  `monto_final` decimal(15,2) DEFAULT NULL,
  `monto_efectivo` decimal(15,2) DEFAULT 0.00,
  `monto_cheques` decimal(15,2) DEFAULT 0.00,
  `monto_transferencias` decimal(15,2) DEFAULT 0.00,
  `monto_tarjetas` decimal(15,2) DEFAULT 0.00,
  `estado` enum('abierta','cerrada') NOT NULL DEFAULT 'abierta',
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sucursal` (`sucursal_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_fecha` (`fecha_apertura`),
  CONSTRAINT `fk_caja_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_caja_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Desembolsos
-- ============================================

CREATE TABLE IF NOT EXISTS `desembolsos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prestamo_id` int(11) NOT NULL,
  `caja_id` int(11) NOT NULL,
  `tipo_desembolso` enum('efectivo','transferencia','cheque','tarjeta') NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `numero_comprobante` varchar(50) DEFAULT NULL,
  `banco` varchar(100) DEFAULT NULL,
  `numero_cuenta` varchar(50) DEFAULT NULL,
  `fecha_desembolso` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_id` int(11) NOT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_prestamo` (`prestamo_id`),
  KEY `idx_caja` (`caja_id`),
  KEY `idx_usuario` (`usuario_id`),
  CONSTRAINT `fk_desembolso_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_desembolso_caja` FOREIGN KEY (`caja_id`) REFERENCES `caja` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_desembolso_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Inventario de Vehículos
-- ============================================

CREATE TABLE IF NOT EXISTS `inventario_vehiculos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prestamo_id` int(11) DEFAULT NULL,
  `tipo` enum('nuevo','recuperado') NOT NULL,
  `marca` varchar(50) NOT NULL,
  `modelo` varchar(50) NOT NULL,
  `ano` int(11) NOT NULL,
  `color` varchar(30) DEFAULT NULL,
  `numero_chasis` varchar(50) NOT NULL,
  `numero_motor` varchar(50) DEFAULT NULL,
  `numero_placa` varchar(20) DEFAULT NULL,
  `kilometraje` int(11) DEFAULT NULL,
  `valor_comercial` decimal(15,2) DEFAULT NULL,
  `valor_garantia` decimal(15,2) DEFAULT NULL,
  `estado` enum('disponible','en_garantia','recuperado','vendido','incautado') NOT NULL DEFAULT 'disponible',
  `fecha_ingreso` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_recuperacion` datetime DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_chasis` (`numero_chasis`),
  KEY `idx_prestamo` (`prestamo_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_vehiculo_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Órdenes de Incautación
-- ============================================

CREATE TABLE IF NOT EXISTS `ordenes_incautacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prestamo_id` int(11) NOT NULL,
  `vehiculo_id` int(11) DEFAULT NULL,
  `numero_orden` varchar(50) NOT NULL,
  `oficial_actuante` varchar(100) NOT NULL,
  `fecha_orden` date NOT NULL,
  `fecha_ejecucion` date DEFAULT NULL,
  `motivo` text NOT NULL,
  `dias_atraso` int(11) NOT NULL,
  `cargos_legales` decimal(15,2) DEFAULT 0.00,
  `estado` enum('emitida','ejecutada','cancelada') NOT NULL DEFAULT 'emitida',
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_orden` (`numero_orden`),
  KEY `idx_prestamo` (`prestamo_id`),
  KEY `idx_vehiculo` (`vehiculo_id`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_orden_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_orden_vehiculo` FOREIGN KEY (`vehiculo_id`) REFERENCES `inventario_vehiculos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Nómina
-- ============================================

CREATE TABLE IF NOT EXISTS `nomina` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empleado_id` int(11) NOT NULL,
  `periodo` varchar(20) NOT NULL,
  `fecha_pago` date NOT NULL,
  `salario_base` decimal(15,2) NOT NULL,
  `horas_extras` decimal(8,2) DEFAULT 0.00,
  `monto_horas_extras` decimal(15,2) DEFAULT 0.00,
  `bonos` decimal(15,2) DEFAULT 0.00,
  `comisiones` decimal(15,2) DEFAULT 0.00,
  `otros_ingresos` decimal(15,2) DEFAULT 0.00,
  `total_ingresos` decimal(15,2) NOT NULL,
  `afp` decimal(15,2) DEFAULT 0.00,
  `ars` decimal(15,2) DEFAULT 0.00,
  `isr` decimal(15,2) DEFAULT 0.00,
  `otros_descuentos` decimal(15,2) DEFAULT 0.00,
  `total_descuentos` decimal(15,2) DEFAULT 0.00,
  `neto_pagar` decimal(15,2) NOT NULL,
  `estado` enum('pendiente','pagado','cancelado') NOT NULL DEFAULT 'pendiente',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_empleado` (`empleado_id`),
  KEY `idx_periodo` (`periodo`),
  KEY `idx_fecha_pago` (`fecha_pago`),
  CONSTRAINT `fk_nomina_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Bonos a Cobradores
-- ============================================

CREATE TABLE IF NOT EXISTS `bonos_cobradores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cobrador_id` int(11) NOT NULL,
  `periodo` varchar(20) NOT NULL,
  `tipo_bono` enum('por_cobro','por_meta','especial') NOT NULL,
  `monto_cobrado` decimal(15,2) DEFAULT 0.00,
  `meta` decimal(15,2) DEFAULT NULL,
  `porcentaje` decimal(5,2) DEFAULT NULL,
  `monto_bono` decimal(15,2) NOT NULL,
  `fecha_calculo` date NOT NULL,
  `fecha_pago` date DEFAULT NULL,
  `estado` enum('pendiente','pagado','cancelado') NOT NULL DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cobrador` (`cobrador_id`),
  KEY `idx_periodo` (`periodo`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_bono_cobrador` FOREIGN KEY (`cobrador_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Estados de Cuenta (Clientes)
-- ============================================

CREATE TABLE IF NOT EXISTS `estados_cuenta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `banco` varchar(100) NOT NULL,
  `numero_cuenta` varchar(50) NOT NULL,
  `tipo_cuenta` enum('ahorro','corriente') NOT NULL,
  `saldo_promedio` decimal(15,2) DEFAULT NULL,
  `fecha_consulta` date NOT NULL,
  `archivo_pdf` varchar(255) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cliente` (`cliente_id`),
  CONSTRAINT `fk_estado_cuenta_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Cheques Empresariales
-- ============================================

CREATE TABLE IF NOT EXISTS `cheques_empresariales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prestamo_id` int(11) NOT NULL,
  `numero_cheque` varchar(50) NOT NULL,
  `banco` varchar(100) NOT NULL,
  `numero_cuenta` varchar(50) NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `fecha_cobro` date DEFAULT NULL,
  `estado` enum('pendiente','cobrado','rechazado','cancelado') NOT NULL DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_cheque` (`numero_cheque`),
  KEY `idx_prestamo` (`prestamo_id`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_cheque_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Hipotecas
-- ============================================

CREATE TABLE IF NOT EXISTS `hipotecas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prestamo_id` int(11) NOT NULL,
  `tipo_propiedad` enum('casa','apartamento','terreno','local_comercial') NOT NULL,
  `direccion` text NOT NULL,
  `numero_catastral` varchar(50) DEFAULT NULL,
  `area_metros` decimal(10,2) DEFAULT NULL,
  `valor_avaluo` decimal(15,2) NOT NULL,
  `valor_garantia` decimal(15,2) NOT NULL,
  `fecha_avaluo` date DEFAULT NULL,
  `avaluador` varchar(100) DEFAULT NULL,
  `numero_escritura` varchar(50) DEFAULT NULL,
  `fecha_escritura` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_prestamo` (`prestamo_id`),
  CONSTRAINT `fk_hipoteca_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Departamentos
-- ============================================

CREATE TABLE IF NOT EXISTS `departamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Empleados (Extensión de Usuarios)
-- ============================================

CREATE TABLE IF NOT EXISTS `empleados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `departamento_id` int(11) DEFAULT NULL,
  `cargo` varchar(100) NOT NULL,
  `fecha_ingreso` date NOT NULL,
  `fecha_salida` date DEFAULT NULL,
  `salario_base` decimal(15,2) NOT NULL,
  `tipo_contrato` enum('indefinido','temporal','por_proyecto') NOT NULL DEFAULT 'indefinido',
  `horas_semanales` int(11) DEFAULT 40,
  `afp_numero` varchar(20) DEFAULT NULL,
  `ars_numero` varchar(20) DEFAULT NULL,
  `tss_numero` varchar(20) DEFAULT NULL,
  `estado` enum('activo','licencia','suspendido','inactivo') NOT NULL DEFAULT 'activo',
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_id` (`usuario_id`),
  KEY `idx_departamento` (`departamento_id`),
  CONSTRAINT `fk_empleado_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_empleado_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Asientos Contables
-- ============================================

CREATE TABLE IF NOT EXISTS `asientos_contables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_asiento` varchar(50) NOT NULL,
  `fecha` date NOT NULL,
  `tipo` enum('diario','ajuste','cierre') NOT NULL DEFAULT 'diario',
  `concepto` text NOT NULL,
  `debe` decimal(15,2) NOT NULL DEFAULT 0.00,
  `haber` decimal(15,2) NOT NULL DEFAULT 0.00,
  `cuenta_contable` varchar(20) NOT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_asiento` (`numero_asiento`),
  KEY `idx_fecha` (`fecha`),
  KEY `idx_cuenta` (`cuenta_contable`),
  KEY `idx_usuario` (`usuario_id`),
  CONSTRAINT `fk_asiento_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Reportes DGII
-- ============================================

CREATE TABLE IF NOT EXISTS `reportes_dgii` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_reporte` enum('607','608','609','anual') NOT NULL,
  `periodo` varchar(20) NOT NULL,
  `fecha_generacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_envio` datetime DEFAULT NULL,
  `estado` enum('pendiente','generado','enviado','rechazado') NOT NULL DEFAULT 'pendiente',
  `archivo_xml` varchar(255) DEFAULT NULL,
  `numero_envio` varchar(50) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tipo` (`tipo_reporte`),
  KEY `idx_periodo` (`periodo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Asientos Legales
-- ============================================

CREATE TABLE IF NOT EXISTS `asientos_legales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prestamo_id` int(11) DEFAULT NULL,
  `tipo_asiento` enum('demanda','embargo','sentencia','ejecucion','otro') NOT NULL,
  `numero_expediente` varchar(50) DEFAULT NULL,
  `tribunal` varchar(100) DEFAULT NULL,
  `juez` varchar(100) DEFAULT NULL,
  `fecha_asiento` date NOT NULL,
  `descripcion` text NOT NULL,
  `monto` decimal(15,2) DEFAULT NULL,
  `estado` enum('pendiente','en_proceso','resuelto','archivado') NOT NULL DEFAULT 'pendiente',
  `archivo_pdf` varchar(255) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prestamo` (`prestamo_id`),
  KEY `idx_tipo` (`tipo_asiento`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_asiento_legal_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Plazos de Atraso y Cargos Legales
-- ============================================

CREATE TABLE IF NOT EXISTS `plazos_atraso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `dias_desde` int(11) NOT NULL,
  `dias_hasta` int(11) NOT NULL,
  `cargo_porcentaje` decimal(5,2) DEFAULT NULL,
  `cargo_fijo` decimal(15,2) DEFAULT NULL,
  `accion` enum('notificacion','llamada','visita','legal') NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_dias` (`dias_desde`, `dias_hasta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Consultas de Cédulas (Historial)
-- ============================================

CREATE TABLE IF NOT EXISTS `consultas_cedulas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cedula` varchar(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo_consulta` enum('data_creditos','jce','dgii','policia') NOT NULL,
  `resultado` json DEFAULT NULL,
  `estado` enum('exitoso','fallido','pendiente') NOT NULL DEFAULT 'pendiente',
  `fecha_consulta` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cedula` (`cedula`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_fecha` (`fecha_consulta`),
  CONSTRAINT `fk_consulta_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Data Créditos (Cache)
-- ============================================

CREATE TABLE IF NOT EXISTS `data_creditos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cedula` varchar(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `deuda_total` decimal(15,2) DEFAULT NULL,
  `cantidad_prestamos_activos` int(11) DEFAULT 0,
  `cantidad_prestamos_vencidos` int(11) DEFAULT 0,
  `ultimo_prestamo_fecha` date DEFAULT NULL,
  `historial_credito` json DEFAULT NULL,
  `fecha_consulta` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cedula` (`cedula`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_score` (`score`),
  CONSTRAINT `fk_data_creditos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Tasas de Interés
-- ============================================

CREATE TABLE IF NOT EXISTS `tasas_interes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo_tasa` enum('fija','variable','mixta') NOT NULL DEFAULT 'fija',
  `tasa_mensual` decimal(8,4) NOT NULL,
  `tasa_anual` decimal(8,4) NOT NULL,
  `monto_minimo` decimal(15,2) DEFAULT NULL,
  `monto_maximo` decimal(15,2) DEFAULT NULL,
  `plazo_minimo` int(11) DEFAULT NULL,
  `plazo_maximo` int(11) DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `idx_activa` (`activa`),
  KEY `idx_fechas` (`fecha_inicio`,`fecha_fin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Préstamos
-- ============================================

CREATE TABLE IF NOT EXISTS `prestamos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_prestamo` varchar(20) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `usuario_creador_id` int(11) NOT NULL,
  `supervisor_aprobador_id` int(11) DEFAULT NULL,
  `tasa_interes_id` int(11) NOT NULL,
  `monto_solicitado` decimal(15,2) NOT NULL,
  `monto_aprobado` decimal(15,2) NOT NULL,
  `tasa_interes_mensual` decimal(8,4) NOT NULL,
  `plazo_meses` int(11) NOT NULL,
  `cuota_mensual` decimal(15,2) NOT NULL,
  `monto_total_pagar` decimal(15,2) NOT NULL,
  `interes_total` decimal(15,2) NOT NULL,
  `fecha_solicitud` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_aprobacion` datetime DEFAULT NULL,
  `fecha_desembolso` datetime DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `estado` enum('pendiente','aprobado','rechazado','desembolsado','vigente','vencido','cancelado','pagado') NOT NULL DEFAULT 'pendiente',
  `motivo_rechazo` text DEFAULT NULL,
  `garantia_tipo` enum('personal','prendaria','hipotecaria','fiador','cheque_empresarial','vehiculo','estado_cuenta') DEFAULT NULL,
  `garantia_descripcion` text DEFAULT NULL,
  `reenganche_de` int(11) DEFAULT NULL,
  `es_reenganche` tinyint(1) NOT NULL DEFAULT 0,
  `tipo_prestamo` enum('personal','empresarial') NOT NULL DEFAULT 'personal',
  `dias_para_legal` int(11) DEFAULT NULL,
  `oficial_actuante` varchar(100) DEFAULT NULL,
  `dias_gracia` int(11) DEFAULT 0,
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_prestamo` (`numero_prestamo`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_sucursal` (`sucursal_id`),
  KEY `idx_usuario_creador` (`usuario_creador_id`),
  KEY `idx_supervisor` (`supervisor_aprobador_id`),
  KEY `idx_tasa` (`tasa_interes_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_vencimiento` (`fecha_vencimiento`),
  CONSTRAINT `fk_prestamo_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_prestamo_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_prestamo_usuario_creador` FOREIGN KEY (`usuario_creador_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_prestamo_supervisor` FOREIGN KEY (`supervisor_aprobador_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_prestamo_tasa` FOREIGN KEY (`tasa_interes_id`) REFERENCES `tasas_interes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Cuotas de Préstamos
-- ============================================

CREATE TABLE IF NOT EXISTS `cuotas_prestamos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prestamo_id` int(11) NOT NULL,
  `numero_cuota` int(11) NOT NULL,
  `monto_cuota` decimal(15,2) NOT NULL,
  `capital` decimal(15,2) NOT NULL,
  `interes` decimal(15,2) NOT NULL,
  `saldo_capital` decimal(15,2) NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `fecha_pago` date DEFAULT NULL,
  `monto_pagado` decimal(15,2) DEFAULT 0.00,
  `mora` decimal(15,2) DEFAULT 0.00,
  `dias_mora` int(11) DEFAULT 0,
  `estado` enum('pendiente','pagada','vencida','parcial') NOT NULL DEFAULT 'pendiente',
  `metodo_pago` enum('efectivo','transferencia','cheque','tarjeta') DEFAULT NULL,
  `numero_comprobante` varchar(50) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prestamo` (`prestamo_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_vencimiento` (`fecha_vencimiento`),
  KEY `idx_numero_cuota` (`prestamo_id`,`numero_cuota`),
  CONSTRAINT `fk_cuota_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Pagos
-- ============================================

CREATE TABLE IF NOT EXISTS `pagos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prestamo_id` int(11) NOT NULL,
  `cuota_id` int(11) DEFAULT NULL,
  `numero_recibo` varchar(20) NOT NULL,
  `monto` decimal(15,2) NOT NULL,
  `capital` decimal(15,2) NOT NULL,
  `interes` decimal(15,2) NOT NULL,
  `mora` decimal(15,2) DEFAULT 0.00,
  `metodo_pago` enum('efectivo','transferencia','cheque','tarjeta') NOT NULL,
  `numero_comprobante` varchar(50) DEFAULT NULL,
  `fecha_pago` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_id` int(11) NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_recibo` (`numero_recibo`),
  KEY `idx_prestamo` (`prestamo_id`),
  KEY `idx_cuota` (`cuota_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_sucursal` (`sucursal_id`),
  KEY `idx_fecha_pago` (`fecha_pago`),
  CONSTRAINT `fk_pago_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pago_cuota` FOREIGN KEY (`cuota_id`) REFERENCES `cuotas_prestamos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pago_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_pago_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Rutas de Supervisores
-- ============================================

CREATE TABLE IF NOT EXISTS `rutas_supervisores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supervisor_id` int(11) NOT NULL,
  `cobrador_id` int(11) DEFAULT NULL,
  `nombre_ruta` varchar(100) NOT NULL,
  `fecha_ruta` date NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `estado` enum('programada','en_proceso','completada','cancelada') NOT NULL DEFAULT 'programada',
  `observaciones` text DEFAULT NULL,
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_supervisor` (`supervisor_id`),
  KEY `idx_cobrador` (`cobrador_id`),
  KEY `idx_fecha_ruta` (`fecha_ruta`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_ruta_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ruta_cobrador` FOREIGN KEY (`cobrador_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ruta_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Visitas de Ruta
-- ============================================

CREATE TABLE IF NOT EXISTS `visitas_ruta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ruta_id` int(11) NOT NULL,
  `prestamo_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `tipo_visita` enum('cobro','seguimiento','renegociacion','legal') NOT NULL,
  `fecha_visita` datetime NOT NULL,
  `latitud` decimal(10,8) DEFAULT NULL,
  `longitud` decimal(11,8) DEFAULT NULL,
  `resultado` enum('exitoso','no_encontrado','rechazado','reprogramado') DEFAULT NULL,
  `monto_cobrado` decimal(15,2) DEFAULT 0.00,
  `observaciones` text DEFAULT NULL,
  `fotos` json DEFAULT NULL,
  `firma_cliente` text DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ruta` (`ruta_id`),
  KEY `idx_prestamo` (`prestamo_id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_fecha_visita` (`fecha_visita`),
  CONSTRAINT `fk_visita_ruta` FOREIGN KEY (`ruta_id`) REFERENCES `rutas_supervisores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_visita_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_visita_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Análisis de Préstamos
-- ============================================

CREATE TABLE IF NOT EXISTS `analisis_prestamos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prestamo_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `analista_id` int(11) NOT NULL,
  `score_credito` int(11) DEFAULT NULL,
  `capacidad_pago` decimal(15,2) DEFAULT NULL,
  `ratio_deuda_ingresos` decimal(8,4) DEFAULT NULL,
  `historial_pagos` json DEFAULT NULL,
  `recomendacion` enum('aprobado','rechazado','condicionado') DEFAULT NULL,
  `comentarios` text DEFAULT NULL,
  `fecha_analisis` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prestamo` (`prestamo_id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_analista` (`analista_id`),
  CONSTRAINT `fk_analisis_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_analisis_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_analisis_analista` FOREIGN KEY (`analista_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Configuración del Sistema
-- ============================================

CREATE TABLE IF NOT EXISTS `configuracion_sistema` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `tipo` enum('string','number','boolean','json') NOT NULL DEFAULT 'string',
  `descripcion` text DEFAULT NULL,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Datos Iniciales
-- ============================================

-- Insertar tasas de interés por defecto
INSERT INTO `tasas_interes` (`codigo`, `nombre`, `tipo_tasa`, `tasa_mensual`, `tasa_anual`, `monto_minimo`, `monto_maximo`, `plazo_minimo`, `plazo_maximo`, `activa`, `fecha_inicio`) VALUES
('TASA_001', 'Tasa Personal Estándar', 'fija', 3.50, 42.00, 1000.00, 50000.00, 3, 24, 1, CURDATE()),
('TASA_002', 'Tasa Personal Premium', 'fija', 2.75, 33.00, 50000.00, 200000.00, 6, 36, 1, CURDATE()),
('TASA_003', 'Tasa Prendaria', 'fija', 2.50, 30.00, 10000.00, 100000.00, 6, 24, 1, CURDATE()),
('TASA_004', 'Tasa Hipotecaria', 'fija', 1.75, 21.00, 50000.00, 500000.00, 12, 60, 1, CURDATE()),
('TASA_005', 'Tasa Microcrédito', 'fija', 4.00, 48.00, 500.00, 10000.00, 1, 12, 1, CURDATE());

-- ============================================
-- Tabla: Tokens de Recuperación de Contraseña
-- ============================================

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_password_reset_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Auditoría de Acciones
-- ============================================

CREATE TABLE IF NOT EXISTS `auditoria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `accion` varchar(100) NOT NULL,
  `tabla` varchar(50) DEFAULT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `datos_anteriores` json DEFAULT NULL,
  `datos_nuevos` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_accion` (`accion`),
  KEY `idx_tabla` (`tabla`),
  KEY `idx_fecha` (`fecha`),
  CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Refresh Tokens
-- ============================================

CREATE TABLE IF NOT EXISTS `refresh_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `revoked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_refresh_token_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuración del sistema
INSERT INTO `configuracion_sistema` (`clave`, `valor`, `tipo`, `descripcion`) VALUES
('tasa_mora_diaria', '0.1', 'number', 'Tasa de mora diaria en porcentaje'),
('dias_gracia', '5', 'number', 'Días de gracia antes de aplicar mora'),
('monto_minimo_prestamo', '500', 'number', 'Monto mínimo para préstamos'),
('monto_maximo_prestamo', '500000', 'number', 'Monto máximo para préstamos'),
('api_data_creditos_key', '', 'string', 'API Key para consulta de data créditos'),
('api_jce_key', '', 'string', 'API Key para consulta JCE'),
('api_dgii_key', '', 'string', 'API Key para consulta DGII'),
('password_reset_expiry', '3600', 'number', 'Tiempo de expiración de token de recuperación (segundos)'),
('refresh_token_expiry', '604800', 'number', 'Tiempo de expiración de refresh token (7 días)'),
('rate_limit_requests', '100', 'number', 'Número de requests por hora por IP'),
('auditoria_activa', '1', 'boolean', 'Activar/desactivar auditoría');

-- Insertar usuario admin por defecto (password: admin123)
INSERT INTO `usuarios` (`cedula`, `nombre`, `apellido`, `email`, `password`, `rol`) VALUES
('00000000000', 'Admin', 'Sistema', 'admin@sistema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insertar sucursal por defecto
INSERT INTO `sucursales` (`codigo`, `nombre`, `direccion`, `telefono`) VALUES
('SUC001', 'Sucursal Principal', 'Calle Principal #123, Santo Domingo', '809-555-0000');

-- Insertar departamentos
INSERT INTO `departamentos` (`codigo`, `nombre`, `descripcion`) VALUES
('CONT', 'Contabilidad', 'Departamento de contabilidad y reportes fiscales'),
('RRHH', 'Recursos Humanos', 'Gestión de personal y nómina'),
('LEGAL', 'Legal', 'Departamento legal y asientos legales'),
('COBRANZA', 'Cobranza', 'Gestión de cobranza y recuperación'),
('CREDITO', 'Crédito', 'Análisis y aprobación de créditos');

-- Insertar plazos de atraso por defecto
INSERT INTO `plazos_atraso` (`nombre`, `dias_desde`, `dias_hasta`, `cargo_porcentaje`, `accion`) VALUES
('Notificación Inicial', 1, 5, 0.1, 'notificacion'),
('Llamada de Recordatorio', 6, 10, 0.2, 'llamada'),
('Visita de Cobro', 11, 20, 0.5, 'visita'),
('Aviso Legal', 21, 30, 1.0, 'legal'),
('Proceso Legal', 31, 999, 2.0, 'legal');

-- ============================================
-- Tabla: Bancos
-- ============================================

CREATE TABLE IF NOT EXISTS `bancos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `codigo_swift` varchar(11) DEFAULT NULL,
  `codigo_ach` varchar(10) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Monedas
-- ============================================

CREATE TABLE IF NOT EXISTS `monedas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(3) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `simbolo` varchar(10) DEFAULT NULL,
  `tasa_cambio` decimal(15,4) DEFAULT 1.0000,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_actualizacion` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Tipos de Comprobantes Fiscales
-- ============================================

CREATE TABLE IF NOT EXISTS `tipos_comprobantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `serie` varchar(10) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Comprobantes Fiscales (NCF)
-- ============================================

CREATE TABLE IF NOT EXISTS `comprobantes_fiscales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_ncf` varchar(20) NOT NULL,
  `tipo_comprobante_id` int(11) NOT NULL,
  `prestamo_id` int(11) DEFAULT NULL,
  `pago_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) NOT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `monto_subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `monto_impuestos` decimal(15,2) DEFAULT 0.00,
  `monto_total` decimal(15,2) NOT NULL,
  `estado` enum('pendiente','emitido','anulado','vencido') NOT NULL DEFAULT 'pendiente',
  `rnc_cliente` varchar(11) DEFAULT NULL,
  `razon_social` varchar(200) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_ncf` (`numero_ncf`),
  KEY `idx_tipo` (`tipo_comprobante_id`),
  KEY `idx_prestamo` (`prestamo_id`),
  KEY `idx_pago` (`pago_id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_fecha` (`fecha_emision`),
  CONSTRAINT `fk_ncf_tipo` FOREIGN KEY (`tipo_comprobante_id`) REFERENCES `tipos_comprobantes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ncf_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ncf_pago` FOREIGN KEY (`pago_id`) REFERENCES `pagos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ncf_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Impuestos
-- ============================================

CREATE TABLE IF NOT EXISTS `impuestos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('porcentaje','fijo') NOT NULL DEFAULT 'porcentaje',
  `valor` decimal(10,4) NOT NULL,
  `aplica_a` enum('prestamo','pago','importacion','general') NOT NULL DEFAULT 'general',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `descripcion` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Impuestos de Comprobantes Fiscales
-- ============================================

CREATE TABLE IF NOT EXISTS `impuestos_comprobantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comprobante_id` int(11) NOT NULL,
  `impuesto_id` int(11) NOT NULL,
  `base_imponible` decimal(15,2) NOT NULL,
  `monto_impuesto` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_comprobante` (`comprobante_id`),
  KEY `idx_impuesto` (`impuesto_id`),
  CONSTRAINT `fk_imp_comp_comprobante` FOREIGN KEY (`comprobante_id`) REFERENCES `comprobantes_fiscales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_imp_comp_impuesto` FOREIGN KEY (`impuesto_id`) REFERENCES `impuestos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Financiamientos de Vehículos
-- ============================================

CREATE TABLE IF NOT EXISTS `financiamientos_vehiculos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_financiamiento` varchar(20) NOT NULL,
  `prestamo_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) NOT NULL,
  `tipo_financiamiento` enum('propio','externo') NOT NULL,
  `vehiculo_id` int(11) DEFAULT NULL,
  `marca` varchar(50) NOT NULL,
  `modelo` varchar(50) NOT NULL,
  `ano` int(11) NOT NULL,
  `color` varchar(30) DEFAULT NULL,
  `numero_chasis` varchar(50) NOT NULL,
  `numero_motor` varchar(50) DEFAULT NULL,
  `numero_placa` varchar(20) DEFAULT NULL,
  `kilometraje` int(11) DEFAULT NULL,
  `valor_comercial` decimal(15,2) NOT NULL,
  `valor_financiado` decimal(15,2) NOT NULL,
  `monto_inicial` decimal(15,2) DEFAULT 0.00,
  `plazo_meses` int(11) NOT NULL,
  `tasa_interes` decimal(8,4) NOT NULL,
  `cuota_mensual` decimal(15,2) NOT NULL,
  `seguro_afiliado` varchar(100) DEFAULT NULL,
  `numero_poliza_seguro` varchar(50) DEFAULT NULL,
  `fecha_vencimiento_seguro` date DEFAULT NULL,
  `tasador` varchar(100) DEFAULT NULL,
  `fecha_tasacion` date DEFAULT NULL,
  `valor_tasacion` decimal(15,2) DEFAULT NULL,
  `banco_id` int(11) DEFAULT NULL,
  `moneda_id` int(11) NOT NULL DEFAULT 1,
  `fecha_inicio` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('pendiente','aprobado','vigente','vencido','cancelado','pagado') NOT NULL DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_financiamiento` (`numero_financiamiento`),
  UNIQUE KEY `numero_chasis` (`numero_chasis`),
  KEY `idx_prestamo` (`prestamo_id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_vehiculo` (`vehiculo_id`),
  KEY `idx_banco` (`banco_id`),
  KEY `idx_moneda` (`moneda_id`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `fk_financ_prestamo` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_financ_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_financ_vehiculo` FOREIGN KEY (`vehiculo_id`) REFERENCES `inventario_vehiculos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_financ_banco` FOREIGN KEY (`banco_id`) REFERENCES `bancos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_financ_moneda` FOREIGN KEY (`moneda_id`) REFERENCES `monedas` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Importaciones de Vehículos
-- ============================================

CREATE TABLE IF NOT EXISTS `importaciones_vehiculos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_importacion` varchar(20) NOT NULL,
  `vehiculo_id` int(11) DEFAULT NULL,
  `financiamiento_id` int(11) DEFAULT NULL,
  `marca` varchar(50) NOT NULL,
  `modelo` varchar(50) NOT NULL,
  `ano` int(11) NOT NULL,
  `numero_chasis` varchar(50) NOT NULL,
  `numero_motor` varchar(50) DEFAULT NULL,
  `pais_origen` varchar(50) NOT NULL,
  `puerto_entrada` varchar(100) DEFAULT NULL,
  `fecha_importacion` date NOT NULL,
  `valor_factura` decimal(15,2) NOT NULL,
  `moneda_factura_id` int(11) NOT NULL,
  `tasa_cambio` decimal(15,4) DEFAULT 1.0000,
  `valor_fob` decimal(15,2) NOT NULL,
  `flete` decimal(15,2) DEFAULT 0.00,
  `seguro` decimal(15,2) DEFAULT 0.00,
  `cif` decimal(15,2) NOT NULL,
  `arancel` decimal(15,2) DEFAULT 0.00,
  `itbis` decimal(15,2) DEFAULT 0.00,
  `selectivo` decimal(15,2) DEFAULT 0.00,
  `otros_impuestos` decimal(15,2) DEFAULT 0.00,
  `total_impuestos` decimal(15,2) DEFAULT 0.00,
  `valor_total` decimal(15,2) NOT NULL,
  `numero_dai` varchar(50) DEFAULT NULL,
  `fecha_dai` date DEFAULT NULL,
  `agente_aduanero` varchar(100) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_importacion` (`numero_importacion`),
  KEY `idx_vehiculo` (`vehiculo_id`),
  KEY `idx_financiamiento` (`financiamiento_id`),
  KEY `idx_moneda` (`moneda_factura_id`),
  CONSTRAINT `fk_import_vehiculo` FOREIGN KEY (`vehiculo_id`) REFERENCES `inventario_vehiculos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_import_financiamiento` FOREIGN KEY (`financiamiento_id`) REFERENCES `financiamientos_vehiculos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_import_moneda` FOREIGN KEY (`moneda_factura_id`) REFERENCES `monedas` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabla: Impuestos de Importación
-- ============================================

CREATE TABLE IF NOT EXISTS `impuestos_importacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `importacion_id` int(11) NOT NULL,
  `impuesto_id` int(11) NOT NULL,
  `base_imponible` decimal(15,2) NOT NULL,
  `porcentaje` decimal(8,4) DEFAULT NULL,
  `monto_fijo` decimal(15,2) DEFAULT NULL,
  `monto_impuesto` decimal(15,2) NOT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_importacion` (`importacion_id`),
  KEY `idx_impuesto` (`impuesto_id`),
  CONSTRAINT `fk_imp_imp_importacion` FOREIGN KEY (`importacion_id`) REFERENCES `importaciones_vehiculos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_imp_imp_impuesto` FOREIGN KEY (`impuesto_id`) REFERENCES `impuestos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar datos iniciales

-- Bancos principales de RD
INSERT INTO `bancos` (`codigo`, `nombre`, `codigo_swift`, `codigo_ach`) VALUES
('BHD', 'Banco BHD León', 'BHDLDOMM', '001'),
('POP', 'Banco Popular Dominicano', 'BPOPDOMM', '002'),
('BSC', 'Banco de Reservas', 'BRESDOMM', '003'),
('BAN', 'Banco Banesco', 'BANEDOMM', '004'),
('SCO', 'Scotiabank', 'NOSCDOMM', '005');

-- Monedas
INSERT INTO `monedas` (`codigo`, `nombre`, `simbolo`, `tasa_cambio`) VALUES
('DOP', 'Peso Dominicano', 'RD$', 1.0000),
('USD', 'Dólar Estadounidense', 'US$', 56.50),
('EUR', 'Euro', '€', 61.20);

-- Tipos de Comprobantes Fiscales (NCF) según DGII
INSERT INTO `tipos_comprobantes` (`codigo`, `nombre`, `descripcion`, `serie`) VALUES
('01', 'Factura de Crédito Fiscal', 'Factura para contribuyentes', 'B'),
('02', 'Factura de Consumo', 'Factura para consumidores finales', 'B'),
('03', 'Nota de Débito', 'Nota de débito', 'B'),
('04', 'Nota de Crédito', 'Nota de crédito', 'B'),
('11', 'Factura de Compra', 'Factura de compra', 'B'),
('12', 'Factura de Regímenes Especiales', 'Factura especial', 'B'),
('13', 'Factura Gubernamental', 'Factura gubernamental', 'B'),
('14', 'Registro Único de Ingresos', 'RUI', 'B'),
('15', 'Gastos Menores', 'Gastos menores', 'B'),
('16', 'Regímenes Especiales de Pagos a Sujetos Excluidos', 'REPSE', 'B');

-- Impuestos comunes
INSERT INTO `impuestos` (`codigo`, `nombre`, `tipo`, `valor`, `aplica_a`, `descripcion`) VALUES
('ITBIS', 'ITBIS', 'porcentaje', 18.0000, 'general', 'Impuesto sobre Transferencia de Bienes Industrializados y Servicios'),
('ISR', 'Impuesto sobre la Renta', 'porcentaje', 0.0000, 'general', 'Impuesto sobre la Renta (variable)'),
('ARANCEL', 'Arancel de Importación', 'porcentaje', 0.0000, 'importacion', 'Arancel según tipo de vehículo'),
('SELECTIVO', 'Impuesto Selectivo', 'porcentaje', 0.0000, 'importacion', 'Impuesto selectivo al consumo'),
('TSS', 'Tasa de Seguridad Social', 'porcentaje', 0.0000, 'general', 'Tasa de Seguridad Social');

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

