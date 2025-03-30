function generarCotizacionAutomatica(litrosPintura, tipoPintura) {
    fetch('obtener_cotizaciones_automaticas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            litros_pintura: litrosPintura,
            tipo_pintura: tipoPintura,
            productos_basicos: ['Brocha', 'Lija', 'Espátula', 'Rodillo', 'Bandeja']
        })
    })
    .then(response => response.json())
    .then(cotizaciones => {
        // Ordenar por precio total ascendente
        cotizaciones.sort((a, b) => a.total - b.total);
        
        // Mostrar en tabla
        const tabla = document.getElementById('tabla-cotizaciones').getElementsByTagName('tbody')[0];
        tabla.innerHTML = '';
        
        cotizaciones.forEach((cotizacion, index) => {
            const productosLista = cotizacion.productos.map(p => 
                `${p.nombre} (${p.cantidad} x $${p.precio_unitario.toFixed(2)})`
            ).join('<br>');
            
            const row = tabla.insertRow();
            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${cotizacion.proveedor_nombre}</td>
                <td>${productosLista}</td>
                <td>$${cotizacion.total.toFixed(2)}</td>
                <td>
                    <button class="btn btn-sm btn-primary" 
                            onclick="personalizarCotizacion(${cotizacion.proveedor_id}, ${litrosPintura}, '${tipoPintura}')">
                        Personalizar
                    </button>
                </td>
            `;
        });
    });
}

function personalizarCotizacion(proveedorId, litrosPintura, tipoPintura) {
    // Guardar en localStorage
    localStorage.setItem('proveedorSeleccionado', proveedorId);
    localStorage.setItem('litrosPintura', litrosPintura);
    localStorage.setItem('tipoPintura', tipoPintura);
    
    // Redirigir a la página de cotización
    window.location.href = 'cotizacion.html';
}