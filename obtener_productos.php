<?php
header('Content-Type: application/json');
include 'includes.php';

$proveedor_id = isset($_GET['proveedor_id']) ? (int)$_GET['proveedor_id'] : 0;

$query = "SELECT id, nombre, precio, imagen FROM productos WHERE provedor_id = $proveedor_id";
$result = mysqli_query($conexion, $query);

$productos = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $productos[] = $row;
    }
}

echo json_encode($productos);
?>