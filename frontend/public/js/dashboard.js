// ============================================
// Dashboard
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    cargarDashboard();
});

async function cargarDashboard() {
    try {
        UI.showLoading();
        
        const response = await api.get('/dashboard');
        
        if (response.success) {
            mostrarDashboard(response.data);
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar dashboard: ' + error.message, 'danger');
    }
}

function mostrarDashboard(data) {
    // Actualizar estadísticas
    if (data.estadisticas) {
        const stats = data.estadisticas;
        
        // Actualizar cards de estadísticas si existen
        const prestamosActivos = document.querySelector('[data-stat="prestamos-activos"]');
        if (prestamosActivos) {
            prestamosActivos.textContent = stats.prestamos_activos || 0;
        }
        
        const montoTotal = document.querySelector('[data-stat="monto-total"]');
        if (montoTotal) {
            montoTotal.textContent = UI.formatCurrency(stats.monto_total || 0);
        }
        
        const prestamosVencidos = document.querySelector('[data-stat="prestamos-vencidos"]');
        if (prestamosVencidos) {
            prestamosVencidos.textContent = stats.prestamos_vencidos || 0;
        }
        
        const cobrosHoy = document.querySelector('[data-stat="cobros-hoy"]');
        if (cobrosHoy) {
            cobrosHoy.textContent = UI.formatCurrency(stats.cobros_hoy || 0);
        }
    }
    
    // Mostrar préstamos vencidos
    if (data.prestamos_vencidos) {
        mostrarPrestamosVencidos(data.prestamos_vencidos);
    }
    
    // Mostrar gráficos si hay datos
    if (data.graficos) {
        mostrarGraficos(data.graficos);
    } else {
        // Si no hay datos de gráficos, crear gráficos con datos por defecto o desde estadísticas
        crearGraficosPorDefecto(data.estadisticas);
    }
}

function mostrarPrestamosVencidos(prestamos) {
    const tbody = document.querySelector('#tablaPrestamosVencidos tbody');
    
    if (!tbody) return;
    
    if (!prestamos || prestamos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No hay préstamos vencidos</td></tr>';
        return;
    }
    
    tbody.innerHTML = prestamos.map(p => `
        <tr onclick="verDetallePrestamo(${p.id})">
            <td>${p.numero_prestamo}</td>
            <td>${p.cliente_nombre} ${p.cliente_apellido}</td>
            <td>${UI.formatCurrency(p.monto_aprobado)}</td>
            <td>${p.dias_vencido || 0}</td>
            <td>${UI.formatCurrency(p.mora_total || 0)}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); verDetallePrestamo(${p.id})">Ver</button>
            </td>
        </tr>
    `).join('');
}

async function verDetallePrestamo(id) {
    try {
        UI.showLoading('Cargando detalle del préstamo...');
        
        const response = await prestamosService.getById(id);
        
        if (response.success) {
            mostrarDetallePrestamo(response.data);
            document.getElementById('modalDetallePrestamo').classList.add('show');
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar el detalle: ' + error.message, 'danger');
    }
}

function mostrarDetallePrestamo(prestamo) {
    document.getElementById('detalleNumero').textContent = prestamo.numero_prestamo || '-';
    document.getElementById('detalleCliente').textContent = `${prestamo.cliente_nombre || ''} ${prestamo.cliente_apellido || ''}`;
    document.getElementById('detalleCedula').textContent = prestamo.cliente_cedula || '-';
    document.getElementById('detalleMonto').textContent = UI.formatCurrency(prestamo.monto_aprobado || 0);
    document.getElementById('detalleCuota').textContent = UI.formatCurrency(prestamo.cuota_mensual || 0);
    document.getElementById('detallePlazo').textContent = `${prestamo.plazo_meses || 0} meses`;
    document.getElementById('detalleTasa').textContent = `${prestamo.tasa_mensual || 0}%`;
    document.getElementById('detalleEstado').textContent = prestamo.estado || '-';
    document.getElementById('detalleEstado').className = `badge badge-${prestamo.estado || 'pendiente'}`;
    document.getElementById('detalleFechaCreacion').textContent = UI.formatDate(prestamo.fecha_creacion);
    document.getElementById('detalleFechaAprobacion').textContent = prestamo.fecha_aprobacion ? UI.formatDate(prestamo.fecha_aprobacion) : '-';
    document.getElementById('detalleSaldo').textContent = UI.formatCurrency(prestamo.saldo_actual || 0);
    document.getElementById('detalleGarantia').textContent = prestamo.garantia_tipo || '-';
    document.getElementById('detalleObservaciones').textContent = prestamo.observaciones || '-';
}

function cerrarModalDetalle() {
    document.getElementById('modalDetallePrestamo').classList.remove('show');
}

/**
 * Muestra gráficos con datos del servidor
 */
function mostrarGraficos(graficos) {
    // Gráfico de Préstamos por Estado
    if (graficos.prestamos_por_estado) {
        chartsManager.createDoughnutChart('chartPrestamosEstado', {
            labels: graficos.prestamos_por_estado.labels || [],
            values: graficos.prestamos_por_estado.values || [],
            colors: [
                'rgba(75, 192, 192, 0.8)',  // Aprobado - Verde
                'rgba(255, 99, 132, 0.8)',  // Vencido - Rojo
                'rgba(255, 206, 86, 0.8)',  // Pendiente - Amarillo
                'rgba(54, 162, 235, 0.8)',  // Vigente - Azul
                'rgba(153, 102, 255, 0.8)'  // Pagado - Morado
            ]
        }, {
            showLegend: true,
            legendPosition: 'right'
        });
    }

    // Gráfico de Evolución de Préstamos
    if (graficos.evolucion_prestamos) {
        chartsManager.createLineChart('chartEvolucionPrestamos', {
            labels: graficos.evolucion_prestamos.labels || [],
            values: graficos.evolucion_prestamos.values || [],
            color: 'rgba(54, 162, 235, 0.8)'
        }, {
            formatYAxis: (value) => value.toLocaleString('es-DO'),
            fill: true
        });
    }

    // Gráfico de Distribución de Montos
    if (graficos.distribucion_montos) {
        chartsManager.createBarChart('chartDistribucionMontos', {
            labels: graficos.distribucion_montos.labels || [],
            values: graficos.distribucion_montos.values || [],
            label: 'Cantidad de Préstamos'
        }, {
            formatYAxis: (value) => value.toLocaleString('es-DO')
        });
    }

    // Gráfico de Cobros Mensuales
    if (graficos.cobros_mensuales) {
        chartsManager.createAreaChart('chartCobrosMensuales', {
            labels: graficos.cobros_mensuales.labels || [],
            values: graficos.cobros_mensuales.values || [],
            color: 'rgba(75, 192, 192, 0.8)'
        }, {
            formatYAxis: (value) => 'RD$ ' + value.toLocaleString('es-DO')
        });
    }
}

/**
 * Crea gráficos con datos por defecto basados en estadísticas
 */
async function crearGraficosPorDefecto(estadisticas) {
    try {
        // Obtener datos adicionales para gráficos
        const [prestamosData, cobrosData] = await Promise.all([
            api.get('/prestamos?limit=1000').catch(() => ({ data: [] })),
            api.get('/pagos?limit=1000').catch(() => ({ data: [] }))
        ]);

        const prestamos = prestamosData.data || prestamosData || [];
        const pagos = cobrosData.data || cobrosData || [];

        // Gráfico de Préstamos por Estado
        const estadosCount = {};
        prestamos.forEach(p => {
            const estado = p.estado || 'pendiente';
            estadosCount[estado] = (estadosCount[estado] || 0) + 1;
        });

        chartsManager.createDoughnutChart('chartPrestamosEstado', {
            labels: Object.keys(estadosCount),
            values: Object.values(estadosCount),
            colors: [
                'rgba(75, 192, 192, 0.8)',
                'rgba(255, 99, 132, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(153, 102, 255, 0.8)'
            ]
        }, {
            showLegend: true,
            legendPosition: 'right'
        });

        // Gráfico de Evolución (últimos 6 meses)
        const meses = [];
        const prestamosPorMes = {};
        const ahora = new Date();
        
        for (let i = 5; i >= 0; i--) {
            const fecha = new Date(ahora.getFullYear(), ahora.getMonth() - i, 1);
            const mesKey = fecha.toLocaleDateString('es-DO', { month: 'short', year: 'numeric' });
            meses.push(mesKey);
            prestamosPorMes[mesKey] = 0;
        }

        prestamos.forEach(p => {
            if (p.fecha_creacion) {
                const fecha = new Date(p.fecha_creacion);
                const mesKey = fecha.toLocaleDateString('es-DO', { month: 'short', year: 'numeric' });
                if (prestamosPorMes[mesKey] !== undefined) {
                    prestamosPorMes[mesKey]++;
                }
            }
        });

        chartsManager.createLineChart('chartEvolucionPrestamos', {
            labels: meses,
            values: meses.map(m => prestamosPorMes[m] || 0),
            color: 'rgba(54, 162, 235, 0.8)'
        }, {
            formatYAxis: (value) => value.toLocaleString('es-DO'),
            fill: true
        });

        // Gráfico de Distribución de Montos
        const rangos = [
            { label: '0-10K', min: 0, max: 10000 },
            { label: '10K-50K', min: 10000, max: 50000 },
            { label: '50K-100K', min: 50000, max: 100000 },
            { label: '100K-500K', min: 100000, max: 500000 },
            { label: '500K+', min: 500000, max: Infinity }
        ];

        const distribucion = rangos.map(rango => {
            return prestamos.filter(p => {
                const monto = p.monto_aprobado || 0;
                return monto >= rango.min && monto < rango.max;
            }).length;
        });

        chartsManager.createBarChart('chartDistribucionMontos', {
            labels: rangos.map(r => r.label),
            values: distribucion,
            label: 'Cantidad de Préstamos'
        }, {
            formatYAxis: (value) => value.toLocaleString('es-DO')
        });

        // Gráfico de Cobros Mensuales
        const cobrosPorMes = {};
        meses.forEach(m => cobrosPorMes[m] = 0);

        pagos.forEach(p => {
            if (p.fecha_pago) {
                const fecha = new Date(p.fecha_pago);
                const mesKey = fecha.toLocaleDateString('es-DO', { month: 'short', year: 'numeric' });
                if (cobrosPorMes[mesKey] !== undefined) {
                    cobrosPorMes[mesKey] += parseFloat(p.monto || 0);
                }
            }
        });

        chartsManager.createAreaChart('chartCobrosMensuales', {
            labels: meses,
            values: meses.map(m => cobrosPorMes[m] || 0),
            color: 'rgba(75, 192, 192, 0.8)'
        }, {
            formatYAxis: (value) => 'RD$ ' + value.toLocaleString('es-DO')
        });

    } catch (error) {
        console.error('Error creando gráficos por defecto:', error);
        // Mostrar mensaje de error en los gráficos
        ['chartPrestamosEstado', 'chartEvolucionPrestamos', 'chartDistribucionMontos', 'chartCobrosMensuales'].forEach(id => {
            const canvas = document.getElementById(id);
            if (canvas) {
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = '#999';
                ctx.font = '14px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('No hay datos disponibles', canvas.width / 2, canvas.height / 2);
            }
        });
    }
}

// Recargar dashboard cada 5 minutos
setInterval(() => {
    cargarDashboard();
}, 300000);


