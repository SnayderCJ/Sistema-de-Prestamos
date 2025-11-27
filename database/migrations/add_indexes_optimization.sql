-- Migración: Agregar índices para optimización de consultas
-- Ejecutar solo si los índices no existen

-- Índices para tabla prestamos
CREATE INDEX IF NOT EXISTS idx_prestamos_cliente ON prestamos(cliente_id);
CREATE INDEX IF NOT EXISTS idx_prestamos_estado ON prestamos(estado);
CREATE INDEX IF NOT EXISTS idx_prestamos_fecha ON prestamos(fecha_creacion);
CREATE INDEX IF NOT EXISTS idx_prestamos_estado_fecha ON prestamos(estado, fecha_creacion);
CREATE INDEX IF NOT EXISTS idx_prestamos_sucursal ON prestamos(sucursal_id);
CREATE INDEX IF NOT EXISTS idx_prestamos_supervisor ON prestamos(supervisor_aprobador_id);

-- Índices para tabla pagos
CREATE INDEX IF NOT EXISTS idx_pagos_prestamo ON pagos(prestamo_id);
CREATE INDEX IF NOT EXISTS idx_pagos_fecha ON pagos(fecha_pago);
CREATE INDEX IF NOT EXISTS idx_pagos_prestamo_fecha ON pagos(prestamo_id, fecha_pago);
CREATE INDEX IF NOT EXISTS idx_pagos_usuario ON pagos(usuario_id);

-- Índices para tabla clientes
CREATE INDEX IF NOT EXISTS idx_clientes_cedula ON clientes(cedula);
CREATE INDEX IF NOT EXISTS idx_clientes_estado ON clientes(estado_credito);
CREATE INDEX IF NOT EXISTS idx_clientes_fecha ON clientes(fecha_creacion);

-- Índices para tabla cuotas_prestamos
CREATE INDEX IF NOT EXISTS idx_cuotas_prestamo ON cuotas_prestamos(prestamo_id);
CREATE INDEX IF NOT EXISTS idx_cuotas_estado ON cuotas_prestamos(estado);
CREATE INDEX IF NOT EXISTS idx_cuotas_fecha_vencimiento ON cuotas_prestamos(fecha_vencimiento);
CREATE INDEX IF NOT EXISTS idx_cuotas_prestamo_estado ON cuotas_prestamos(prestamo_id, estado);

-- Índices para tabla rutas
CREATE INDEX IF NOT EXISTS idx_rutas_fecha ON rutas(fecha_ruta);
CREATE INDEX IF NOT EXISTS idx_rutas_supervisor ON rutas(supervisor_id);
CREATE INDEX IF NOT EXISTS idx_rutas_cobrador ON rutas(cobrador_id);

