# Migraciones de Base de Datos

Este directorio contiene las migraciones SQL para agregar funcionalidades avanzadas de la Semana 3.

## Migraciones Disponibles

### 1. `add_auditoria_advanced_fields.sql`

Agrega campos avanzados a la tabla `auditoria` para mejorar el sistema de auditoría.

**Campos agregados:**
- `request_uri`: URI de la petición
- `request_method`: Método HTTP (GET, POST, etc.)
- `cambios_significativos`: JSON con cambios importantes detectados
- `metadata`: JSON con metadatos adicionales

**Índices agregados:**
- `idx_auditoria_usuario`: Para búsquedas por usuario
- `idx_auditoria_fecha`: Para búsquedas por fecha
- `idx_auditoria_tabla`: Para búsquedas por tabla
- `idx_auditoria_accion`: Para búsquedas por acción
- `idx_auditoria_usuario_fecha`: Índice compuesto para consultas frecuentes

**Cómo ejecutar:**
```sql
source database/migrations/add_auditoria_advanced_fields.sql;
```

O desde MySQL:
```bash
mysql -u usuario -p nombre_base_datos < database/migrations/add_auditoria_advanced_fields.sql
```

---

### 2. `add_indexes_optimization.sql`

Agrega índices optimizados para mejorar el rendimiento de las consultas más frecuentes.

**Índices agregados:**

#### Tabla `prestamos`:
- `idx_prestamos_cliente`: Búsquedas por cliente
- `idx_prestamos_estado`: Filtros por estado
- `idx_prestamos_fecha`: Ordenamiento por fecha
- `idx_prestamos_estado_fecha`: Consultas combinadas estado + fecha
- `idx_prestamos_sucursal`: Filtros por sucursal
- `idx_prestamos_supervisor`: Filtros por supervisor

#### Tabla `pagos`:
- `idx_pagos_prestamo`: Búsquedas por préstamo
- `idx_pagos_fecha`: Ordenamiento por fecha
- `idx_pagos_prestamo_fecha`: Consultas combinadas
- `idx_pagos_usuario`: Filtros por usuario

#### Tabla `clientes`:
- `idx_clientes_cedula`: Búsquedas por cédula (ya existe, pero se verifica)
- `idx_clientes_estado`: Filtros por estado de crédito
- `idx_clientes_fecha`: Ordenamiento por fecha de creación

#### Tabla `cuotas_prestamos`:
- `idx_cuotas_prestamo`: Búsquedas por préstamo
- `idx_cuotas_estado`: Filtros por estado
- `idx_cuotas_fecha_vencimiento`: Búsquedas por fecha de vencimiento
- `idx_cuotas_prestamo_estado`: Consultas combinadas

#### Tabla `rutas`:
- `idx_rutas_fecha`: Ordenamiento por fecha
- `idx_rutas_supervisor`: Filtros por supervisor
- `idx_rutas_cobrador`: Filtros por cobrador

**Cómo ejecutar:**
```sql
source database/migrations/add_indexes_optimization.sql;
```

O desde MySQL:
```bash
mysql -u usuario -p nombre_base_datos < database/migrations/add_indexes_optimization.sql
```

---

## Verificación de Migraciones

### Verificar campos de auditoría

```sql
DESCRIBE auditoria;
```

Debe mostrar los nuevos campos: `request_uri`, `request_method`, `cambios_significativos`, `metadata`.

### Verificar índices

```sql
SHOW INDEX FROM prestamos;
SHOW INDEX FROM pagos;
SHOW INDEX FROM clientes;
SHOW INDEX FROM cuotas_prestamos;
SHOW INDEX FROM rutas;
SHOW INDEX FROM auditoria;
```

---

## Rollback (Reversión)

### Revertir campos de auditoría

```sql
ALTER TABLE auditoria 
DROP COLUMN IF EXISTS request_uri,
DROP COLUMN IF EXISTS request_method,
DROP COLUMN IF EXISTS cambios_significativos,
DROP COLUMN IF EXISTS metadata;
```

### Revertir índices

**Nota:** Los índices se pueden eliminar individualmente si es necesario, pero generalmente no es necesario revertirlos ya que mejoran el rendimiento.

```sql
-- Ejemplo: Eliminar un índice específico
DROP INDEX IF EXISTS idx_prestamos_cliente ON prestamos;
```

---

## Notas Importantes

1. **Backup:** Siempre hacer backup de la base de datos antes de ejecutar migraciones en producción.

2. **IF NOT EXISTS:** Las migraciones usan `IF NOT EXISTS` para evitar errores si ya se ejecutaron.

3. **Rendimiento:** Los índices mejoran las consultas pero pueden ralentizar ligeramente las inserciones. En la mayoría de los casos, el beneficio supera el costo.

4. **Espacio:** Los índices ocupan espacio adicional en disco. Verificar espacio disponible antes de ejecutar.

5. **Tiempo de Ejecución:** Las migraciones pueden tardar varios minutos en tablas grandes. Ejecutar en horarios de bajo tráfico.

---

## Orden de Ejecución

1. Primero ejecutar `add_auditoria_advanced_fields.sql`
2. Luego ejecutar `add_indexes_optimization.sql`

---

## Problemas Comunes

### Error: "Duplicate key name"

**Causa:** El índice ya existe.

**Solución:** Las migraciones usan `IF NOT EXISTS`, pero si aún así falla, verificar índices existentes y eliminar duplicados.

### Error: "Table doesn't exist"

**Causa:** La tabla no existe en la base de datos.

**Solución:** Ejecutar primero el script de creación de esquema (`schema_prestamos.sql`).

### Error: "Out of disk space"

**Causa:** No hay suficiente espacio para crear índices.

**Solución:** Liberar espacio en disco o aumentar el tamaño de la partición.

---

## Soporte

Para problemas con las migraciones, contactar al equipo de desarrollo o revisar los logs del servidor de base de datos.

