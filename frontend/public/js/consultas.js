/**
 * Consultas de Clientes
 */

function realizarConsulta(event) {
    event.preventDefault();
    
    const tipo = document.getElementById('tipoConsulta').value;
    const valor = document.getElementById('valorConsulta').value;
    
    if (!tipo || !valor) {
        UI.showAlert('Complete todos los campos', 'warning');
        return;
    }

    consultarCliente(tipo, valor);
}

async function consultarCliente(tipo, valor) {
    try {
        UI.showLoading('Consultando...');
        
        const response = await api.get(`/consultas?tipo=${tipo}&valor=${encodeURIComponent(valor)}`);
        
        mostrarResultado(response);
        UI.hideLoading();
    } catch (error) {
        UI.hideLoading();
        console.error('Error:', error);
        UI.showAlert('No se encontraron resultados', 'warning');
        document.getElementById('resultadoConsulta').style.display = 'none';
    }
}

function mostrarResultado(resultado) {
    const contenedor = document.getElementById('contenidoResultado');
    const card = document.getElementById('resultadoConsulta');
    
    if (!contenedor || !card) return;
    
    // Limpiar contenido previo
    contenedor.innerHTML = '';
    
    if (!resultado || resultado.length === 0) {
        const p = createElement('p', { class: 'text-center' }, 'No se encontraron resultados');
        contenedor.appendChild(p);
        card.style.display = 'block';
        return;
    }

    const tableWrapper = createElement('div', { class: 'table-responsive' });
    const table = createElement('table', { class: 'table' });
    const thead = createElement('thead');
    const headerRow = createElement('tr');
    headerRow.appendChild(createElement('th', {}, 'Campo'));
    headerRow.appendChild(createElement('th', {}, 'Valor'));
    thead.appendChild(headerRow);
    table.appendChild(thead);
    
    const tbody = createElement('tbody');
    
    resultado.forEach(item => {
        Object.keys(item).forEach(key => {
            if (item[key] !== null && item[key] !== undefined) {
                const tr = createElement('tr');
                const th = createElement('td');
                const strong = createElement('strong', {}, formatearCampo(key));
                th.appendChild(strong);
                tr.appendChild(th);
                
                const td = createElement('td', {}, String(item[key]));
                tr.appendChild(td);
                tbody.appendChild(tr);
            }
        });
    });
    
    table.appendChild(tbody);
    tableWrapper.appendChild(table);
    contenedor.appendChild(tableWrapper);
    card.style.display = 'block';
}

function formatearCampo(campo) {
    const campos = {
        'cedula': 'Cédula',
        'nombre': 'Nombre',
        'apellido': 'Apellido',
        'telefono': 'Teléfono',
        'email': 'Email',
        'direccion': 'Dirección',
        'numero_prestamo': 'Número de Préstamo',
        'monto_aprobado': 'Monto Aprobado',
        'estado': 'Estado',
        'fecha_creacion': 'Fecha de Creación'
    };
    return campos[campo] || campo.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function limpiarConsulta() {
    document.getElementById('formConsulta').reset();
    document.getElementById('resultadoConsulta').style.display = 'none';
}

