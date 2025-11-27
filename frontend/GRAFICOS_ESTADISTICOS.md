# Gráficos Estadísticos - ImaxPrestamos

## 📊 Sistema de Gráficos Implementado

Se ha integrado **Chart.js** para crear gráficos estadísticos interactivos en el dashboard.

## 🎯 Gráficos Disponibles

### 1. Préstamos por Estado (Gráfico de Dona)
- **Tipo**: Doughnut Chart
- **ID Canvas**: `chartPrestamosEstado`
- **Datos**: Distribución de préstamos por estado (Aprobado, Vencido, Pendiente, etc.)
- **Ubicación**: Dashboard principal

### 2. Evolución de Préstamos (Gráfico de Líneas)
- **Tipo**: Line Chart
- **ID Canvas**: `chartEvolucionPrestamos`
- **Datos**: Cantidad de préstamos en los últimos 6 meses
- **Ubicación**: Dashboard principal

### 3. Distribución de Montos (Gráfico de Barras)
- **Tipo**: Bar Chart
- **ID Canvas**: `chartDistribucionMontos`
- **Datos**: Cantidad de préstamos por rango de montos
- **Rangos**: 0-10K, 10K-50K, 50K-100K, 100K-500K, 500K+
- **Ubicación**: Dashboard principal

### 4. Cobros Mensuales (Gráfico de Área)
- **Tipo**: Area Chart
- **ID Canvas**: `chartCobrosMensuales`
- **Datos**: Total de cobros por mes (últimos 6 meses)
- **Ubicación**: Dashboard principal

## 🔧 Cómo Funciona

### Integración con el API

El sistema intenta cargar datos de gráficos desde el endpoint `/dashboard`:

```javascript
{
  "success": true,
  "data": {
    "estadisticas": { ... },
    "graficos": {
      "prestamos_por_estado": {
        "labels": ["Aprobado", "Vencido", "Pendiente"],
        "values": [50, 20, 30]
      },
      "evolucion_prestamos": {
        "labels": ["Ene", "Feb", "Mar", ...],
        "values": [10, 15, 12, ...]
      },
      "distribucion_montos": {
        "labels": ["0-10K", "10K-50K", ...],
        "values": [25, 40, ...]
      },
      "cobros_mensuales": {
        "labels": ["Ene", "Feb", "Mar", ...],
        "values": [50000, 75000, ...]
      }
    }
  }
}
```

### Modo Fallback

Si el API no proporciona datos de gráficos, el sistema:
1. Obtiene datos de préstamos y pagos directamente
2. Calcula las estadísticas localmente
3. Genera los gráficos con los datos disponibles

## 📝 Uso del ChartsManager

### Crear un Gráfico de Barras

```javascript
chartsManager.createBarChart('miCanvas', {
    labels: ['Enero', 'Febrero', 'Marzo'],
    values: [10, 20, 15],
    label: 'Ventas',
    colors: ['rgba(54, 162, 235, 0.8)', ...]
}, {
    formatYAxis: (value) => value.toLocaleString('es-DO'),
    showLegend: true
});
```

### Crear un Gráfico de Líneas

```javascript
chartsManager.createLineChart('miCanvas', {
    labels: ['Ene', 'Feb', 'Mar'],
    values: [100, 150, 120],
    color: 'rgba(54, 162, 235, 0.8)'
}, {
    fill: true,
    formatYAxis: (value) => 'RD$ ' + value
});
```

### Crear un Gráfico de Dona

```javascript
chartsManager.createDoughnutChart('miCanvas', {
    labels: ['Aprobado', 'Vencido', 'Pendiente'],
    values: [50, 20, 30],
    colors: [
        'rgba(75, 192, 192, 0.8)',
        'rgba(255, 99, 132, 0.8)',
        'rgba(255, 206, 86, 0.8)'
    ]
}, {
    showLegend: true,
    legendPosition: 'right'
});
```

### Actualizar un Gráfico Existente

```javascript
chartsManager.updateChart('miCanvas', {
    labels: ['Nuevo', 'Datos'],
    values: [100, 200]
});
```

### Destruir un Gráfico

```javascript
chartsManager.destroyChart('miCanvas');
```

## 🎨 Personalización

### Colores por Defecto

El sistema incluye una paleta de colores por defecto:
- Azul: `rgba(54, 162, 235, 0.8)`
- Rojo: `rgba(255, 99, 132, 0.8)`
- Verde: `rgba(75, 192, 192, 0.8)`
- Amarillo: `rgba(255, 206, 86, 0.8)`
- Morado: `rgba(153, 102, 255, 0.8)`
- Naranja: `rgba(255, 159, 64, 0.8)`

### Formateo de Valores

Puedes personalizar cómo se muestran los valores:

```javascript
{
    formatValue: (value) => UI.formatCurrency(value),
    formatYAxis: (value) => value.toLocaleString('es-DO')
}
```

## 📱 Responsive

Los gráficos son completamente responsive:
- Se ajustan automáticamente al tamaño del contenedor
- En móviles, los gráficos se apilan verticalmente
- Mantienen su aspecto ratio en todos los tamaños

## 🔄 Actualización Automática

El dashboard se actualiza automáticamente cada 5 minutos, lo que incluye:
- Recarga de datos
- Actualización de gráficos
- Refresco de estadísticas

## 📊 Agregar Nuevos Gráficos

Para agregar un nuevo gráfico al dashboard:

1. **Agregar el canvas en el HTML**:
```html
<div class="chart-container">
    <div class="chart-card">
        <h3>Título del Gráfico</h3>
        <canvas id="miNuevoGrafico"></canvas>
    </div>
</div>
```

2. **Crear el gráfico en JavaScript**:
```javascript
function mostrarGraficos(graficos) {
    if (graficos.mi_nuevo_grafico) {
        chartsManager.createBarChart('miNuevoGrafico', {
            labels: graficos.mi_nuevo_grafico.labels,
            values: graficos.mi_nuevo_grafico.values
        });
    }
}
```

## 🐛 Solución de Problemas

### Los gráficos no se muestran

1. Verifica que Chart.js esté cargado (consola del navegador)
2. Verifica que el canvas exista en el DOM
3. Revisa la consola para errores de JavaScript
4. Verifica que los datos estén en el formato correcto

### Los gráficos se ven pequeños

1. Verifica que el contenedor tenga altura definida
2. Ajusta `min-height` en `.chart-container`
3. Verifica que `maintainAspectRatio: false` esté configurado

### Los datos no se actualizan

1. Verifica que `updateChart()` se esté llamando
2. Revisa que los nuevos datos estén en el formato correcto
3. Verifica que el gráfico exista antes de actualizar

## 📚 Recursos

- **Chart.js Documentation**: https://www.chartjs.org/docs/
- **CDN**: https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js
- **Ejemplos**: Ver `js/charts.js` y `js/dashboard.js`

## ✅ Características Implementadas

- ✅ 4 tipos de gráficos (Bar, Line, Doughnut, Area)
- ✅ Responsive y adaptativo
- ✅ Formateo de valores personalizado
- ✅ Actualización dinámica
- ✅ Modo fallback con datos locales
- ✅ Estilos integrados con el diseño
- ✅ Tooltips interactivos
- ✅ Leyendas configurables

---

**Última actualización**: 2025-01-27
**Versión Chart.js**: 4.4.0

