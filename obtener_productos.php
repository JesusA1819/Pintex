<?php
header('Content-Type: application/json');
include 'includes.php';

$proveedor_id = isset($_GET['proveedor_id']) ? (int)$_GET['proveedor_id'] : 0;

// Consulta para obtener los productos
$query_productos = "SELECT id, nombre, precio, imagen, rol FROM productos WHERE provedor_id = $proveedor_id";
$result_productos = mysqli_query($conexion, $query_productos);

$productos = [];
if ($result_productos && mysqli_num_rows($result_productos) > 0) {
    while ($row = mysqli_fetch_assoc($result_productos)) {
        $productos[] = $row;
    }
}

// Consulta para obtener las pinturas
$query_pinturas = "SELECT id, marca, tamano, precio, tipo, rol FROM pinturas WHERE proveedor_id = $proveedor_id";
$result_pinturas = mysqli_query($conexion, $query_pinturas);

$pinturas = [];
if ($result_pinturas && mysqli_num_rows($result_pinturas) > 0) {
    while ($row = mysqli_fetch_assoc($result_pinturas)) {
        $pinturas[] = $row;
    }
}

// Combinar los resultados de productos y pinturas
$items = array_merge($productos, $pinturas);

// Enviar los datos en formato JSON
echo json_encode($items);
?>
