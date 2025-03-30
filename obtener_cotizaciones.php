<?php
header('Content-Type: application/json');
include 'includes.php';

try {
    if (!isset($conexion) || !($conexion instanceof mysqli)) {
        throw new Exception("Conexión a la base de datos no está disponible");
    }

    $query = "SELECT id, nombre, telefono, direccion FROM proveedores ORDER BY nombre";
    $result = $conexion->query($query);

    if (!$result) {
        throw new Exception("Error en la consulta: " . $conexion->error);
    }

    $proveedores = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $proveedores
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>