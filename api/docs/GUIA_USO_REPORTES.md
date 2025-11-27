# Guía de Uso - Sistema de Reportes

## Índice

1. [Generar Reportes PDF](#generar-reportes-pdf)
2. [Generar Reportes Excel](#generar-reportes-excel)
3. [Exportar Datos a CSV](#exportar-datos-a-csv)
4. [Dashboard Avanzado](#dashboard-avanzado)
5. [Solución de Problemas](#solución-de-problemas)

---

## Generar Reportes PDF

### Desde el Frontend Web

1. **Préstamos:**
   - Navegar a **Préstamos**
   - Aplicar filtros si es necesario (estado, fecha, cédula)
   - Hacer clic en **"📄 Exportar PDF"**
   - El archivo se descargará automáticamente

2. **Pagos:**
   - Navegar a **Pagos**
   - Aplicar filtros de fecha si es necesario
   - Hacer clic en **"📄 Exportar PDF"**

3. **Mora:**
   - Usar el endpoint directamente o integrar en una vista futura

### Desde la API

```bash
# Ejemplo con cURL
curl -X GET "http://localhost/api/reportes/exportar-pdf?tipo=prestamos&fecha_desde=2024-01-01&fecha_hasta=2024-01-31" \
  -H "Authorization: Bearer {tu_token}" \
  -o reporte_prestamos.pdf
```

---

## Generar Reportes Excel

### Desde el Frontend Web

1. **Préstamos:**
   - Navegar a **Préstamos**
   - Aplicar filtros
   - Hacer clic en **"📊 Exportar Excel"**

2. **Pagos:**
   - Navegar a **Pagos**
   - Aplicar filtros
   - Hacer clic en **"📊 Exportar Excel"**

### Desde la API

```bash
curl -X GET "http://localhost/api/reportes/exportar-excel?tipo=cobros&fecha_desde=2024-01-01&fecha_hasta=2024-01-31" \
  -H "Authorization: Bearer {tu_token}" \
  -o reporte_cobros.xlsx
```

**Nota:** Si PhpSpreadsheet no está instalado, se generará un archivo CSV en su lugar.

---

## Exportar Datos a CSV

### Desde el Frontend Web

1. **Préstamos:**
   - Hacer clic en **"📋 Exportar CSV"** en la vista de Préstamos

2. **Pagos:**
   - Hacer clic en **"📋 Exportar CSV"** en la vista de Pagos

3. **Clientes:**
   - Hacer clic en **"📋 Exportar CSV"** en la vista de Clientes

### Desde la API

```bash
# Exportar préstamos
curl -X GET "http://localhost/api/exportacion/prestamos?estado=vigente" \
  -H "Authorization: Bearer {tu_token}" \
  -o prestamos.csv

# Exportar pagos
curl -X GET "http://localhost/api/exportacion/pagos?fecha_desde=2024-01-01" \
  -H "Authorization: Bearer {tu_token}" \
  -o pagos.csv

# Exportar clientes
curl -X GET "http://localhost/api/exportacion/clientes" \
  -H "Authorization: Bearer {tu_token}" \
  -o clientes.csv
```

---

## Dashboard Avanzado

### Acceso

1. Navegar a **Dashboard Avanzado** desde el menú lateral
2. O acceder directamente a `/dashboard-avanzado.html`

### Funcionalidades

#### Filtros de Fecha

- **Fecha Desde:** Primer día del período a analizar
- **Fecha Hasta:** Último día del período
- Hacer clic en **"Aplicar Filtros"** para actualizar los datos

**Validaciones:**
- La fecha desde no puede ser mayor que la fecha hasta
- Rango máximo: 2 años

#### Gráficos Disponibles

1. **Préstamos por Mes:**
   - Muestra cantidad y monto total de préstamos por mes
   - Gráfico de línea con dos ejes Y

2. **Cobros por Mes:**
   - Muestra desglose de cobros: total, capital, interés, mora
   - Gráfico de barras agrupadas

3. **Distribución por Estado:**
   - Muestra distribución de préstamos por estado
   - Gráfico de dona

#### Top 10 Clientes

- Lista los 10 clientes con mayor monto total en préstamos
- Incluye: cédula, nombre, total préstamos, monto total, monto pagado, saldo

#### Tendencias

- Compara el mes actual con el mes anterior
- Muestra variación en cantidad y monto
- Indicadores de color:
  - Verde: Aumento positivo
  - Rojo: Disminución

### Exportar Dashboard

- Hacer clic en **"Exportar PDF"** para generar un reporte PDF del dashboard

---

## Solución de Problemas

### Error: "TCPDF no está instalado"

**Solución:**
```bash
cd api
composer require tecnickcom/tcpdf
```

### Error: "PhpSpreadsheet no está instalado"

**Solución:**
```bash
cd api
composer require phpoffice/phpspreadsheet
```

### Error: "Fechas inválidas"

**Causas:**
- Formato incorrecto (debe ser YYYY-MM-DD)
- Fecha desde mayor que fecha hasta
- Rango mayor a 2 años

**Solución:**
- Verificar formato de fechas
- Ajustar el rango de fechas

### Error: "Tipo de reporte no válido"

**Tipos válidos:**
- `prestamos`
- `cobros`
- `mora`

### Los gráficos no se muestran

**Causas posibles:**
- Chart.js no está cargado
- No hay datos para el período seleccionado
- Error de JavaScript en consola

**Solución:**
1. Verificar que Chart.js esté cargado (CDN en el HTML)
2. Verificar la consola del navegador para errores
3. Probar con un rango de fechas diferente

### El archivo descargado está vacío o corrupto

**Causas:**
- Error en la generación del reporte
- Dependencias no instaladas correctamente
- Problemas de memoria en el servidor

**Solución:**
1. Verificar logs del servidor
2. Instalar dependencias correctamente
3. Aumentar `memory_limit` en PHP si es necesario

---

## Mejores Prácticas

1. **Filtros de Fecha:**
   - Usar rangos razonables (máximo 1 año para mejor rendimiento)
   - Evitar exportar todos los datos sin filtros

2. **Exportaciones Grandes:**
   - Considerar usar CSV en lugar de Excel para grandes volúmenes
   - Aplicar filtros para reducir el tamaño

3. **Dashboard Avanzado:**
   - Actualizar datos periódicamente
   - Usar filtros de fecha para análisis específicos

4. **Rendimiento:**
   - Los reportes grandes pueden tardar varios segundos
   - Mostrar indicador de carga al usuario

---

## Contacto y Soporte

Para problemas o preguntas sobre el sistema de reportes, contactar al equipo de desarrollo.

