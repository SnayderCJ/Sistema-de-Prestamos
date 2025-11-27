/**
 * Reportes DGII
 */

document.addEventListener('DOMContentLoaded', function() {
    cargarReportes();
    
    // Establecer período actual por defecto
    const today = new Date();
    const periodo = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;
    if (document.getElementById('periodo606')) document.getElementById('periodo606').value = periodo;
    if (document.getElementById('periodo607')) document.getElementById('periodo607').value = periodo;
    if (document.getElementById('periodo608')) document.getElementById('periodo608').value = periodo;
});

async function cargarReportes() {
    try {
        const params = new URLSearchParams();
        if (document.getElementById('filtroTipo').value) {
            params.append('tipo_reporte', document.getElementById('filtroTipo').value);
        }
        if (document.getElementById('filtroPeriodo').value) {
            params.append('periodo', document.getElementById('filtroPeriodo').value);
        }

        const response = await fetch(`${API_BASE_URL}/reportes-dgii?${params}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const reportes = await response.json();
            mostrarReportes(reportes);
        } else {
            showAlert('Error al cargar los reportes', 'error');
        }
    } catch (error) {
        console.error('Error cargando reportes:', error);
        showAlert('Error al cargar los reportes', 'error');
    }
}

function mostrarReportes(reportes) {
    const tbody = document.getElementById('tablaReportes');
    
    if (!reportes || reportes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No hay reportes generados</td></tr>';
        return;
    }

    tbody.innerHTML = reportes.map(r => `
        <tr>
            <td><strong>${r.tipo_reporte}</strong></td>
            <td>${r.periodo}</td>
            <td>${r.fecha_generacion ? formatDate(r.fecha_generacion) : '-'}</td>
            <td>${r.fecha_envio ? formatDate(r.fecha_envio) : '-'}</td>
            <td><span class="badge badge-${getEstadoBadge(r.estado)}">${r.estado || 'pendiente'}</span></td>
            <td>
                <div class="btn-group" role="group">
                    ${r.archivo_txt ? `<button class="btn btn-sm btn-success" onclick="descargarReporteTXT('${r.tipo_reporte}', '${r.periodo}')" title="Descargar TXT">📄 TXT</button>` : ''}
                    ${r.archivo_excel ? `<button class="btn btn-sm btn-primary" onclick="descargarReporteExcel('${r.tipo_reporte}', '${r.periodo}')" title="Descargar Excel">📊 Excel</button>` : ''}
                    ${r.archivo_pdf ? `<button class="btn btn-sm btn-danger" onclick="descargarReportePDF('${r.tipo_reporte}', '${r.periodo}')" title="Descargar PDF">📑 PDF</button>` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

function getEstadoBadge(estado) {
    const badges = {
        'pendiente': 'warning',
        'generado': 'info',
        'enviado': 'success',
        'rechazado': 'danger'
    };
    return badges[estado] || 'secondary';
}

async function generarReporte(tipo) {
    const periodoInput = document.getElementById(`periodo${tipo}`);
    const periodo = periodoInput ? periodoInput.value : new Date().toISOString().slice(0, 7);
    
    if (!periodo || !periodo.match(/^\d{4}-\d{2}$/)) {
        UI.showAlert('Ingrese un período válido (YYYY-MM)', 'danger');
        return;
    }

    if (!confirm(`¿Generar Reporte ${tipo} (TXT, Excel y PDF) para el período ${periodo}?`)) {
        return;
    }

    try {
        UI.showLoading('Generando reportes (TXT, Excel, PDF)...');
        
        const response = await api.post(`/reportes-dgii/${tipo}?periodo=${periodo}`);
        
        if (response.success !== false) {
            UI.showAlert(`Reporte ${tipo} generado exitosamente en todos los formatos`, 'success');
            
            // Ofrecer descarga inmediata
            if (response.data && response.data.archivos) {
                const formatos = [];
                if (response.data.archivos.txt) formatos.push('TXT');
                if (response.data.archivos.excel) formatos.push('Excel');
                if (response.data.archivos.pdf) formatos.push('PDF');
                
                if (formatos.length > 0) {
                    const descargar = confirm(`Reporte ${tipo} generado en formato: ${formatos.join(', ')}. ¿Desea descargar algún formato ahora?`);
                    if (descargar) {
                        if (response.data.archivos.txt) descargarReporteTXT(tipo, periodo);
                        if (response.data.archivos.excel) setTimeout(() => descargarReporteExcel(tipo, periodo), 500);
                        if (response.data.archivos.pdf) setTimeout(() => descargarReportePDF(tipo, periodo), 1000);
                    }
                }
            }
            
            cargarReportes();
        } else {
            UI.showAlert(response.message || 'Error al generar el reporte', 'danger');
        }
        
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        console.error('Error:', error);
        UI.showAlert(error.message || 'Error al generar el reporte', 'danger');
    }
}

async function descargarReporteTXT(tipo, periodo) {
    try {
        const url = `/api/reportes-dgii/descargar-txt?tipo=${tipo}&periodo=${periodo}`;
        await api.downloadFile(url, {}, `R${tipo}_${periodo}.txt`);
        UI.showAlert('Reporte TXT descargado correctamente', 'success');
    } catch (error) {
        UI.showAlert('Error al descargar reporte TXT: ' + error.message, 'danger');
    }
}

async function descargarReporteExcel(tipo, periodo) {
    try {
        const url = `/api/reportes-dgii/descargar-excel?tipo=${tipo}&periodo=${periodo}`;
        await api.downloadFile(url, {}, `R${tipo}_${periodo}.xlsx`);
        UI.showAlert('Reporte Excel descargado correctamente', 'success');
    } catch (error) {
        UI.showAlert('Error al descargar reporte Excel: ' + error.message, 'danger');
    }
}

async function descargarReportePDF(tipo, periodo) {
    try {
        const url = `/api/reportes-dgii/descargar-pdf?tipo=${tipo}&periodo=${periodo}`;
        await api.downloadFile(url, {}, `R${tipo}_${periodo}.pdf`);
        UI.showAlert('Reporte PDF descargado correctamente', 'success');
    } catch (error) {
        UI.showAlert('Error al descargar reporte PDF: ' + error.message, 'danger');
    }
}

function filtrarReportes() {
    cargarReportes();
}

function descargarReporte(id) {
    window.open(`${API_BASE_URL}/reportes-dgii/${id}/descargar`, '_blank');
}

function enviarReporte(id) {
    if (!confirm('¿Enviar este reporte a la DGII?')) {
        return;
    }

    fetch(`${API_BASE_URL}/reportes-dgii/${id}/enviar`, {
        method: 'PUT',
        headers: {
            'Authorization': `Bearer ${getToken()}`
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Reporte enviado exitosamente', 'success');
            cargarReportes();
        } else {
            showAlert(data.message || 'Error al enviar el reporte', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error al enviar el reporte', 'error');
    });
}

