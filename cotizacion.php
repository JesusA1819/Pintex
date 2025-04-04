<?php
// Configuración de la conexión a la base de datos
if (!file_exists('includes.php')) {
    die("Archivo de configuración no encontrado");
}

require 'includes.php';

// Verificar si se ha seleccionado un proveedor
$proveedor_id = isset($_GET['proveedor_id']) ? intval($_GET['proveedor_id']) : 0;

// Obtener parámetros de búsqueda
$busqueda_proveedor = isset($_GET['busqueda_proveedor']) ? trim($_GET['busqueda_proveedor']) : '';
$colonia = isset($_GET['colonia']) ? trim($_GET['colonia']) : '';

// Consulta para obtener todos los proveedores con filtros
$query_proveedores = "SELECT * FROM proveedores WHERE 1=1";
$params = [];
$types = '';

if (!empty($busqueda_proveedor)) {
    $query_proveedores .= " AND (nombre LIKE ? OR descripcion LIKE ?)";
    $params[] = "%$busqueda_proveedor%";
    $params[] = "%$busqueda_proveedor%";
    $types .= 'ss';
}

if (!empty($colonia)) {
    $query_proveedores .= " AND direccion LIKE ?";
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

    // Consulta para obtener los productos del proveedor
    $busqueda_producto = isset($_GET['busqueda_producto']) ? trim($_GET['busqueda_producto']) : '';
    
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
    
    $query_productos .= " ORDER BY p.nombre";
    
    $stmt_productos = $conexion->prepare($query_productos);
    $stmt_productos->bind_param($types_productos, ...$params_productos);
    $stmt_productos->execute();
    $result_productos = $stmt_productos->get_result();
    $productos = $result_productos->fetch_all(MYSQLI_ASSOC);

    // Consulta para obtener categorías disponibles
    $stmt_categorias = $conexion->prepare("
        SELECT DISTINCT categoria 
        FROM productos 
        WHERE provedor_id = ? AND categoria IS NOT NULL
    ");
    $stmt_categorias->bind_param('i', $proveedor_id);
    $stmt_categorias->execute();
    $result_categorias = $stmt_categorias->get_result();
    $categorias = array_column($result_categorias->fetch_all(), 0);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Proveedores - Vinimex®</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --color-primario: #2c3e50;
            --color-secundario: #3498db;
            --color-terciario: #e74c3c;
            --color-fondo: #f8f9fa;
            --color-texto: #333;
            --texto-oscuro: #2c3e50;
            --sombra: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transicion: all 0.3s ease;
            --border-radius: 12px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--color-fondo);
            color: var(--color-texto);
        }
        
        .header {
            background: linear-gradient(135deg, var(--color-primario), var(--color-secundario));
            color: white;
            padding: 25px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: var(--sombra);
        }
        
        /* Estilos para la lista de proveedores */
        .proveedor-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: var(--sombra);
            transition: var(--transicion);
            height: 100%;
            display: flex;
            flex-direction: column;
            border: 1px solid #e0e0e0;
        }
        
        .proveedor-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .proveedor-logo {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin: 0 auto 15px;
            border-radius: 50%;
            background-color: #f8f9fa;
            padding: 10px;
            border: 2px solid #eee;
        }
        
        .proveedor-nombre {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--texto-oscuro);
            margin-bottom: 10px;
            text-align: center;
        }
        
        .proveedor-info {
            font-size: 0.95rem;
            color: #555;
            margin-bottom: 8px;
        }
        
        .proveedor-descripcion {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 15px;
            flex-grow: 1;
        }
        
        .btn-cotizar {
            background-color: var(--color-secundario);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: var(--transicion);
            width: 100%;
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-cotizar:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        /* Estilos para los buscadores */
        .search-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--sombra);
            border: 1px solid #e0e0e0;
        }
        
        .search-title {
            font-size: 1.2rem;
            color: var(--color-primario);
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .search-title i {
            margin-right: 10px;
            color: var(--color-secundario);
        }
        
        .search-input-container {
            position: relative;
            margin-bottom: 15px;
        }
        
        .search-input-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
            z-index: 2;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            font-size: 1rem;
            transition: var(--transicion);
            background-color: #f8f9fa;
        }
        
        .search-input:focus {
            border-color: var(--color-secundario);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            background-color: white;
            outline: none;
        }
        
        .search-btn {
            background-color: var(--color-secundario);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: var(--transicion);
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .search-btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        /* Estilos para los filtros */
        .filters-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--sombra);
            border: 1px solid #e0e0e0;
        }
        
        .filter-group {
            margin-bottom: 20px;
        }
        
        .filter-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--color-primario);
        }
        
        .filter-select {
            width: 100%;
            padding: 10px 15px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            background-color: #f8f9fa;
            font-size: 1rem;
            transition: var(--transicion);
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 1em;
        }
        
        .filter-select:focus {
            border-color: var(--color-secundario);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            background-color: white;
            outline: none;
        }
        
        /* Estilos para los productos */
        .producto-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--sombra);
            margin-bottom: 25px;
            padding: 20px;
            transition: var(--transicion);
            border-left: 4px solid var(--color-secundario);
            border: 1px solid #e0e0e0;
        }
        
        .producto-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .producto-titulo {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--texto-oscuro);
            margin-bottom: 5px;
        }
        
        .producto-descripcion {
            color: #555;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }
        
        .producto-rating {
            color: #ffc107;
            margin-bottom: 10px;
            font-size: 1rem;
        }
        
        .producto-sku {
            font-size: 0.85rem;
            color: #777;
            margin-bottom: 10px;
            background: #f8f9fa;
            padding: 3px 8px;
            border-radius: 4px;
            display: inline-block;
        }
        
        .producto-precio {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-secundario);
            margin: 15px 0;
        }
        
        .producto-acciones {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #eee;
            padding-top: 15px;
            margin-top: 15px;
        }
        
        /* Estilos para los controles de cantidad */
        .quantity-control {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e0e0e0;
        }
        
        .quantity-btn {
            width: 40px;
            height: 40px;
            background-color: #e0e0e0;
            color: var(--texto-oscuro);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transicion);
            font-size: 1.1rem;
        }
        
        .quantity-btn:hover {
            background-color: #d0d0d0;
        }
        
        .quantity-btn:active {
            transform: scale(0.95);
        }
        
        .quantity-input {
            width: 50px;
            height: 40px;
            text-align: center;
            border: none;
            border-left: 2px solid #e0e0e0;
            border-right: 2px solid #e0e0e0;
            font-size: 1rem;
            font-weight: 600;
            color: var(--texto-oscuro);
            background: white;
        }
        
        .quantity-input:focus {
            outline: none;
        }
        
        /* Estilos para el botón agregar */
        .add-btn {
            background-color: var(--color-secundario);
            color: white;
            border: none;
            padding: 0 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: var(--transicion);
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex-grow: 1;
            margin-left: 10px;
        }
        
        .add-btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .add-btn:active {
            transform: translateY(0);
        }
        
        .add-btn i {
            font-size: 1rem;
        }
        
        /* Estilos para el botón de regresar */
        .btn-regresar {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 500;
            transition: var(--transicion);
            margin-bottom: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-regresar:hover {
            background-color: #5a6268;
            color: white;
            transform: translateY(-2px);
        }
        
        /* Estilos para el botón del carrito */
        .btn-carrito {
            background-color: var(--color-secundario);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: var(--transicion);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-carrito:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .contador-carrito {
            background-color: var(--color-terciario);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            margin-left: 8px;
        }
        
        /* Estilos para el modal del carrito */
        .modal-header {
            background-color: var(--color-primario);
            color: white;
            border-radius: 0;
        }
        
        #carrito-contenido {
            max-height: 60vh;
            overflow-y: auto;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .btn-eliminar {
            transition: var(--transicion);
        }
        
        .btn-eliminar:hover {
            transform: scale(1.1);
        }
        
        /* Estilos para mensajes de alerta */
        .alert-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 350px;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .producto-acciones {
                flex-direction: column;
                gap: 10px;
            }
            
            .add-btn {
                margin-left: 0;
                width: 100%;
            }
            
            .quantity-control {
                width: 100%;
                justify-content: center;
            }
            
            .proveedor-card {
                padding: 20px;
            }
            
            .proveedor-nombre {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><i class="fas fa-paint-roller"></i> Sistema de Cotizaciones - Pintex®</h1>
            <p class="mb-0"><?php echo $proveedor_id > 0 ? 'Catálogo de ' . htmlspecialchars($proveedor['nombre']) : 'Seleccione un proveedor para comenzar'; ?></p>
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
                                <input type="text" class="search-input form-control" id="buscar-proveedor" name="busqueda_proveedor" 
                                       placeholder="Buscar por nombre o descripción..." value="<?php echo htmlspecialchars($busqueda_proveedor); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="search-input-container">
                                <i class="fas fa-map-marker-alt"></i>
                                <input type="text" class="search-input form-control" id="buscar-colonia" name="colonia" 
                                       placeholder="Filtrar por colonia o dirección..." value="<?php echo htmlspecialchars($colonia); ?>">
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
            
            <h2 class="mb-4">Proveedores Disponibles</h2>
            
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
                                     alt="<?php echo htmlspecialchars($prov['nombre']); ?>" 
                                     class="proveedor-logo img-fluid">
                                <h3 class="proveedor-nombre"><?php echo htmlspecialchars($prov['nombre']); ?></h3>
                                <p class="proveedor-info"><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($prov['telefono'] ?? 'N/A'); ?></p>
                                <p class="proveedor-info"><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($prov['direccion'] ?? 'N/A'); ?></p>
                                <p class="proveedor-descripcion"><?php echo htmlspecialchars($prov['descripcion'] ?? 'Sin descripción'); ?></p>
                                <a href="?proveedor_id=<?php echo $prov['id']; ?>" class="btn btn-cotizar">
                                    <i class="fas fa-clipboard-list"></i> Ver Catálogo
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Botón para regresar a proveedores -->
            <a href="?" class="btn btn-regresar">
                <i class="fas fa-arrow-left"></i> Regresar a proveedores
            </a>
            
            <!-- Información del proveedor -->
            <div class="proveedor-info bg-white p-4 rounded shadow-sm mb-4">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        <img src="<?php echo !empty($proveedor['logo']) ? htmlspecialchars($proveedor['logo']) : 'https://via.placeholder.com/100?text=' . urlencode(substr($proveedor['nombre'], 0, 1)); ?>" 
                             alt="<?php echo htmlspecialchars($proveedor['nombre']); ?>" 
                             class="img-fluid rounded-circle" style="max-width: 80px;">
                    </div>
                    <div class="col-md-10">
                        <div class="row">
                            <div class="col-md-6">
                                <h3><i class="fas fa-building me-2"></i><?php echo htmlspecialchars($proveedor['nombre']); ?></h3>
                                <p><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($proveedor['telefono'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($proveedor['direccion'] ?? 'N/A'); ?></p>
                                <p><i class="fas fa-info-circle me-2"></i><?php echo htmlspecialchars($proveedor['descripcion'] ?? 'Sin descripción'); ?></p>
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
                                       placeholder="Buscar por nombre, descripción o SKU..." value="<?php echo htmlspecialchars($busqueda_producto); ?>">
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
                                    <option value="<?php echo htmlspecialchars($categoria); ?>"><?php echo htmlspecialchars($categoria); ?></option>
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
            
            <!-- Listado de productos -->
            <div class="row row-cols-1 row-cols-md-2 g-4" id="productos-container">
                <?php if (count($productos) === 0): ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center py-4">
                            <i class="fas fa-info-circle me-2"></i> No se encontraron productos con los criterios de búsqueda.
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($productos as $producto): ?>
                        <div class="col producto-item" 
                             data-categoria="<?php echo htmlspecialchars($producto['categoria'] ?? ''); ?>" 
                             data-tipo="<?php echo htmlspecialchars($producto['tipo'] ?? ''); ?>">
                            <div class="producto-card h-100">
                                <h3 class="producto-titulo"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                                <p class="producto-descripcion"><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></p>
                                
                                <?php if (isset($producto['rating'])): ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="rating-stars">
                                            <?php 
                                            $rating = $producto['rating'];
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
                                        <span class="rating-count ms-2"><?php echo number_format($rating, 1); ?> (<?php echo $producto['reviews'] ?? 0; ?>)</span>
                                    </div>
                                <?php endif; ?>
                                
                                <p class="producto-sku">SKU: <?php echo htmlspecialchars($producto['sku'] ?? 'N/A'); ?></p>
                                
                                <p class="producto-precio">$<?php echo number_format($producto['precio'], 2); ?></p>
                                
                                <div class="producto-acciones">
                                    <div class="quantity-control">
                                        <button class="quantity-btn">-</button>
                                        <input type="number" class="quantity-input" value="1" min="1">
                                        <button class="quantity-btn">+</button>
                                    </div>
                                    <button class="add-btn" 
                                            data-id="<?php echo $producto['id']; ?>"
                                            data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                            data-precio="<?php echo $producto['precio']; ?>"
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
                    <li class="page-item">
                        <a class="page-link" href="#" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#" aria-label="Next">
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
                    <h5 class="modal-title" id="carritoModalLabel"><i class="fas fa-shopping-cart"></i> Tu Carrito de Cotización</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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
            btn.addEventListener('click', function() {
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
            input.addEventListener('change', function() {
                if (this.value < 1 || isNaN(this.value)) {
                    this.value = 1;
                }
            });
        });
        
        // Funcionalidad para los botones de agregar
        document.querySelectorAll('.add-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const productoId = this.dataset.id;
                const productoNombre = this.dataset.nombre;
                const productoPrecio = parseFloat(this.dataset.precio);
                const proveedorNombre = this.dataset.proveedor;
                const cantidad = parseInt(this.closest('.producto-acciones').querySelector('.quantity-input').value);
                
                const productoExistente = carrito.find(item => item.id === productoId);
                
                if (productoExistente) {
                    productoExistente.cantidad += cantidad;
                } else {
                    carrito.push({
                        id: productoId,
                        nombre: productoNombre,
                        precio: productoPrecio,
                        cantidad: cantidad,
                        proveedor: proveedorNombre
                    });
                }
                
                // Guardar en localStorage
                localStorage.setItem('carrito', JSON.stringify(carrito));
                
                // Mostrar notificación
                mostrarNotificacion(`${cantidad} x ${productoNombre} agregado al carrito`);
                
                actualizarContadorCarrito();
                actualizarCarrito();
            });
        });
        
        // Funcionalidad para los filtros
        document.querySelectorAll('.filter-select').forEach(select => {
            select.addEventListener('change', function() {
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
        
        // Función para actualizar el carrito
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
                                <th>Producto</th>
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
            
            carrito.forEach(item => {
                const subtotal = item.precio * item.cantidad;
                total += subtotal;
                
                html += `
                    <tr>
                        <td>${item.nombre}</td>
                        <td>${item.proveedor}</td>
                        <td>$${item.precio.toFixed(2)}</td>
                        <td>${item.cantidad}</td>
                        <td>$${subtotal.toFixed(2)}</td>
                        <td>
                            <button class="btn btn-sm btn-danger btn-eliminar" data-id="${item.id}" title="Eliminar">
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
                                <td colspan="4" class="text-end fw-bold">Total:</td>
                                <td colspan="2" class="fw-bold">$${total.toFixed(2)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;
            
            carritoContenido.innerHTML = html;
            
            document.querySelectorAll('.btn-eliminar').forEach(btn => {
                btn.addEventListener('click', function() {
                    const productoId = this.dataset.id;
                    carrito = carrito.filter(item => item.id !== productoId);
                    localStorage.setItem('carrito', JSON.stringify(carrito));
                    actualizarContadorCarrito();
                    actualizarCarrito();
                    mostrarNotificacion('Producto eliminado del carrito', 'info');
                });
            });
        }
        
        // Mostrar el carrito
        document.getElementById('ver-carrito').addEventListener('click', function() {
            const carritoModal = new bootstrap.Modal(document.getElementById('carritoModal'));
            actualizarCarrito();
            carritoModal.show();
        });
        
        // Finalizar compra
        document.getElementById('finalizar-compra').addEventListener('click', function() {
            if (carrito.length === 0) {
                mostrarNotificacion('No hay productos en el carrito', 'warning');
                return;
            }
            
            // Aquí puedes agregar la lógica para generar la cotización
            mostrarNotificacion('Cotización generada con éxito! Se ha creado un PDF con los detalles.');
            
            // Limpiar el carrito
            carrito = [];
            localStorage.removeItem('carrito');
            actualizarContadorCarrito();
            actualizarCarrito();
            
            // Cerrar el modal
            const carritoModal = bootstrap.Modal.getInstance(document.getElementById('carritoModal'));
            carritoModal.hide();
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