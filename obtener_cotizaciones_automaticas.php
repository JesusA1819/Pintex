<?php
header('Content-Type: application/json');
include 'includes.php';


$data = json_decode(file_get_contents('php://input'), true);
$litrosPintura = floatval($data['litros_pintura']);
$tipoPintura = $conn->real_escape_string($data['tipo_pintura']);
$productosBasicos = $data['productos_basicos'];

$proveedores = $conn->query("SELECT * FROM proveedores")->fetch_all(MYSQLI_ASSOC);
$cotizaciones = [];

foreach ($proveedores as $proveedor) {
    $productosCotizacion = [];
    $total = 0;
    
    // 1. Buscar pintura del tipo solicitado
    $pintura = $conn->query("SELECT p.* FROM pinturas pint
                            JOIN productos p ON pint.producto_id = p.id
                            WHERE p.proveedor_id = {$proveedor['id']}
                            AND pint.tipo = '$tipoPintura'
                            ORDER BY p.precio ASC LIMIT 1")->fetch_assoc();
    
    if ($pintura) {
        $subtotal = $litrosPintura * $pintura['precio'];
        $productosCotizacion[] = [
            'id' => $pintura['id'],
            'nombre' => $pintura['nombre'],
            'cantidad' => $litrosPintura,
            'precio_unitario' => $pintura['precio'],
            'subtotal' => $subtotal
        ];
        $total += $subtotal;
    }
    
    // 2. Agregar productos básicos
    foreach ($productosBasicos as $basico) {
        $producto = $conn->query("SELECT * FROM productos 
                                WHERE proveedor_id = {$proveedor['id']}
                                AND nombre LIKE '%$basico%'
                                ORDER BY precio ASC LIMIT 1")->fetch_assoc();
        
        if ($producto) {
            $cantidad = calcularCantidadProducto($basico, $litrosPintura);
            $subtotal = $cantidad * $producto['precio'];
            $productosCotizacion[] = [
                'id' => $producto['id'],
                'nombre' => $producto['nombre'],
                'cantidad' => $cantidad,
                'precio_unitario' => $producto['precio'],
                'subtotal' => $subtotal
            ];
            $total += $subtotal;
        }
    }
    
    if (count($productosCotizacion) > 0) {
        $cotizaciones[] = [
            'proveedor_id' => $proveedor['id'],
            'proveedor_nombre' => $proveedor['nombre'],
            'productos' => $productosCotizacion,
            'total' => $total
        ];
    }
}

function calcularCantidadProducto($producto, $litros) {
    $producto = strtolower($producto);
    if (strpos($producto, 'lija') !== false) return ceil($litros * 2);
    if (strpos($producto, 'brocha') !== false || strpos($producto, 'rodillo') !== false) {
        return ceil($litros / 5);
    }
    return 1;
}

echo json_encode($cotizaciones);
?>