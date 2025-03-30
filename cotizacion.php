<?php
// Obtener parámetros de la calculadora
$litrosPintura = isset($_GET['litros']) ? floatval($_GET['litros']) : 0;
$areaPintura = isset($_GET['area']) ? floatval($_GET['area']) : 0;
$tipoPintura = isset($_GET['tipoPintura']) ? htmlspecialchars($_GET['tipoPintura']) : '';
$tipoProducto = isset($_GET['tipoProducto']) ? htmlspecialchars($_GET['tipoProducto']) : '';

// Mapear nombres descriptivos
$tiposPintura = [
    'interior' => 'Interior',
    'exterior' => 'Exterior'
];

$tiposProducto = [
    'vinilica' => 'Vinílica',
    'agua' => 'Al agua',
    'esmalte' => 'Esmalte',
    'latex' => 'Látex'
];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personalizar Cotización - Pintex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .proveedor-card {
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .proveedor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .selected {
            border: 2px solid #198754;
            background-color: rgba(25, 135, 84, 0.05);
        }

        .info-proveedor {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 5px solid #198754;
        }

        .table-cotizacion th {
            background-color: #198754;
            color: white;
        }

        #litros-necesarios {
            font-weight: bold;
            color: #198754;
        }

        .badge-litros {
            background-color: #198754;
            font-size: 1rem;
            padding: 5px 10px;
        }

        .btn-cotizar {
            background-color: #198754;
            color: white;
            font-weight: 600;
        }

        .btn-cotizar:hover {
            background-color: #157347;
            color: white;
        }

        .loading-spinner {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>

<body>
    <header class="bg-primary text-white text-center py-4 shadow">
        <div class="container">
            <img src="imagen/logo.jpg" alt="Logo" class="rounded-circle shadow" width="100">
            <h1 class="mt-3">Pintex - Personalizar Cotización</h1>
        </div>
    </header>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.html">Pintex</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.html"><i class="fas fa-home"></i> Inicio</a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="index.html#calculator"><i
                                class="fas fa-calculator"></i> Calculadora</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <!-- Mostrar alerta si no hay datos de cálculo -->
        <?php if ($litrosPintura <= 0): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> No se han recibido datos de cálculo válidos.
                <a href="index.html#calculator" class="alert-link">Volver a la calculadora</a>
            </div>
        <?php endif; ?>

        <div class="cotizacion-container">
            <!-- Sección de selección de proveedor -->
            <div id="seleccion-proveedor">
                <h3 class="mb-4"><i class="fas fa-truck"></i> Seleccione un proveedor</h3>
                <div id="proveedores-container" class="row mt-3 g-3"></div>
            </div>

            <!-- Sección de productos -->
            <div id="seccion-productos" style="display: none;">
                <div id="info-proveedor" class="info-proveedor">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h3><i class="fas fa-building"></i> Proveedor: <span id="proveedor-nombre"
                                    class="text-primary"></span></h3>
                            <p class="mb-1"><strong><i class="fas fa-phone"></i> Teléfono:</strong> <span
                                    id="proveedor-telefono"></span></p>
                        </div>
                        <div class="mt-2 mt-md-0">
                            <p class="mb-1"><strong><i class="fas fa-ruler-combined"></i> Área a pintar:</strong>
                                <?php echo number_format($areaPintura, 2); ?> m²</p>
                            <p class="mb-1"><strong><i class="fas fa-paint-roller"></i> Tipo:</strong>
                                <?php echo isset($tiposPintura[$tipoPintura]) ? $tiposPintura[$tipoPintura] : 'N/A'; ?>
                                -
                                <?php echo isset($tiposProducto[$tipoProducto]) ? $tiposProducto[$tipoProducto] : 'N/A'; ?>
                            </p>
                            <p class="mb-0"><strong><i class="fas fa-tint"></i> Litros necesarios:</strong>
                                <span id="litros-necesarios"
                                    class="badge badge-litros"><?php echo number_format($litrosPintura, 1); ?></span>
                            </p>
                        </div>
                    </div>
                </div>

                <div id="loading" class="text-center py-5">
                    <div class="spinner-border text-primary loading-spinner" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-3">Cargando productos...</p>
                </div>
                <div style='display: flex !important;align-items: flex-start;'>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Tipo</th>
                                <th>Acción</th>
                                <th>Imagen</th>
                            </tr>
                        </thead>
                        <tbody id="productos-disponibles-body"></tbody>
                    </table>

                    
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Color</th>
                                <th>Tipo</th>
                                <th>Tamaño</th>
                                <th>Precio</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="pinturas-disponibles-body"></tbody>
                    </table>

                </div>


                <div class="table-responsive mt-5">
                    <h4 class="mb-3"><i class="fas fa-file-invoice-dollar"></i> Productos en la cotización</h4>
                    <table class="table table-bordered table-cotizacion" id="tabla-cotizacion">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Producto</th>
                                <th>Proveedor</th>
                                <th>Cantidad</th>
                                <th>P. Unitario</th>
                                <th>Subtotal</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="productos-cotizacion-body"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-end"><strong>Total:</strong></td>
                                <td id="total-cotizacion" class="fw-bold">$0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <button id="cambiar-proveedor" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cambiar Proveedor
                    </button>
                    <button id="guardar-cotizacion" class="btn btn-cotizar">
                        <i class="fas fa-save"></i> Guardar Cotización
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal para editar cantidad -->
    <div class="modal fade" id="modal-cantidad" tabindex="-1" aria-labelledby="modalCantidadLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalCantidadLabel"><i class="fas fa-cart-plus"></i> Agregar Producto
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="form-cantidad">
                        <input type="hidden" id="producto-id">
                        <input type="hidden" id="producto-nombre">
                        <input type="hidden" id="producto-precio">
                        <div class="mb-3">
                            <label for="cantidad" class="form-label">Cantidad</label>
                            <input type="number" class="form-control" id="cantidad" min="1" value="1" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i>
                        Cancelar</button>
                    <button type="button" class="btn btn-primary" id="agregar-producto"><i class="fas fa-check"></i>
                        Agregar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Obtener parámetros de la URL
        const urlParams = new URLSearchParams(window.location.search);
        const litrosPintura = urlParams.get('litros') || <?php echo $litrosPintura; ?>;
        const areaPintura = urlParams.get('area') || <?php echo $areaPintura; ?>;

        let proveedorSeleccionado = null;
        const cantidadModal = new bootstrap.Modal(document.getElementById('modal-cantidad'));

        document.addEventListener('DOMContentLoaded', function () {
            // Mostrar litros de pintura si existen
            if (litrosPintura && litrosPintura > 0) {
                document.getElementById('litros-necesarios').textContent = parseFloat(litrosPintura).toFixed(1);
            }

            cargarProveedores();

            // Evento para agregar producto desde el modal
            document.getElementById('agregar-producto').addEventListener('click', agregarProductoCotizacion);

            // Evento para cambiar de proveedor
            document.getElementById('cambiar-proveedor').addEventListener('click', function () {
                document.getElementById('seccion-productos').style.display = 'none';
                document.getElementById('seleccion-proveedor').style.display = 'block';
            });

            // Evento para guardar cotización
            document.getElementById('guardar-cotizacion').addEventListener('click', function () {
                guardarCotizacion();
            });
        });

        function cargarProveedores() {
            fetch('obtener_proveedores.php')
                .then(response => {
                    if (!response.ok) throw new Error('Error al cargar proveedores');
                    return response.json();
                })
                .then(data => {
                    if (!data.success) throw new Error(data.error || 'Error en los datos');

                    const container = document.getElementById('proveedores-container');
                    container.innerHTML = '';

                    if (data.data.length === 0) {
                        container.innerHTML = '<div class="col-12 text-center py-4"><p>No hay proveedores disponibles</p></div>';
                        return;
                    }

                    data.data.forEach(proveedor => {
                        const card = document.createElement('div');
                        card.className = 'col-md-4';
                        card.innerHTML = `
                            <div class="card proveedor-card mb-3" data-id="${proveedor.id}">
                                <div class="card-body">
                                    <h5 class="card-title">${proveedor.nombre}</h5>
                                    <p class="card-text"><i class="fas fa-phone"></i> ${proveedor.telefono}</p>
                                    <p class="card-text small"><i class="fas fa-map-marker-alt"></i> ${proveedor.direccion}</p>
                                </div>
                            </div>
                        `;

                        card.querySelector('.card').addEventListener('click', function () {
                            seleccionarProveedor(proveedor);
                        });

                        container.appendChild(card);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar proveedores: ' + error.message);
                });
        }

        function seleccionarProveedor(proveedor) {
            proveedorSeleccionado = proveedor;

            // Actualizar UI
            document.querySelectorAll('.proveedor-card').forEach(card => {
                card.classList.remove('selected');
                if (card.dataset.id == proveedor.id) {
                    card.classList.add('selected');
                }
            });

            document.getElementById('proveedor-nombre').textContent = proveedor.nombre;
            document.getElementById('proveedor-telefono').textContent = proveedor.telefono;

            // Mostrar sección de productos
            document.getElementById('seleccion-proveedor').style.display = 'none';
            document.getElementById('seccion-productos').style.display = 'block';

            // Cargar productos del proveedor
            cargarProductosProveedor(proveedor.id);
        }

        function cargarProductosProveedor(proveedorId) {
            const loading = document.getElementById('loading');
            const productosBody = document.getElementById('productos-disponibles-body');
            const pinturasBody = document.getElementById('pinturas-disponibles-body');

            loading.style.display = 'block';
            productosBody.innerHTML = '';
            pinturasBody.innerHTML = '';

            fetch(`obtener_productos_proveedor.php?proveedor_id=${proveedorId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Error en la respuesta del servidor');
                    return response.json();
                })
                .then(data => {
                    loading.style.display = 'none';

                    if (!data.success) throw new Error(data.error || 'Error en los datos');

                    // Procesar productos generales
                    if (!Array.isArray(data.productos) || data.productos.length === 0) {
                        productosBody.innerHTML = '<tr><td colspan="4" class="text-center py-4">No hay productos disponibles</td></tr>';
                    } else {
                        productosBody.innerHTML = '';
                        data.productos.forEach(producto => {
                            const row = productosBody.insertRow();
                            row.innerHTML = `
                        <td>
                            <strong>${producto.nombre}</strong>
                            ${producto.marca ? `<br><small class="text-muted">${producto.marca}</small>` : ''}
                        </td>
                        <td>$${parseFloat(producto.precio).toFixed(2)}</td>
                        <td>${producto.tipo || 'N/A'}</td>
                        <td><img src="${producto.imagen || 'N/A'}" alt="Logo" width="40"></td>
                          
                        <td>
                            <button class="btn btn-sm btn-primary btn-agregar"
                                    data-id="${producto.id}"
                                    data-nombre="${producto.nombre}"
                                    data-precio="${producto.precio}">
                                <i class="fas fa-plus"></i> Agregar
                            </button>
                        </td>
                    `;
                            row.querySelector('.btn-agregar').addEventListener('click', function () {
                                prepararAgregarProducto(this.dataset.id, this.dataset.nombre, this.dataset.precio);
                            });
                        });
                    }

                   // Procesar pinturas
if (!Array.isArray(data.pinturas) || data.pinturas.length === 0) {
    pinturasBody.innerHTML = '<tr><td colspan="5" class="text-center py-4">No hay pinturas disponibles</td></tr>';
} else {
    pinturasBody.innerHTML = '';
    data.pinturas.forEach(pintura => {
        const row = pinturasBody.insertRow();
        row.innerHTML = `
            <td style="background-color: ${pintura.codigo_rgb};">
                <strong>${pintura.nombre_color}</strong>
                ${pintura.marca ? `<br><small class="text-muted">${pintura.marca}</small>` : ''}
            </td>
            <td>${pintura.tipo || 'N/A'}</td>
            <td>${pintura.tamano || 'N/A'}</td>
            <td>$${pintura.Precio ? parseFloat(pintura.Precio).toFixed(2) : 'N/A'}</td>
            <td>
                <button class="btn btn-sm btn-primary btn-agregar"
                        data-id="${pintura.id}"
                        data-nombre="${pintura.tipo}"
                        data-precio="${pintura.Precio || '0'}">
                    <i class="fas fa-plus"></i> Agregar
                </button>
            </td>
        `;
    });
}

                })
                .catch(error => {
                    loading.style.display = 'none';
                    console.error('Error:', error);
                    productosBody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle"></i> ${error.message}</td></tr>`;
                    pinturasBody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle"></i> ${error.message}</td></tr>`;
                });
        }



        function prepararAgregarProducto(id, nombre, precio) {
            document.getElementById('producto-id').value = id;
            document.getElementById('producto-nombre').value = nombre;
            document.getElementById('producto-precio').value = precio;
            document.getElementById('cantidad').value = 1;

            cantidadModal.show();
        }

        function agregarProductoCotizacion() {
            const id = document.getElementById('producto-id').value;
            const nombre = document.getElementById('producto-nombre').value;
            const precio = parseFloat(document.getElementById('producto-precio').value);
            const cantidad = parseInt(document.getElementById('cantidad').value);

            if (!proveedorSeleccionado) {
                alert('No se ha seleccionado un proveedor');
                return;
            }

            if (isNaN(cantidad)) {
                alert('Por favor ingrese una cantidad válida');
                return;
            }

            const subtotal = (precio * cantidad).toFixed(2);
            const productosBody = document.getElementById('productos-cotizacion-body');
            const row = productosBody.insertRow();

            row.innerHTML = `
                <td>${productosBody.rows.length + 1}</td>
                <td>${nombre}</td>
                <td>${proveedorSeleccionado.nombre}</td>
                <td>${cantidad}</td>
                <td>$${precio.toFixed(2)}</td>
                <td>$${subtotal}</td>
                <td>
                    <button class="btn btn-sm btn-warning btn-editar me-1">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger btn-eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;

            // Configurar eventos para los botones
            row.querySelector('.btn-editar').addEventListener('click', function () {
                const currentRow = this.closest('tr');
                prepararAgregarProducto(
                    id,
                    currentRow.cells[1].textContent,
                    currentRow.cells[4].textContent.replace('$', '')
                );
                document.getElementById('cantidad').value = currentRow.cells[3].textContent;
                currentRow.remove();
                calcularTotal();
            });

            row.querySelector('.btn-eliminar').addEventListener('click', function () {
                if (confirm('¿Eliminar este producto de la cotización?')) {
                    this.closest('tr').remove();
                    actualizarNumeracion();
                    calcularTotal();
                }
            });

            cantidadModal.hide();
            calcularTotal();
        }

        function actualizarNumeracion() {
            const rows = document.getElementById('productos-cotizacion-body').rows;
            for (let i = 0; i < rows.length; i++) {
                rows[i].cells[0].textContent = i + 1;
            }
        }

        function calcularTotal() {
            const rows = document.getElementById('productos-cotizacion-body').rows;
            let total = 0;

            for (let i = 0; i < rows.length; i++) {
                const subtotal = parseFloat(rows[i].cells[5].textContent.replace('$', ''));
                total += subtotal;
            }

            document.getElementById('total-cotizacion').textContent = `$${total.toFixed(2)}`;
        }

        function guardarCotizacion() {
            const productos = [];
            const rows = document.getElementById('productos-cotizacion-body').rows;

            if (rows.length === 0) {
                alert('No hay productos en la cotización');
                return;
            }

            for (let i = 0; i < rows.length; i++) {
                productos.push({
                    nombre: rows[i].cells[1].textContent,
                    proveedor: rows[i].cells[2].textContent,
                    cantidad: rows[i].cells[3].textContent,
                    precio: rows[i].cells[4].textContent.replace('$', ''),
                    subtotal: rows[i].cells[5].textContent.replace('$', '')
                });
            }

            const total = document.getElementById('total-cotizacion').textContent.replace('$', '');
            const cotizacion = {
                proveedor: proveedorSeleccionado,
                litrosPintura: litrosPintura,
                areaPintura: areaPintura,
                productos: productos,
                total: total,
                fecha: new Date().toLocaleDateString()
            };

            // Aquí puedes enviar la cotización al servidor o guardarla localmente
            console.log('Cotización a guardar:', cotizacion);
            alert('Cotización generada correctamente');

            // Opcional: Redirigir a una página de resumen
            // window.location.href = `resumen_cotizacion.php?data=${encodeURIComponent(JSON.stringify(cotizacion))}`;
        }
    </script>
</body>

</html>