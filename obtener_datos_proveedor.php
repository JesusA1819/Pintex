<?php
header('Content-Type: application/json');

require 'includes.php';

if (!isset($_GET['proveedor_id'])) {
    echo json_encode(['error' => 'proveedor_id no especificado']);
    exit;
}

$proveedor_id = intval($_GET['proveedor_id']);

// Obtener productos
$queryProductos = "SELECT s.id, p.nombre, s.nombre AS producto_nombre, s.precio
                FROM proveedores p 
                INNER JOIN productos s ON p.id = s.provedor_id
                WHERE p.id = ?";
$stmtProductos = mysqli_prepare($conexion, $queryProductos);
mysqli_stmt_bind_param($stmtProductos, "i", $proveedor_id);
mysqli_stmt_execute($stmtProductos);
$resultProductos = mysqli_stmt_get_result($stmtProductos);

$productos = [];
while ($producto = mysqli_fetch_assoc($resultProductos)) {
    $productos[] = $producto;
}

// Obtener pinturas
$queryPinturas = "SELECT p.nombre, s.id, s.tamano, s.Precio AS precio
                FROM proveedores p 
                INNER JOIN pinturas s ON p.id = s.proveedor_id
                WHERE s.proveedor_id = ?";
$stmtPinturas = mysqli_prepare($conexion, $queryPinturas);
mysqli_stmt_bind_param($stmtPinturas, "i", $proveedor_id);
mysqli_stmt_execute($stmtPinturas);
$resultPinturas = mysqli_stmt_get_result($stmtPinturas);

$pinturas = [];
while ($pintura = mysqli_fetch_assoc($resultPinturas)) {
    $pintura['precio'] = (float)str_replace(',', '', $pintura['precio']);
    $pinturas[] = $pintura;
}

echo json_encode([
    'productos' => $productos,
    'pinturas' => $pinturas
]);
?>