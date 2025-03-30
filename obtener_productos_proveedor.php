<?php
header('Content-Type: application/json');
require_once 'includes.php'; // Asegura que este archivo contiene la conexión a la BD.

if (!isset($_GET['proveedor_id']) || !is_numeric($_GET['proveedor_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID de proveedor inválido']);
    exit;
}

$proveedorId = intval($_GET['proveedor_id']);

try {
    // Conectar a la base de datos
    if (!$conexion) {
        throw new Exception('Error en la conexión a la base de datos.');
    }


    // Consulta para obtener productos generales del proveedor
    $sqlProductos = "SELECT id, nombre, precio, tipo FROM productos WHERE provedor_id = ?";
    $stmtProductos = $conexion->prepare($sqlProductos);
    if (!$stmtProductos) {
        throw new Exception("Error preparando consulta de productos: " . $conexion->error);
    }
    $stmtProductos->bind_param("i", $proveedorId);
    $stmtProductos->execute();
    $resultProductos = $stmtProductos->get_result();

    $productos = [];
    while ($row = $resultProductos->fetch_assoc()) {
        $productos[] = $row;
    }

    // Consulta para obtener pinturas específicas del proveedor
    $sqlPinturas = "SELECT pi.id, CO.nombre_color, pi.tipo, pi.marca, pi.tamano, pi.Precio
                    FROM pinturas pi 
                    INNER JOIN productos p ON pi.producto_id = p.id 
                    INNER JOIN colores_pintura CO ON CO.pintura_id = pi.id 
                    WHERE p.provedor_id = ?";
    $stmtPinturas = $conexion->prepare($sqlPinturas);
    if (!$stmtPinturas) {
        throw new Exception("Error preparando consulta de pinturas: " . $conexion->error);
    }
    $stmtPinturas->bind_param("i", $proveedorId);
    $stmtPinturas->execute();
    $resultPinturas = $stmtPinturas->get_result();

    $pinturas = [];
    while ($row = $resultPinturas->fetch_assoc()) {
        $pinturas[] = $row;
    }

    // Devolver datos en formato JSON
    echo json_encode([
        'success' => true,
        'productos' => $productos ?: [], // Asegura que sea un array vacío si no hay datos
        'pinturas' => $pinturas ?: []
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($conexion)) $conexion->close();
}
