# API de Reportes - Documentación

## Endpoints de Reportes

### 1. Obtener Reporte de Préstamos

**GET** `/api/reportes/prestamos`

Obtiene un reporte de préstamos con filtros opcionales.

#### Parámetros de Query

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `fecha_desde` | string (YYYY-MM-DD) | No | Fecha de inicio del período |
| `fecha_hasta` | string (YYYY-MM-DD) | No | Fecha de fin del período |
| `estado` | string | No | Estado del préstamo (pendiente, aprobado, vigente, vencido, pagado) |
| `sucursal_id` | integer | No | ID de la sucursal |

#### Ejemplo de Respuesta

```json
{
  "success": true,
  "data": {
    "prestamos": [...],
    "resumen": {
      "total_prestamos": 150,
      "total_monto": 5000000.00,
      "total_pagado": 2000000.00,
      "total_pendiente": 3000000.00,
      "total_mora": 50000.00
    },
    "filtros": {...},
    "fecha_generacion": "2024-01-15 10:30:00"
  }
}
```

---

### 2. Obtener Reporte de Cobros

**GET** `/api/reportes/cobros`

Obtiene un reporte de cobros con filtros opcionales.

#### Parámetros de Query

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `fecha_desde` | string (YYYY-MM-DD) | No | Fecha de inicio del período |
| `fecha_hasta` | string (YYYY-MM-DD) | No | Fecha de fin del período |
| `sucursal_id` | integer | No | ID de la sucursal |

#### Ejemplo de Respuesta

```json
{
  "success": true,
  "data": {
    "pagos": [...],
    "resumen": {
      "total_pagos": 200,
      "total_cobros": 1500000.00,
      "total_capital": 1000000.00,
      "total_interes": 400000.00,
      "total_mora": 100000.00
    }
  }
}
```

---

### 3. Obtener Reporte de Mora

**GET** `/api/reportes/mora`

Obtiene un reporte de cuotas vencidas con mora.

#### Parámetros de Query

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `fecha_desde` | string (YYYY-MM-DD) | No | Fecha de inicio del período |
| `fecha_hasta` | string (YYYY-MM-DD) | No | Fecha de fin del período |

#### Ejemplo de Respuesta

```json
{
  "success": true,
  "data": {
    "cuotas_vencidas": [...],
    "resumen": {
      "total_cuotas_vencidas": 50,
      "total_mora": 250000.00,
      "promedio_dias_vencido": 15.5
    }
  }
}
```

---

### 4. Exportar Reporte a PDF

**GET** `/api/reportes/exportar-pdf`

Genera y descarga un reporte en formato PDF.

#### Parámetros de Query

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `tipo` | string | Sí | Tipo de reporte: `prestamos`, `cobros`, `mora` |
| `fecha_desde` | string (YYYY-MM-DD) | No | Fecha de inicio |
| `fecha_hasta` | string (YYYY-MM-DD) | No | Fecha de fin |
| `estado` | string | No | Estado (solo para préstamos) |
| `sucursal_id` | integer | No | ID de sucursal |

#### Ejemplo de Uso

```
GET /api/reportes/exportar-pdf?tipo=prestamos&fecha_desde=2024-01-01&fecha_hasta=2024-01-31
```

**Respuesta:** Archivo PDF descargable

---

### 5. Exportar Reporte a Excel

**GET** `/api/reportes/exportar-excel`

Genera y descarga un reporte en formato Excel (XLSX).

#### Parámetros de Query

Mismos que exportar PDF.

#### Ejemplo de Uso

```
GET /api/reportes/exportar-excel?tipo=cobros&fecha_desde=2024-01-01&fecha_hasta=2024-01-31
```

**Respuesta:** Archivo XLSX descargable (o CSV si PhpSpreadsheet no está instalado)

---

## Endpoints de Dashboard Avanzado

### 6. Obtener Estadísticas Avanzadas

**GET** `/api/dashboard-avanzado/estadisticas-avanzadas`

Obtiene estadísticas avanzadas con gráficos y tendencias.

#### Parámetros de Query

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `fecha_desde` | string (YYYY-MM-DD) | No | Fecha de inicio (default: primer día del mes) |
| `fecha_hasta` | string (YYYY-MM-DD) | No | Fecha de fin (default: hoy) |

**Validaciones:**
- Formato de fecha: YYYY-MM-DD
- `fecha_desde` no puede ser mayor que `fecha_hasta`
- Rango máximo: 2 años (730 días)

#### Ejemplo de Respuesta

```json
{
  "success": true,
  "data": {
    "estadisticas": {
      "total_prestamos": 500,
      "prestamos_activos": {
        "cantidad": 300,
        "monto_total": 10000000.00
      },
      "prestamos_vencidos": {
        "cantidad": 50,
        "monto_total": 2000000.00
      },
      "tasa_recuperacion": 75.5
    },
    "graficos": {
      "prestamos_por_mes": [...],
      "cobros_por_mes": [...],
      "distribucion_estado": [...]
    },
    "top_clientes": [...],
    "tendencias": {
      "prestamos": {
        "mes_actual": {...},
        "mes_anterior": {...},
        "variacion_cantidad": 10.5,
        "variacion_monto": 15.2
      }
    }
  }
}
```

---

## Endpoints de Exportación

### 7. Exportar Préstamos a CSV

**GET** `/api/exportacion/prestamos`

Exporta préstamos a formato CSV.

#### Parámetros de Query

Mismos filtros que reporte de préstamos.

**Respuesta:** Archivo CSV descargable

---

### 8. Exportar Pagos a CSV

**GET** `/api/exportacion/pagos`

Exporta pagos a formato CSV.

#### Parámetros de Query

Mismos filtros que reporte de cobros.

**Respuesta:** Archivo CSV descargable

---

### 9. Exportar Clientes a CSV

**GET** `/api/exportacion/clientes`

Exporta clientes a formato CSV.

#### Parámetros de Query

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `estado_credito` | string | No | Estado del crédito (activo, bloqueado, en_revision) |

**Respuesta:** Archivo CSV descargable

---

## Códigos de Error

| Código | Descripción |
|--------|-------------|
| 400 | Solicitud inválida (parámetros incorrectos) |
| 401 | No autorizado (token inválido o faltante) |
| 403 | Prohibido (sin permisos) |
| 500 | Error interno del servidor |

## Notas Importantes

1. **Dependencias Requeridas:**
   - Para PDF: `composer require tecnickcom/tcpdf`
   - Para Excel: `composer require phpoffice/phpspreadsheet`
   - Si no están instaladas, se usará fallback (JSON para PDF, CSV para Excel)

2. **Autenticación:**
   - Todos los endpoints requieren token de autenticación
   - Incluir en header: `Authorization: Bearer {token}`

3. **Límites:**
   - Rango de fechas máximo: 2 años
   - Exportaciones grandes pueden tardar varios segundos

4. **Formato de Fechas:**
   - Siempre usar formato: `YYYY-MM-DD`
   - Ejemplo: `2024-01-15`

