/**
 * Módulo de Gráficos Estadísticos
 * Utiliza Chart.js para crear gráficos interactivos
 */

class ChartsManager {
    constructor() {
        this.charts = new Map();
        this.defaultColors = [
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 99, 132, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)',
            'rgba(199, 199, 199, 0.8)',
            'rgba(83, 102, 255, 0.8)',
            'rgba(255, 99, 255, 0.8)',
            'rgba(99, 255, 132, 0.8)'
        ];
    }

    /**
     * Crea un gráfico de barras
     */
    createBarChart(canvasId, data, options = {}) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            console.warn(`Canvas con id "${canvasId}" no encontrado`);
            return null;
        }

        // Destruir gráfico existente si hay uno
        if (this.charts.has(canvasId)) {
            this.charts.get(canvasId).destroy();
        }

        const ctx = canvas.getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels || [],
                datasets: [{
                    label: data.label || 'Datos',
                    data: data.values || [],
                    backgroundColor: data.colors || this.defaultColors.slice(0, data.values?.length || 1),
                    borderColor: data.borderColors || data.colors || this.defaultColors.slice(0, data.values?.length || 1),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: options.showLegend !== false,
                        position: 'top'
                    },
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function(context) {
                                if (options.formatValue) {
                                    return options.formatValue(context.parsed.y);
                                }
                                return context.dataset.label + ': ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (options.formatYAxis) {
                                    return options.formatYAxis(value);
                                }
                                return value;
                            }
                        }
                    }
                },
                ...options.chartOptions
            }
        });

        this.charts.set(canvasId, chart);
        return chart;
    }

    /**
     * Crea un gráfico de líneas
     */
    createLineChart(canvasId, data, options = {}) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            console.warn(`Canvas con id "${canvasId}" no encontrado`);
            return null;
        }

        if (this.charts.has(canvasId)) {
            this.charts.get(canvasId).destroy();
        }

        const ctx = canvas.getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: data.datasets || [{
                    label: data.label || 'Datos',
                    data: data.values || [],
                    borderColor: data.color || this.defaultColors[0],
                    backgroundColor: data.color ? data.color.replace('0.8', '0.1') : this.defaultColors[0].replace('0.8', '0.1'),
                    tension: 0.4,
                    fill: options.fill !== false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: options.showLegend !== false,
                        position: 'top'
                    },
                    tooltip: {
                        enabled: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (options.formatYAxis) {
                                    return options.formatYAxis(value);
                                }
                                return value;
                            }
                        }
                    }
                },
                ...options.chartOptions
            }
        });

        this.charts.set(canvasId, chart);
        return chart;
    }

    /**
     * Crea un gráfico de pastel (doughnut)
     */
    createDoughnutChart(canvasId, data, options = {}) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            console.warn(`Canvas con id "${canvasId}" no encontrado`);
            return null;
        }

        if (this.charts.has(canvasId)) {
            this.charts.get(canvasId).destroy();
        }

        const ctx = canvas.getContext('2d');
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels || [],
                datasets: [{
                    data: data.values || [],
                    backgroundColor: data.colors || this.defaultColors.slice(0, data.values?.length || 1),
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: options.showLegend !== false,
                        position: options.legendPosition || 'right'
                    },
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                
                                if (options.formatValue) {
                                    return `${label}: ${options.formatValue(value)} (${percentage}%)`;
                                }
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                ...options.chartOptions
            }
        });

        this.charts.set(canvasId, chart);
        return chart;
    }

    /**
     * Crea un gráfico de área
     */
    createAreaChart(canvasId, data, options = {}) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) {
            console.warn(`Canvas con id "${canvasId}" no encontrado`);
            return null;
        }

        if (this.charts.has(canvasId)) {
            this.charts.get(canvasId).destroy();
        }

        const ctx = canvas.getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: data.datasets || [{
                    label: data.label || 'Datos',
                    data: data.values || [],
                    borderColor: data.color || this.defaultColors[0],
                    backgroundColor: data.color ? data.color.replace('0.8', '0.3') : this.defaultColors[0].replace('0.8', '0.3'),
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: options.showLegend !== false,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                ...options.chartOptions
            }
        });

        this.charts.set(canvasId, chart);
        return chart;
    }

    /**
     * Actualiza un gráfico existente
     */
    updateChart(canvasId, newData) {
        const chart = this.charts.get(canvasId);
        if (!chart) {
            console.warn(`Gráfico con id "${canvasId}" no encontrado`);
            return;
        }

        if (newData.labels) {
            chart.data.labels = newData.labels;
        }

        if (newData.values) {
            chart.data.datasets[0].data = newData.values;
        }

        if (newData.datasets) {
            chart.data.datasets = newData.datasets;
        }

        chart.update();
    }

    /**
     * Destruye un gráfico
     */
    destroyChart(canvasId) {
        const chart = this.charts.get(canvasId);
        if (chart) {
            chart.destroy();
            this.charts.delete(canvasId);
        }
    }

    /**
     * Destruye todos los gráficos
     */
    destroyAll() {
        this.charts.forEach((chart, id) => {
            chart.destroy();
        });
        this.charts.clear();
    }
}

// Crear instancia global
const chartsManager = new ChartsManager();

// Funciones helper globales
window.createBarChart = (canvasId, data, options) => chartsManager.createBarChart(canvasId, data, options);
window.createLineChart = (canvasId, data, options) => chartsManager.createLineChart(canvasId, data, options);
window.createDoughnutChart = (canvasId, data, options) => chartsManager.createDoughnutChart(canvasId, data, options);
window.createAreaChart = (canvasId, data, options) => chartsManager.createAreaChart(canvasId, data, options);
window.updateChart = (canvasId, newData) => chartsManager.updateChart(canvasId, newData);
window.chartsManager = chartsManager;

