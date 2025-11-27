// ============================================
// Dashboard Avanzado
// ============================================

let chartPrestamosMes = null;
let chartCobrosMes = null;
let chartDistribucionEstado = null;

document.addEventListener('DOMContentLoaded', () => {
    // Establecer fechas por defecto (último mes)
    const hoy = new Date();
    const haceUnMes = new Date();
    haceUnMes.setMonth(haceUnMes.getMonth() - 1);
    
    document.getElementById('fechaDesde').value = haceUnMes.toISOString().split('T')[0];
    document.getElementById('fechaHasta').value = hoy.toISOString().split('T')[0];
    
    cargarDashboardAvanzado();
});

async function cargarDashboardAvanzado() {
    try {
        UI.showLoading();
        
        const fechaDesde = document.getElementById('fechaDesde').value;
        const fechaHasta = document.getElementById('fechaHasta').value;
        
        const params = new URLSearchParams();
        if (fechaDesde) params.append('fecha_desde', fechaDesde);
        if (fechaHasta) params.append('fecha_hasta', fechaHasta);
        
        const response = await api.get(`/dashboard-avanzado/estadisticas-avanzadas?${params.toString()}`);
        
        if (response.success) {
            mostrarDashboardAvanzado(response.data);
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al cargar dashboard avanzado: ' + error.message, 'danger');
    }
}

function mostrarDashboardAvanzado(data) {
    // Actualizar estadísticas
    if (data.estadisticas) {
        const stats = data.estadisticas;
        
        document.getElementById('stat-total-prestamos').textContent = stats.total_prestamos || 0;
        document.getElementById('stat-prestamos-activos').textContent = stats.prestamos_activos?.cantidad || 0;
        document.getElementById('stat-monto-activos').textContent = UI.formatCurrency(stats.prestamos_activos?.monto_total || 0);
        document.getElementById('stat-tasa-recuperacion').textContent = (stats.tasa_recuperacion || 0) + '%';
    }
    
    // Mostrar gráficos
    if (data.graficos) {
        mostrarGraficos(data.graficos);
    }
    
    // Mostrar top clientes
    if (data.top_clientes) {
        mostrarTopClientes(data.top_clientes);
    }
    
    // Mostrar tendencias
    if (data.tendencias) {
        mostrarTendencias(data.tendencias);
    }
}

function mostrarGraficos(graficos) {
    // Gráfico de Préstamos por Mes
    if (graficos.prestamos_por_mes && graficos.prestamos_por_mes.length > 0) {
        const ctx = document.getElementById('chartPrestamosMes').getContext('2d');
        
        if (chartPrestamosMes) {
            chartPrestamosMes.destroy();
        }
        
        chartPrestamosMes = new Chart(ctx, {
            type: 'line',
            data: {
                labels: graficos.prestamos_por_mes.map(p => p.mes),
                datasets: [{
                    label: 'Cantidad',
                    data: graficos.prestamos_por_mes.map(p => p.cantidad),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }, {
                    label: 'Monto Total',
                    data: graficos.prestamos_por_mes.map(p => parseFloat(p.monto_total || 0)),
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }
    
    // Gráfico de Cobros por Mes
    if (graficos.cobros_por_mes && graficos.cobros_por_mes.length > 0) {
        const ctx = document.getElementById('chartCobrosMes').getContext('2d');
        
        if (chartCobrosMes) {
            chartCobrosMes.destroy();
        }
        
        chartCobrosMes = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: graficos.cobros_por_mes.map(c => c.mes),
                datasets: [{
                    label: 'Total Cobros',
                    data: graficos.cobros_por_mes.map(c => parseFloat(c.monto_total || 0)),
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: 'Capital',
                    data: graficos.cobros_por_mes.map(c => parseFloat(c.capital_total || 0)),
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }, {
                    label: 'Interés',
                    data: graficos.cobros_por_mes.map(c => parseFloat(c.interes_total || 0)),
                    backgroundColor: 'rgba(255, 206, 86, 0.5)',
                    borderColor: 'rgba(255, 206, 86, 1)',
                    borderWidth: 1
                }, {
                    label: 'Mora',
                    data: graficos.cobros_por_mes.map(c => parseFloat(c.mora_total || 0)),
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Gráfico de Distribución por Estado
    if (graficos.distribucion_estado && graficos.distribucion_estado.length > 0) {
        const ctx = document.getElementById('chartDistribucionEstado').getContext('2d');
        
        if (chartDistribucionEstado) {
            chartDistribucionEstado.destroy();
        }
        
        chartDistribucionEstado = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: graficos.distribucion_estado.map(d => d.estado),
                datasets: [{
                    data: graficos.distribucion_estado.map(d => d.cantidad),
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.5)',
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(255, 206, 86, 0.5)',
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(153, 102, 255, 0.5)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
}

function mostrarTopClientes(clientes) {
    const tbody = document.querySelector('#tablaTopClientes tbody');
    
    if (!tbody) return;
    
    if (!clientes || clientes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No hay datos disponibles</td></tr>';
        return;
    }
    
    tbody.innerHTML = clientes.map((cliente, index) => {
        const saldo = (cliente.monto_total || 0) - (cliente.monto_pagado || 0);
        return `
            <tr>
                <td>${index + 1}</td>
                <td>${cliente.cedula || '-'}</td>
                <td>${cliente.nombre || ''} ${cliente.apellido || ''}</td>
                <td>${cliente.total_prestamos || 0}</td>
                <td>${UI.formatCurrency(cliente.monto_total || 0)}</td>
                <td>${UI.formatCurrency(cliente.monto_pagado || 0)}</td>
                <td>${UI.formatCurrency(saldo)}</td>
            </tr>
        `;
    }).join('');
}

function mostrarTendencias(tendencias) {
    if (tendencias.prestamos) {
        const prestamos = tendencias.prestamos;
        
        document.getElementById('tendencia-cantidad-actual').textContent = prestamos.mes_actual?.cantidad || 0;
        document.getElementById('tendencia-cantidad-anterior').textContent = prestamos.mes_anterior?.cantidad || 0;
        
        const variacionCantidad = prestamos.variacion_cantidad || 0;
        const variacionCantidadEl = document.getElementById('tendencia-cantidad-variacion');
        variacionCantidadEl.textContent = variacionCantidad.toFixed(2) + '%';
        variacionCantidadEl.className = 'tendencia-value ' + (variacionCantidad >= 0 ? 'text-success' : 'text-danger');
        
        document.getElementById('tendencia-monto-actual').textContent = UI.formatCurrency(prestamos.mes_actual?.monto || 0);
        document.getElementById('tendencia-monto-anterior').textContent = UI.formatCurrency(prestamos.mes_anterior?.monto || 0);
        
        const variacionMonto = prestamos.variacion_monto || 0;
        const variacionMontoEl = document.getElementById('tendencia-monto-variacion');
        variacionMontoEl.textContent = variacionMonto.toFixed(2) + '%';
        variacionMontoEl.className = 'tendencia-value ' + (variacionMonto >= 0 ? 'text-success' : 'text-danger');
    }
}

async function exportarDashboard() {
    try {
        UI.showLoading('Generando reporte PDF...');
        
        const fechaDesde = document.getElementById('fechaDesde').value;
        const fechaHasta = document.getElementById('fechaHasta').value;
        
        const params = new URLSearchParams();
        if (fechaDesde) params.append('fecha_desde', fechaDesde);
        if (fechaHasta) params.append('fecha_hasta', fechaHasta);
        params.append('tipo', 'prestamos');
        
        const token = getToken();
        const url = `${API_BASE_URL}/reportes/exportar-pdf?${params.toString()}`;
        
        // Descargar PDF
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        
        if (response.ok) {
            const blob = await response.blob();
            const urlBlob = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = urlBlob;
            a.download = `dashboard_${fechaDesde}_${fechaHasta}.pdf`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(urlBlob);
            
            UI.showAlert('Reporte PDF generado exitosamente', 'success');
        } else {
            throw new Error('Error al generar el reporte PDF');
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        UI.showAlert('Error al exportar dashboard: ' + error.message, 'danger');
    }
}

