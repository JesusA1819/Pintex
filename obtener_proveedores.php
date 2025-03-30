<?php
header('Content-Type: application/json');

require_once 'includes.php';

try {
  
    $sql = "SELECT * FROM proveedores";
    $result = $conexion->query($sql);
    
    if (!$result) {
        throw new Exception("Error in query: " . $conexion->error);
    }
    
    $proveedores = [];
    while ($row = $result->fetch_assoc()) {
        $proveedores[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $proveedores
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($conexion)) $conexion->close();
}
?>