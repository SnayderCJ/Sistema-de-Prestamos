/**
 * Gestión de Cooperativas
 */

let cooperativaActual = null;

document.addEventListener('DOMContentLoaded', function() {
    cargarCooperativas();
    
    document.getElementById('formCooperativa').addEventListener('submit', function(e) {
        e.preventDefault();
        guardarCooperativa();
    });
});

async function cargarCooperativas() {
    try {
        const params = new URLSearchParams();
        if (document.getElementById('filtroActiva').value) {
            params.append('activa', document.getElementById('filtroActiva').value);
        }

        const response = await fetch(`${API_BASE_URL}/cooperativas?${params}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const cooperativas = await response.json();
            mostrarCooperativas(cooperativas);
        } else {
            showAlert('Error al cargar las cooperativas', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al cargar las cooperativas', 'error');
    }
}

function mostrarCooperativas(cooperativas) {
    const tbody = document.getElementById('tablaCooperativas');
    const filtro = document.getElementById('filtroBuscar').value.toLowerCase();
    
    const cooperativasFiltradas = cooperativas.filter(c => {
        if (!filtro) return true;
        const nombre = (c.nombre || '').toLowerCase();
        const rnc = (c.rnc || '').toLowerCase();
        return nombre.includes(filtro) || rnc.includes(filtro);
    });
    
    if (cooperativasFiltradas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No hay cooperativas registradas</td></tr>';
        return;
    }

    tbody.innerHTML = cooperativasFiltradas.map(c => `
        <tr>
            <td><strong>${c.nombre}</strong></td>
            <td>${c.rnc || '-'}</td>
            <td>${c.total_socios || 0}</td>
            <td>${formatCurrency(c.total_apartaciones || 0)}</td>
            <td>
                <span class="badge badge-${c.activa ? 'success' : 'secondary'}">
                    ${c.activa ? 'Activa' : 'Inactiva'}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-info" onclick="verDetalleCooperativa(${c.id})">Ver</button>
                <button class="btn btn-sm btn-primary" onclick="editarCooperativa(${c.id})">Editar</button>
                <button class="btn btn-sm btn-success" onclick="gestionarSocios(${c.id})">Socios</button>
            </td>
        </tr>
    `).join('');
}

function filtrarCooperativas() {
    cargarCooperativas();
}

function abrirModalCooperativa() {
    document.getElementById('modalCooperativaTitulo').textContent = 'Nueva Cooperativa';
    document.getElementById('formCooperativa').reset();
    document.getElementById('cooperativaId').value = '';
    document.getElementById('modalCooperativa').style.display = 'block';
}

async function editarCooperativa(id) {
    try {
        showLoading('Cargando cooperativa...');
        const response = await fetch(`${API_BASE_URL}/cooperativas/${id}`, {
            headers: {
                'Authorization': `Bearer ${getToken()}`
            }
        });

        if (response.ok) {
            const cooperativa = await response.json();
            document.getElementById('modalCooperativaTitulo').textContent = 'Editar Cooperativa';
            document.getElementById('cooperativaId').value = cooperativa.id;
            document.getElementById('nombre').value = cooperativa.nombre || '';
            document.getElementById('rnc').value = cooperativa.rnc || '';
            document.getElementById('direccion').value = cooperativa.direccion || '';
            document.getElementById('telefono').value = cooperativa.telefono || '';
            document.getElementById('email').value = cooperativa.email || '';
            document.getElementById('fechaConstitucion').value = cooperativa.fecha_constitucion || '';
            document.getElementById('activa').checked = cooperativa.activa == 1;
            document.getElementById('modalCooperativa').style.display = 'block';
        } else {
            showAlert('Error al cargar la cooperativa', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al cargar la cooperativa', 'error');
    } finally {
        hideLoading();
    }
}

async function guardarCooperativa() {
    const id = document.getElementById('cooperativaId').value;
    const data = {
        nombre: document.getElementById('nombre').value,
        rnc: document.getElementById('rnc').value,
        direccion: document.getElementById('direccion').value,
        telefono: document.getElementById('telefono').value,
        email: document.getElementById('email').value,
        fecha_constitucion: document.getElementById('fechaConstitucion').value,
        activa: document.getElementById('activa').checked ? 1 : 0
    };

    if (!data.nombre) {
        showAlert('El nombre es requerido', 'error');
        return;
    }

    try {
        showLoading('Guardando...');
        const url = id 
            ? `${API_BASE_URL}/cooperativas/${id}`
            : `${API_BASE_URL}/cooperativas`;
        
        const method = id ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getToken()}`
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (response.ok) {
            showAlert(id ? 'Cooperativa actualizada correctamente' : 'Cooperativa creada correctamente', 'success');
            cerrarModal('modalCooperativa');
            cargarCooperativas();
        } else {
            showAlert(result.message || 'Error al guardar la cooperativa', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error al guardar la cooperativa', 'error');
    } finally {
        hideLoading();
    }
}

async function verDetalleCooperativa(id) {
    window.location.href = `cooperativa-detalle.html?id=${id}`;
}

function gestionarSocios(id) {
    window.location.href = `cooperativa-socios.html?cooperativa_id=${id}`;
}

