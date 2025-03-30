<?php

session_start(); // Iniciar sesión

// Almacenar valores de sesión en variables
$user_id = $_SESSION['user_id'] ?? null;
$es_administrador = $_SESSION['es_administrador'] ?? false;

// Verificar si el usuario está autenticado y es administrador
if (!$user_id || !$es_administrador) {
    header('Location: index.php');
    exit();
}

// Ahora puedes usar las variables en todo tu código
$proveedor_id = filter_input(INPUT_GET, 'proveedor_id', FILTER_VALIDATE_INT);
if (!$proveedor_id) {
    $_SESSION['error'] = "Proveedor no válido";
    header('Location: admin_panel.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Herramienta</title>
    <style>
        .form-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            cursor: pointer;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Agregar Nueva Herramienta</h1>
        
        <?php if (!empty($error)): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
        
        <form action="AHTRM.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            
            <div class="form-group">
                <label for="precio">Precio:</label>
                <input type="number" id="precio" name="precio" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="marca">Marca:</label>
                <input type="text" id="marca" name="marca" required>
            </div>
            
            <div class="form-group">
                <input type="hidden" id="proovedor_id" name="proovedor_id" value="<?= $proveedor_id ?>">
            </div>
            
            <div class="form-group">
                <label for="imagen">Imagen:</label>
                <input type="file" id="imagen" name="imagen" accept="image/*" required>
            </div>
            
            <button type="submit">Guardar Herramienta</button>
        </form>
    </div>
</body>
</html>
