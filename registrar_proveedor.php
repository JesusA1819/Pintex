<?php
session_start();
include 'includes.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $telefono = mysqli_real_escape_string($conexion, $_POST['telefono']);
    $direccion = mysqli_real_escape_string($conexion, $_POST['direccion']);
    $colonia = mysqli_real_escape_string($conexion, $_POST['colonia']);
    
    $logo_path = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/logos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = uniqid() . '_' . basename($_FILES['logo']['name']);
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
            $logo_path = $target_path;
        }
    }
    
    $query = "INSERT INTO proveedores (nombre, telefono, direccion, colonia, logo) 
              VALUES ('$nombre', '$telefono', '$direccion', '$colonia', " . ($logo_path ? "'$logo_path'" : "NULL") . ")";
    
    if (mysqli_query($conexion, $query)) {
        header("Location: admin_panel.php?mensaje=Proveedor registrado correctamente");
    } else {
        header("Location: admin_panel.php?mensaje=Error al registrar proveedor: " . urlencode(mysqli_error($conexion)));
    }
    exit();
}
?>