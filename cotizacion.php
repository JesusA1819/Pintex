<?php
session_start(); // Inicia la sesión

// Configuración de la conexión a la base de datos
if (!file_exists('includes.php')) {
    die("Archivo de configuración no encontrado");
}

require 'includes.php';
// Verificar si se ha seleccionado un proveedor
$proveedor_id = isset($_GET['proveedor_id']) ? intval($_GET['proveedor_id']) : 0;
$litrosPintura = isset($_GET['litros']) ? floatval($_GET['litros']) : 0;
$areaPintura = isset($_GET['area']) ? floatval($_GET['area']) : 0;
$tipoPintura = isset($_GET['tipoPintura']) ? htmlspecialchars($_GET['tipoPintura']) : '';
$tipoProducto = isset($_GET['tipoProducto']) ? htmlspecialchars($_GET['tipoProducto']) : '';

// Obtener parámetros de búsqueda
$busqueda_proveedor = isset($_GET['busqueda_proveedor']) ? trim($_GET['busqueda_proveedor']) : '';
$colonia = isset($_GET['colonia']) ? trim($_GET['colonia']) : '';

// Consulta para obtener todos los proveedores con filtros
$query_proveedores = "SELECT * FROM proveedores WHERE 1=1";
$params = [];
$types = '';

if (!empty($busqueda_proveedor)) {
    $query_proveedores .= " AND (nombre LIKE ?)";
    $params[] = "%$busqueda_proveedor%";
    $types .= 'ss';
}

if (!empty($colonia)) {
    $query_proveedores .= " AND colonia LIKE ?";
    $params[] = "%$colonia%";
    $types .= 's';
}

$query_proveedores .= " ORDER BY nombre";

$stmt_proveedores = $conexion->prepare($query_proveedores);
if (!empty($params)) {
    $stmt_proveedores->bind_param($types, ...$params);
}
$stmt_proveedores->execute();
$result_proveedores = $stmt_proveedores->get_result();
$proveedores = $result_proveedores->fetch_all(MYSQLI_ASSOC);

// Si se ha seleccionado un proveedor, obtener sus productos
if ($proveedor_id > 0) {
    // Consulta para obtener información del proveedor seleccionado
    $stmt_proveedor = $conexion->prepare("SELECT * FROM proveedores WHERE id = ?");
    $stmt_proveedor->bind_param('i', $proveedor_id);
    $stmt_proveedor->execute();
    $result_proveedor = $stmt_proveedor->get_result();
    $proveedor = $result_proveedor->fetch_assoc();

    if (!$proveedor) {
        die("Proveedor no encontrado");
    }
// Parámetros básicos
$busqueda_producto = isset($_GET['busqueda_producto']) ? trim($_GET['busqueda_producto']) : '';
$proveedor_id = isset($_GET['proveedor_id']) ? (int)$_GET['proveedor_id'] : 0;

$limite_por_pagina = 10; // Número de elementos por página
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaActual - 1) * $limite_por_pagina;

// Consulta para obtener los productos
$query_productos = "SELECT p.*, pr.nombre as proveedor_nombre 
                   FROM productos p
                   JOIN proveedores pr ON p.provedor_id = pr.id
                   WHERE p.provedor_id = ?";
$params_productos = [$proveedor_id];
$types_productos = 'i';

if (!empty($busqueda_producto)) {
    $query_productos .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ? OR p.sku LIKE ?)";
    $params_productos[] = "%$busqueda_producto%";
    $params_productos[] = "%$busqueda_producto%";
    $params_productos[] = "%$busqueda_producto%";
    $types_productos .= 'sss';
}

$query_productos .= " ORDER BY p.nombre LIMIT ? OFFSET ?";
$params_productos[] = $limite_por_pagina;
$params_productos[] = $offset;
$types_productos .= 'ii';

$stmt_productos = $conexion->prepare($query_productos);
$stmt_productos->bind_param($types_productos, ...$params_productos);
$stmt_productos->execute();
$result_productos = $stmt_productos->get_result();
$productos = $result_productos->fetch_all(MYSQLI_ASSOC);

// Consulta para obtener las pinturas
$query_pinturas = "SELECT id, marca, tamano, precio, tipo, rol FROM pinturas WHERE proveedor_id = ? LIMIT ? OFFSET ?";
$params_pinturas = [$proveedor_id, $limite_por_pagina, $offset];
$types_pinturas = 'iii';

$stmt_pinturas = $conexion->prepare($query_pinturas);
$stmt_pinturas->bind_param($types_pinturas, ...$params_pinturas);
$stmt_pinturas->execute();
$result_pinturas = $stmt_pinturas->get_result();
$pinturas = $result_pinturas->fetch_all(MYSQLI_ASSOC);

// Combinamos productos y pinturas
$items = array_merge($productos, $pinturas);

// Calculamos el total de elementos para la paginación
$query_count_productos = "SELECT COUNT(*) FROM productos WHERE provedor_id = ?";
$stmt_count_productos = $conexion->prepare($query_count_productos);
$stmt_count_productos->bind_param('i', $proveedor_id);
$stmt_count_productos->execute();
$result_count_productos = $stmt_count_productos->get_result();
$total_productos = $result_count_productos->fetch_row()[0];

$query_count_pinturas = "SELECT COUNT(*) FROM pinturas WHERE proveedor_id = ?";
$stmt_count_pinturas = $conexion->prepare($query_count_pinturas);
$stmt_count_pinturas->bind_param('i', $proveedor_id);
$stmt_count_pinturas->execute();
$result_count_pinturas = $stmt_count_pinturas->get_result();
$total_pinturas = $result_count_pinturas->fetch_row()[0];

$total_items = $total_productos + $total_pinturas;
$totalPaginas = ceil($total_items / $limite_por_pagina);

}
// Logica para determinar la cantidad de brochas y pinturas a agregar al carrito
$brochas_a_agregar = ceil($litrosPintura / 20);  // Cada 20 litros, 1 brocha
$litros_a_agregar = $litrosPintura;  // La cantidad de pintura es el mismo número de litros

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🖌 Proveedores • Pintex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link href="style1.css" rel="stylesheet">
    <link rel="icon" href="pintex.ico" type="image/x-icon">
    <!-- jsPDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<!-- jsPDF AutoTable plugin -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>

<style>
    .producto-imagen {
        min-width: 100px; /* Ancho mínimo para la imagen */
        text-align: right; /* Alinea la imagen a la derecha */
    }
    
    .producto-imagen img {
        max-width: 100px;
        max-height: 100px;
        object-fit: cover;
        width: 100%;
        height: auto;
    }
    
    .producto-card {
        padding: 15px;
    }
    
    .producto-acciones {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
    }
</style>
</head>

<body>
    <div class="header">
        <div class="container">
            <h1><i class="fas fa-paint-roller"></i> Sistema de Cotizaciones - Pintex®</h1>
            <p class="mb-0">
                <?php echo $proveedor_id > 0 ? 'Catálogo de ' . htmlspecialchars($proveedor['nombre']) : 'Seleccione un proveedor para comenzar'; ?>
            </p>
        </div>
    </div>

    <div class="container">
        <?php if ($proveedor_id === 0): ?>
            <!-- Sección de búsqueda de proveedores -->
            <div class="search-section mb-4">
                <h3 class="search-title"><i class="fas fa-search"></i> Buscar Proveedores</h3>
                <form method="get" action="" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="search-input-container">
                                <i class="fas fa-building"></i>
                                <input type="text" class="search-input form-control" id="buscar-proveedor"
                                    name="busqueda_proveedor" placeholder="Buscar por nombre o descripción..."
                                    value="<?php echo htmlspecialchars($busqueda_proveedor); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="search-input-container">
                                <i class="fas fa-map-marker-alt"></i>
                                <input type="text" class="search-input form-control" id="buscar-colonia" name="colonia"
                                    placeholder="Filtrar por colonia o dirección..."
                                    value="<?php echo htmlspecialchars($colonia); ?>">
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="search-btn btn btn-primary">
                                <i class="fas fa-filter"></i> Aplicar Filtros
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <h2 class="mb-4">Proveedores Disponibles: </h2>
            <?php
            $tiposPintura = [
                'interior' => 'Pintura Interior',
                'exterior' => 'Pintura Exterior',
                // más tipos si aplican
            ];

            $tiposProducto = [
                'vinilica' => 'Vinílica',
                'esmalte' => 'Esmalte',
                // más tipos si aplican
            ];
            ?>
            <div class="mt-2 mt-md-0">
                <p class="mb-1"><strong><i class="fas fa-ruler-combined"></i> Área a pintar:</strong>
                    <?php echo number_format($areaPintura, 2); ?> m²</p>
                <p class="mb-1"><strong><i class="fas fa-paint-roller"></i> Tipo:</strong>
                    <?php echo isset($tiposPintura[$tipoPintura]) ? $tiposPintura[$tipoPintura] : 'N/A'; ?>
                    -
                    <?php echo isset($tiposProducto[$tipoProducto]) ? $tiposProducto[$tipoProducto] : 'N/A'; ?>
                </p>
                <p class="mb-0"><strong><i class="fas fa-tint"></i> Litros necesarios:</strong>
                    <span id="litros-necesarios" class="badge badge-litros"
                        style='color: black;'><?php echo number_format($litrosPintura, 1); ?></span>

                </p>
            </div>
            <?php if (count($proveedores) === 0): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> No se encontraron proveedores con los criterios de búsqueda
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($proveedores as $prov): ?>
                        <div class="col">
                            <div class="proveedor-card h-100">
                                <img src="<?php echo !empty($prov['logo']) ? htmlspecialchars($prov['logo']) : 'https://via.placeholder.com/100?text=' . urlencode(substr($prov['nombre'], 0, 1)); ?>"
                                    alt="<?php echo htmlspecialchars($prov['nombre']); ?>" class="proveedor-logo img-fluid">
                                <h3 class="proveedor-nombre"><?php echo htmlspecialchars($prov['nombre']); ?></h3>
                                <p class="proveedor-info"><i
                                        class="fas fa-phone me-2"></i><?php echo htmlspecialchars($prov['telefono'] ?? 'N/A'); ?>
                                </p>
                                <p class="proveedor-info"><i
                                        class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($prov['direccion'] ?? 'N/A'); ?>
                                </p>
                                <p class="proveedor-descripcion">
                                    <?php echo htmlspecialchars($prov['descripcion'] ?? 'Sin descripción'); ?>
                                </p>
                                <button class="btn btn-sm btn-link"
                                    onclick="this.previousElementSibling.style.webkitLineClamp='unset'; this.style.display='none';">Leer
                                    más</button>

                                <a href="?proveedor_id=<?php echo $prov['id']; ?>&<?php echo http_build_query($_GET); ?>"
                                    class="btn btn-cotizar">
                                    <i class="fas fa-clipboard-list" onclick="calcularMinimo(<?php echo $prov['id']; ?>)"></i> Ver
                                    Catálogo
                                </a>


                            </div>

                            <?php
                            // CONSULTA DE PRODUCTOS POR PROVEEDOR
                            $query = "SELECT s.*, a.* 
                                FROM productos s
                                INNER JOIN pinturas a ON s.id = a.producto_id 
                                WHERE s.provedor_id = " . intval($prov['id']);

                            $result = mysqli_query($conexion, $query);
                            ?>
                        </div>
                    <?php endforeach; ?>

                </div>
            <?php endif; ?>

        <?php else: ?>
            <?php
            // Obtener todos los parámetros del GET
            $queryParams = $_GET;
            unset($queryParams['proveedor_id']); // Eliminar el parámetro proveedor_id
        
            // Construir la URL de regreso sin proveedor_id
            $urlRegresar = '?' . http_build_query($queryParams);
            ?>

            <!-- Botón para regresar a proveedores -->
            <a href="<?php echo $urlRegresar; ?>" class="btn btn-regresar">
                <i class="fas fa-arrow-left"></i> Regresar a proveedores
            </a>

            <!-- Información del proveedor -->
            <div class="proveedor-info bg-white p-4 rounded shadow-sm mb-4">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        <img src="<?php echo !empty($proveedor['logo'])
                            ? htmlspecialchars($proveedor['logo'])
                            : 'https://via.placeholder.com/100?text=' . urlencode(substr($proveedor['nombre'], 0, 1)); ?>"
                            alt="<?php echo htmlspecialchars($proveedor['nombre']); ?>" class="img-fluid rounded-circle"
                            style="max-width: 80px;">
                    </div>
                    <div class="col-md-10">
                        <div class="row">
                            <div class="col-md-6">
                                <h3><i
                                        class="fas fa-building me-2"></i><?php echo htmlspecialchars($proveedor['nombre']); ?>
                                </h3>
                                <p><i
                                        class="fas fa-phone me-2"></i><?php echo htmlspecialchars($proveedor['telefono'] ?? 'N/A'); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><i
                                        class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($proveedor['direccion'] ?? 'N/A'); ?>
                                </p>
                                <p><i
                                        class="fas fa-info-circle me-2"></i><?php echo htmlspecialchars($proveedor['descripcion'] ?? 'Sin descripción'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>


            <!-- Sección de búsqueda de productos -->
            <div class="search-section mb-4">
                <h3 class="search-title"><i class="fas fa-search"></i> Buscar Productos</h3>
                <form method="get" action="" class="needs-validation" novalidate>
                    <input type="hidden" name="proveedor_id" value="<?php echo $proveedor_id; ?>">
                    <div class="row g-3">
                        <div class="col-md-10">
                            <div class="search-input-container">
                                <i class="fas fa-box-open"></i>
                                <input type="text" class="search-input form-control" name="busqueda_producto"
                                    placeholder="Buscar por nombre, descripción o SKU..."
                                    value="<?php echo htmlspecialchars($busqueda_producto); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="search-btn btn btn-primary w-100">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Sección de filtros -->
            <div class="filters-section mb-4">
                <h3 class="search-title"><i class="fas fa-sliders-h"></i> Filtros</h3>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="filter-group">
                            <label for="filtro-categoria" class="filter-label">Categoría</label>
                            <select class="filter-select form-select" id="filtro-categoria">
                                <option value="" selected>Todas las categorías</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo htmlspecialchars($categoria); ?>">
                                        <?php echo htmlspecialchars($categoria); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="filter-group">
                            <label for="filtro-tipo" class="filter-label">Tipo</label>
                            <select class="filter-select form-select" id="filtro-tipo">
                                <option value="" selected>Todos los tipos</option>
                                <option value="1">Interior</option>
                                <option value="2">Exterior</option>
                                <option value="3">Mate</option>
                                <option value="4">Brillante</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="filter-group">
                            <label for="filtro-orden" class="filter-label">Ordenar por</label>
                            <select class="filter-select form-select" id="filtro-orden">
                                <option value="nombre" selected>Nombre (A-Z)</option>
                                <option value="precio_asc">Menor precio</option>
                                <option value="precio_desc">Mayor precio</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botón para ver el carrito -->
            <div class="d-flex justify-content-end mb-4">
                <button class="btn btn-carrito" id="ver-carrito">
                    <i class="fas fa-shopping-cart"></i> Ver Carrito
                    <span class="contador-carrito" id="contador-carrito">0</span>
                </button>
            </div>
<!-- Listado de productos y pinturas -->
<div class="row row-cols-1 row-cols-md-2 g-4" id="productos-container">
    <?php if (count($items) === 0): ?>
        <div class="col-12">
            <div class="alert alert-info text-center py-4">
                <i class="fas fa-info-circle me-2"></i> No se encontraron productos o pinturas con los criterios de búsqueda.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($items as $item): ?>
            <div class="col producto-item" data-categoria="<?php echo htmlspecialchars($item['categoria'] ?? ''); ?>" data-tipo="<?php echo htmlspecialchars($item['tipo'] ?? ''); ?>">
                <div class="producto-card h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between">
                        <div class="flex-grow-1">
                            <h3 class="producto-titulo"><?php echo htmlspecialchars($item['nombre'] ?? $item['marca']); ?></h3>
                            <p class="producto-descripcion"><?php echo htmlspecialchars($item['descripcion'] ?? $item['tamano'] ?? ''); ?></p>

                            <?php if (isset($item['rating'])): ?>
                                <div class="d-flex align-items-center mb-2">
                                    <div class="rating-stars">
                                        <?php
                                        $rating = $item['rating'];
                                        $fullStars = floor($rating);
                                        $halfStar = ($rating - $fullStars) >= 0.5;

                                        for ($i = 0; $i < $fullStars; $i++) {
                                            echo '<i class="fas fa-star"></i>';
                                        }

                                        if ($halfStar) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        }

                                        $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                                        for ($i = 0; $i < $emptyStars; $i++) {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                        ?>
                                    </div>
                                    <span class="rating-count ms-2"><?php echo number_format($rating, 1); ?>
                                        (<?php echo $item['reviews'] ?? 0; ?>)</span>
                                </div>
                            <?php endif; ?>

                            <!-- SKU o "N/A" si no existe -->
                            <p class="producto-sku">SKU: <?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></p>

                            <!-- Precio, asegurándonos de redondear a dos decimales -->
                            <p class="producto-precio">$<?php echo number_format($item['precio'], 2); ?></p>
                        </div>

                        <!-- Mostrar imagen o texto si no hay imagen -->
                        <div class="producto-imagen ms-3">
                            <?php if (!empty($item['imagen'])): ?>
                                <img src="<?php echo htmlspecialchars($item['imagen']); ?>" alt="<?php echo htmlspecialchars($item['nombre'] ?? $item['marca']); ?>" class="img-fluid">
                            <?php else: ?>
                                <h2>Pintura</h2>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="producto-acciones mt-auto">
                        <div class="quantity-control">
                            <button class="quantity-btn">-</button>
                            <input type="number" class="quantity-input" value="1" min="1">
                            <button class="quantity-btn">+</button>
                        </div>
                        <!-- Botón Agregar -->
                        <button class="add-btn" 
                            data-id="<?php echo $item['id']; ?>"
                            data-nombre="<?php echo htmlspecialchars($item['nombre'] ?? $item['marca']); ?>"
                            data-precio="<?php echo number_format($item['precio'], 2); ?>"
                            data-proveedor="<?php echo htmlspecialchars($proveedor['nombre']); ?>">
                            <i class="fas fa-cart-plus"></i>
                            <span>Agregar</span>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>


<!-- Paginación -->
<nav aria-label="Page navigation" class="mt-4">
    <ul class="pagination justify-content-center">
        <li class="page-item <?php echo ($paginaActual <= 1) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?pagina=<?php echo $paginaActual - 1; ?>" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <li class="page-item <?php echo ($i == $paginaActual) ? 'active' : ''; ?>">
                <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?php echo ($paginaActual >= $totalPaginas) ? 'disabled' : ''; ?>">
            <a class="page-link" href="?pagina=<?php echo $paginaActual + 1; ?>" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    </ul>
</nav>



        <?php endif; ?>
    </div>

    <!-- Modal del carrito -->
    <div class="modal fade" id="carritoModal" tabindex="-1" aria-labelledby="carritoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="carritoModalLabel"><i class="fas fa-shopping-cart"></i> Tu Carrito de
                        Cotización</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="carrito-contenido">
                        <p class="text-center py-4">No hay productos en el carrito</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Seguir comprando</button>
                    <button type="button" class="btn btn-success" id="finalizar-compra">
                        <i class="fas fa-file-pdf"></i> Generar Cotización
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenedor para notificaciones -->
    <div class="alert-container" id="alert-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales
        let productosGlobal = [];
        let pinturasGlobal = [];
        let litrosPinturaGlobal = <?php echo json_encode($litrosPintura ?? 0); ?>;

        // Función para cargar datos del proveedor via AJAX
        function cargarDatosProveedor(proveedor_id, callback) {
            // Si ya tenemos los datos, usarlos directamente
            if (productosGlobal.length > 0 && pinturasGlobal.length > 0) {
                if (callback) callback();
                return;
            }

            // Hacer petición AJAX para obtener los datos
            fetch(`obtener_datos_proveedor.php?proveedor_id=${proveedor_id}`)
                .then(response => response.json())
                .then(data => {
                    productosGlobal = data.productos;
                    pinturasGlobal = data.pinturas;
                    if (callback) callback();
                })
                .catch(error => {
                    console.error('Error al cargar datos del proveedor:', error);
                });
            }
            // Función principal que recibe el proveedor_id
            function calcularMinimo(proveedor_id) {
                // Cargar datos del proveedor
                cargarDatosProveedor(proveedor_id, function() {
                    // Vaciar completamente el carrito antes de empezar
                    carrito = []; // Vaciar la variable global
                    localStorage.setItem('carrito', JSON.stringify(carrito)); // Vaciar localStorage
                    
                    // Producto predeterminado: Brocha
                    const brocha = productosGlobal.find(producto => 
                        producto.producto_nombre.toLowerCase().includes('brocha'));

                    if (brocha) {
                        carrito.push({
                            id: brocha.id,
                            nombre: brocha.producto_nombre || brocha.nombre,
                            precio: parseFloat(brocha.precio).toFixed(2),
                            proveedor: brocha.nombre,
                            cantidad: 1,
                            Litros: 0,
                            tamano: null
                        });
                    }

                    let litrosRestantes = litrosPinturaGlobal;
                    const carritoPinturas = [];
                    
                    // Ordenar pinturas por tamaño (de mayor a menor)
                    pinturasGlobal.sort((a, b) => b.tamano - a.tamano);
                    
                    // Calcular cuántas pinturas de cada tamaño necesitamos
                    pinturasGlobal.forEach(pintura => {
                        if (litrosRestantes <= 0) return;
                        
                        let precio = parseFloat(pintura.precio);
                        if (isNaN(precio) || precio <= 0) {
                            console.error('Precio inválido para la pintura', pintura);
                            return;
                        }

                        const unidades = Math.floor(litrosRestantes / pintura.tamano);
                        if (unidades > 0) {
                            carritoPinturas.push({
                                id: pintura.id,
                                nombre: 'Pintura ' + pintura.tamano + 'L',
                                precio: precio.toFixed(2),
                                proveedor: pintura.nombre,
                                cantidad: unidades,
                                Litros: pintura.tamano * unidades,
                                tamano: pintura.tamano
                            });
                            litrosRestantes -= pintura.tamano * unidades;
                        }
                    });

                    // Si quedan litros sin cubrir, agregar una unidad de la pintura más pequeña
                    if (litrosRestantes > 0 && pinturasGlobal.length > 0) {
                        const pinturaMasPequena = pinturasGlobal[pinturasGlobal.length - 1];
                        carritoPinturas.push({
                            id: pinturaMasPequena.id,
                            nombre: 'Pintura ' + pinturaMasPequena.tamano + 'L',
                            precio: pinturaMasPequena.precio.toFixed(2),
                            proveedor: pinturaMasPequena.nombre,
                            cantidad: 1,
                            Litros: pinturaMasPequena.tamano,
                            tamano: pinturaMasPequena.tamano
                        });
                    }

                    // Agregar todas las pinturas al carrito (no hay duplicados porque el carrito está vacío)
                    carritoPinturas.forEach(pintura => {
                        carrito.push(pintura);
                    });

                    // Guardar el carrito actualizado
                    localStorage.setItem('carrito', JSON.stringify(carrito));

                    // Actualizar interfaz
                    actualizarCarrito();
                    actualizarContadorCarrito();
                    
                    // Mostrar notificación
                    mostrarNotificacion('Carrito actualizado con los productos necesarios');
                });
            }
                    // Cargar datos iniciales si hay proveedor_id en la URL
                    <?php if (isset($_GET['proveedor_id'])): ?>
                        document.addEventListener('DOMContentLoaded', function () {
                            calcularMinimo(<?php echo $_GET['proveedor_id']; ?>);
                        });
                    <?php endif; ?>
                    // Carrito de compras
                    let carrito = JSON.parse(localStorage.getItem('carrito')) || [];

                    // Actualizar contador del carrito
                    function actualizarContadorCarrito() {
                        const totalItems = carrito.reduce((total, item) => total + item.cantidad, 0);
                        document.getElementById('contador-carrito').textContent = totalItems;
                    }

                    // Mostrar notificación
                    function mostrarNotificacion(mensaje, tipo = 'success') {
                        const alertContainer = document.getElementById('alert-container');
                        const alert = document.createElement('div');
                        alert.className = `alert alert-${tipo} alert-dismissible fade show`;
                        alert.role = 'alert';
                        alert.innerHTML = `
                            <i class="fas fa-${tipo === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                            ${mensaje}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;

                        alertContainer.appendChild(alert);

                        // Eliminar la notificación después de 3 segundos
                        setTimeout(() => {
                            alert.remove();
                        }, 3000);
                    }

                    // Funcionalidad para los controles de cantidad
                    document.querySelectorAll('.quantity-btn').forEach(btn => {
                        btn.addEventListener('click', function () {
                            const input = this.parentNode.querySelector('.quantity-input');
                            let value = parseInt(input.value);

                            if (this.textContent === '+') {
                                input.value = value + 1;
                            } else {
                                if (value > 1) {
                                    input.value = value - 1;
                                }
                            }

                            // Disparar evento change para actualizar
                            const event = new Event('change');
                            input.dispatchEvent(event);
                        });
                    });

                    // Validación del input de cantidad
                    document.querySelectorAll('.quantity-input').forEach(input => {
                        input.addEventListener('change', function () {
                            if (this.value < 1 || isNaN(this.value)) {
                                this.value = 1;
                            }
                        });
                    });

                    document.querySelectorAll('.add-btn').forEach(boton => {
                boton.addEventListener('click', function () {
                    const id = this.dataset.id;
                    const nombre = this.dataset.nombre;
                    const precio = parseFloat(this.dataset.precio);
                    const proveedor = this.dataset.proveedor;

                    // Obtener la cantidad seleccionada
                    const cantidadInput = this.closest('.producto-acciones').querySelector('.quantity-input');
                    const cantidad = parseInt(cantidadInput.value) || 1;

                    // Buscar si ya existe en el carrito
                    const index = carrito.findIndex(item => item.id === id);
                    if (index !== -1) {
                        carrito[index].cantidad += cantidad;
                    } else {
                        carrito.push({ id, nombre, precio, proveedor, cantidad });
                    }

                    // Guardar en localStorage
                    localStorage.setItem('carrito', JSON.stringify(carrito));

                    // Actualizar UI
                    actualizarContadorCarrito();
                    mostrarNotificacion('Producto agregado al carrito', 'success');
                });
            });


        // Funcionalidad para los filtros
        document.querySelectorAll('.filter-select').forEach(select => {
            select.addEventListener('change', function () {
                const categoria = document.getElementById('filtro-categoria').value;
                const tipo = document.getElementById('filtro-tipo').value;
                const orden = document.getElementById('filtro-orden').value;

                document.querySelectorAll('.producto-item').forEach(item => {
                    const itemCategoria = item.dataset.categoria;
                    const itemTipo = item.dataset.tipo;

                    const cumpleCategoria = !categoria || itemCategoria === categoria;
                    const cumpleTipo = !tipo || itemTipo === tipo;

                    if (cumpleCategoria && cumpleTipo) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });

                if (orden === 'precio_asc' || orden === 'precio_desc') {
                    const container = document.getElementById('productos-container');
                    const items = Array.from(document.querySelectorAll('.producto-item:not([style*="display: none"])'));

                    items.sort((a, b) => {
                        const precioA = parseFloat(a.querySelector('.producto-precio').textContent.replace('$', ''));
                        const precioB = parseFloat(b.querySelector('.producto-precio').textContent.replace('$', ''));

                        return orden === 'precio_asc' ? precioA - precioB : precioB - precioA;
                    });

                    // Limpiar el contenedor
                    container.innerHTML = '';

                    // Agregar los items ordenados
                    items.forEach(item => container.appendChild(item));
                }
            });
        });
        function actualizarCarrito() {
            const carritoContenido = document.getElementById('carrito-contenido');

            if (carrito.length === 0) {
                carritoContenido.innerHTML = '<p class="text-center py-4">No hay productos en el carrito</p>';
                return;
            }

            let html = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>imagen</th>
                        <th>Producto/Pintura</th>
                        <th>Proveedor</th>
                        <th>Precio Unitario</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
    `;

            let total = 0;

            carrito.forEach((item, index) => {
                const subtotal = Number(item.precio) * Number(item.cantidad);
                total += subtotal;

                html += `
            <tr>
                <td>${item.id}</td>
                <td>${item.nombre}</td>
                <td>${item.proveedor || 'N/A'}</td>
                <td>$${Number(item.precio).toFixed(2)}</td>
                <td>${item.cantidad}</td>
                <td>$${subtotal.toFixed(2)}</td>
                <td>
                    <button class="btn btn-sm btn-danger btn-eliminar" data-index="${index}" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
            });

            html += `
                </tbody>
                <tfoot class="table-group-divider">
                    <tr>
                        <td colspan="5" class="text-end fw-bold">Total:</td>
                        <td colspan="2" class="fw-bold">$${total.toFixed(2)}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    `;

            carritoContenido.innerHTML = html;

            // Agregar eventos de eliminación (mantén tu código actual)
            document.querySelectorAll('.btn-eliminar').forEach(btn => {
                btn.addEventListener('click', function () {
                    const index = parseInt(this.dataset.index);
                    if (!isNaN(index) && index >= 0 && index < carrito.length) {
                        carrito.splice(index, 1);
                        localStorage.setItem('carrito', JSON.stringify(carrito));
                        actualizarContadorCarrito();
                        actualizarCarrito();
                        mostrarNotificacion('Producto eliminado del carrito', 'info');
                    }
                });
            });
        }
        // Mostrar el carrito
        document.getElementById('ver-carrito').addEventListener('click', function () {
            const carritoModal = new bootstrap.Modal(document.getElementById('carritoModal'));
            actualizarCarrito();
            carritoModal.show();
        });

        document.getElementById('finalizar-compra').addEventListener('click', function () {
            if (carrito.length === 0) {
                mostrarNotificacion('No hay productos en el carrito', 'warning');
                return;
            }

            // Generar PDF
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            doc.setFontSize(16);
            doc.text('Cotización de Productos', 14, 20);

            const fecha = new Date().toLocaleDateString();
            doc.setFontSize(10);
            doc.text(`Fecha: ${fecha}`, 14, 27);

            const tabla = carrito.map(item => [
                item.id,
                item.nombre,
                item.proveedor || 'N/A',
                `$${Number(item.precio).toFixed(2)}`,
                item.cantidad,
                `$${(item.precio * item.cantidad).toFixed(2)}`
            ]);

            doc.autoTable({
                head: [['ID', 'Producto', 'Proveedor', 'Precio Unitario', 'Cantidad', 'Subtotal']],
                body: tabla,
                startY: 35
            });

            const total = carrito.reduce((acc, item) => acc + item.precio * item.cantidad, 0);
            doc.text(`Total: $${total.toFixed(2)}`, 14, doc.lastAutoTable.finalY + 10);

            doc.save('cotizacion.pdf');

            // Limpiar el carrito después de generar
            carrito = [];
            localStorage.removeItem('carrito');
            actualizarContadorCarrito();
            actualizarCarrito();

            // Cerrar el modal
            const carritoModal = bootstrap.Modal.getInstance(document.getElementById('carritoModal'));
            carritoModal.hide();

            mostrarNotificacion('Cotización generada con éxito! Se ha descargado un PDF con los detalles.');
        });

        // Inicializar contador del carrito
        actualizarContadorCarrito();

        // Inicializar tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

    </script>
</body>

</html>