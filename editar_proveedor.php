<?php
session_start();
include 'includes.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['es_administrador']) {
    header('Location: index.php');
    exit();
}

$proveedor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Obtener datos del proveedor
$query = "SELECT * FROM proveedores WHERE id = $proveedor_id";
$result = mysqli_query($conexion, $query);
$proveedor = $result ? mysqli_fetch_assoc($result) : null;

if (!$proveedor) {
    header('Location: admin_panel.php?mensaje=Proveedor no encontrado');
    exit();
}

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $telefono = mysqli_real_escape_string($conexion, $_POST['telefono']);
    $direccion = mysqli_real_escape_string($conexion, $_POST['direccion']);
    $colonia = mysqli_real_escape_string($conexion, $_POST['colonia']);
    
    $logo_path = $proveedor['logo'];
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        // Eliminar logo anterior si existe
        if ($logo_path && file_exists($logo_path)) {
            unlink($logo_path);
        }
        
        // Subir nuevo logo
        $upload_dir = 'uploads/logos/';
        $file_name = uniqid() . '_' . basename($_FILES['logo']['name']);
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
            $logo_path = $target_path;
        }
    }
    
    $query = "UPDATE proveedores SET 
              nombre = '$nombre', 
              telefono = '$telefono', 
              direccion = '$direccion', 
              colonia = '$colonia', 
              logo = " . ($logo_path ? "'$logo_path'" : "NULL") . "
              WHERE id = $proveedor_id";
    
    if (mysqli_query($conexion, $query)) {
        header("Location: admin_panel.php?mensaje=Proveedor actualizado correctamente");
    } else {
        header("Location: admin_panel.php?mensaje=Error al actualizar proveedor: " . urlencode(mysqli_error($conexion)));
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Proveedor</title>
    <style>
        .form-container {
            max-width: 500px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input, select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-submit {
            background-color: #4CAF50;
            color: white;
        }
        .logo-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 10px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Editar Proveedor</h1>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Nombre del Proveedor:</label>
                <input type="text" name="nombre" value="<?= htmlspecialchars($proveedor['nombre']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Teléfono:</label>
                <input type="text" name="telefono" value="<?= htmlspecialchars($proveedor['telefono']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Dirección:</label>
                <input type="text" name="direccion" value="<?= htmlspecialchars($proveedor['direccion']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Colonia:</label>
                <input type="text" name="colonia" value="<?= htmlspecialchars($proveedor['colonia']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Logo:</label>
                <?php if ($proveedor['logo']): ?>
                    <img src="<?= $proveedor['logo'] ?>" class="logo-preview" alt="Logo actual">
                <?php endif; ?>
                <input type="file" name="logo" accept="image/*">
            </div>
            
            <button type="submit" class="btn btn-submit">Guardar Cambios</button>
            <a href="admin_panel.php" class="btn">Cancelar</a>
        </form>
    </div>
</body>
</html>