-- Migración: Agregar campos avanzados a la tabla auditoria
-- Ejecutar solo si los campos no existen

ALTER TABLE auditoria 
ADD COLUMN IF NOT EXISTS request_uri VARCHAR(500) NULL AFTER user_agent,
ADD COLUMN IF NOT EXISTS request_method VARCHAR(10) NULL AFTER request_uri,
ADD COLUMN IF NOT EXISTS cambios_significativos JSON NULL AFTER datos_nuevos,
ADD COLUMN IF NOT EXISTS metadata JSON NULL AFTER cambios_significativos;

-- Índices para mejorar rendimiento
CREATE INDEX IF NOT EXISTS idx_auditoria_usuario ON auditoria(usuario_id);
CREATE INDEX IF NOT EXISTS idx_auditoria_fecha ON auditoria(fecha);
CREATE INDEX IF NOT EXISTS idx_auditoria_tabla ON auditoria(tabla);
CREATE INDEX IF NOT EXISTS idx_auditoria_accion ON auditoria(accion);
CREATE INDEX IF NOT EXISTS idx_auditoria_usuario_fecha ON auditoria(usuario_id, fecha);

