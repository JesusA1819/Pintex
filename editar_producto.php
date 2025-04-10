<?php
session_start();
require_once 'includes.php';
$rol = $_GET['rol'] ?? 'producto';  // valor por defecto 'producto'

if (!isset($_SESSION['user_id']) || !$_SESSION['es_administrador']) {
    header('Location: index.php');
    exit();
}

// Validar ID de producto o pintura
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['error'] = "ID no válido.";
    header("Location: adm.php");
    exit();
}

// Consulta modificada para pintura (corrige los joins)
if ($rol === 'pintura') {
    $query = "SELECT p.id AS pintura_id, p.marca, p.tamano, p.tipo, p.precio,
                     pr.nombre AS proveedor_nombre, pr.id AS proveedor_id,
                     p.marca AS nombre_producto
              FROM pinturas p
              JOIN proveedores pr ON p.proveedor_id = pr.id
              WHERE p.id = ?";
} else {
    // Consulta para producto
    $query = "SELECT prod.id AS producto_id, prod.nombre, prod.precio, prod.imagen, 
                     pr.nombre AS proveedor_nombre, pr.id AS proveedor_id 
              FROM productos prod
              JOIN proveedores pr ON prod.provedor_id = pr.id
              WHERE prod.id = ?";
}

// Preparar la consulta
$stmt = mysqli_prepare($conexion, $query);

if ($stmt === false) {
    $_SESSION['error'] = "Error al preparar la consulta: " . mysqli_error($conexion);
    header("Location: adm.php");
    exit();
}

// Vincular parámetros
mysqli_stmt_bind_param($stmt, 'i', $id);

// Ejecutar la consulta
if (!mysqli_stmt_execute($stmt)) {
    $_SESSION['error'] = "Error al ejecutar la consulta: " . mysqli_stmt_error($stmt);
    header("Location: adm.php");
    exit();
}

// Obtener el resultado
$result = mysqli_stmt_get_result($stmt);

if ($rol === 'pintura') {
    $registro = mysqli_fetch_assoc($result);
    if (!$registro) {
        $_SESSION['error'] = "Pintura no encontrada.";
        header("Location: adm.php");
        exit();
    }
} else {
    $registro = mysqli_fetch_assoc($result);
    if (!$registro) {
        $_SESSION['error'] = "Producto no encontrado.";
        header("Location: adm.php");
        exit();
    }
}
mysqli_stmt_close($stmt);

// Si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {    
    $nombre = trim($_POST['nombre'] ?? '');
    $precio = str_replace(',', '.', $_POST['precio'] ?? '');
    $precio = filter_var($precio, FILTER_VALIDATE_FLOAT);
    $errores = [];

    if ($precio === false || $precio <= 0) $errores[] = "Precio inválido.";

    if (empty($errores)) {
        mysqli_begin_transaction($conexion);
        try {
            if ($rol === 'pintura') {
                // Actualizar pintura
                $query = "UPDATE pinturas SET marca = ?, tamano = ?, precio = ?, tipo = ? WHERE id = ?";
                $stmt = mysqli_prepare($conexion, $query);
                if (!$stmt) {
                    throw new Exception("Error al preparar la consulta: " . mysqli_error($conexion));
                }
                
                // Corregido el orden y cantidad de parámetros (5 en total)
                mysqli_stmt_bind_param($stmt, 'ssdsi', 
                    $_POST['marca'], 
                    $_POST['tamano'],
                    $precio,  // Usar la variable $precio ya validada
                    $_POST['tipo'], 
                    $id
                );
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error actualizando pintura: " . mysqli_stmt_error($stmt));
                }
                mysqli_stmt_close($stmt);

            } else {
                $imagen_path = $registro['imagen'];

                // Validar nueva imagen (si se subió)
                if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                    $allowed = ['image/jpeg', 'image/png', 'image/gif'];
                    if (!in_array($_FILES['imagen']['type'], $allowed)) {
                        $errores[] = "Tipo de imagen no permitido.";
                    } else {
                        $upload_dir = 'uploads/productos/';
                        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                        $file_name = uniqid() . '_' . basename($_FILES['imagen']['name']);
                        $target_path = $upload_dir . $file_name;
                        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $target_path)) {
                            $imagen_path = $target_path;
                            // Opcional: eliminar la imagen anterior si existe
                            if (!empty($registro['imagen']) && file_exists($registro['imagen'])) {
                                unlink($registro['imagen']);
                            }
                        } else {
                            $errores[] = "No se pudo subir la imagen.";
                        }
                    }
                }

                if (empty($errores)) {
                    
                    // Actualizar producto
                    $query = "UPDATE productos SET nombre = ?, precio = ?, imagen = ? WHERE id = ?";
                    $stmt = mysqli_prepare($conexion, $query);
                    if (!$stmt) {
                        throw new Exception("Error al preparar la consulta: " . mysqli_error($conexion));
                    }
                    
                    mysqli_stmt_bind_param($stmt, 'sdsi', $nombre, $precio, $imagen_path, $id);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Error actualizando producto: " . mysqli_stmt_error($stmt));
                    }
                    mysqli_stmt_close($stmt);
                }
            }

            if (empty($errores)) {
                mysqli_commit($conexion);
                $_SESSION['exito'] = "¡Actualización exitosa!";
                header("Location: adm.php");
                exit();
            } else {
                mysqli_rollback($conexion);
                $_SESSION['error'] = implode("<br>", $errores);
            }
        } catch (Exception $e) {
            mysqli_rollback($conexion);
            $_SESSION['error'] = $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errores);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar <?php echo ucfirst($rol); ?></title>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --light-gray: #f5f5f5;
            --medium-gray: #e0e0e0;
            --dark-gray: #95a5a6;
            --text-color: #333;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 25px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--medium-gray);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--medium-gray);
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 15px;
        }
        
        .image-preview {
            margin: 15px 0;
        }
        
        .image-preview img {
            max-width: 200px;
            max-height: 200px;
            border: 1px solid var(--medium-gray);
            border-radius: 4px;
            padding: 5px;
            background: white;
        }
        
        .no-image {
            color: var(--dark-gray);
            font-style: italic;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
        }
        
        .btn-submit {
            background-color: var(--success-color);
            color: white;
            border: none;
        }
        
        .btn-submit:hover {
            background-color: #27ae60;
            transform: translateY(-1px);
        }
        
        .file-input {
            display: block;
            width: 100%;
            padding: 10px;
            border: 1px dashed var(--medium-gray);
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s;
        }
        
        .file-input:hover {
            border-color: var(--primary-color);
        }
        
        .form-actions {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--medium-gray);
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 15px;
                padding: 15px;
            }
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        .alert-danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <h1>Modificar <?php echo ucfirst($rol); ?></h1>
        
        <?php if ($rol === 'pintura') { ?>
            <!-- Formulario para Pintura -->
            <form method="POST" enctype="multipart/form-data">
              
                <div class="form-group">
                    <label>Nombre:</label>
                    <input type="text" name="marca" value="<?= htmlspecialchars($registro['marca'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Precio:</label>
                    <input type="number" id="precio" name="precio" step="0.01" min="0" value="<?= htmlspecialchars(number_format($registro['precio'] ?? 0, 2)) ?>" required>
                </div>
    
                
                <div class="form-group">
                    <label>Tamaño:</label>
                    <select name="tamano" required>
                        <option value="1" <?= ($registro['tamano'] ?? '') == '1' ? 'selected' : '' ?>>1 litro</option>
                        <option value="5" <?= ($registro['tamano'] ?? '') == '5' ? 'selected' : '' ?>>Galón (5 litros)</option>
                        <option value="20" <?= ($registro['tamano'] ?? '') == '20' ? 'selected' : '' ?>>Cubeta (20 litros)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Tipo:</label>
                    <select name="tipo" required>
                        <option value="vinilica" <?= ($registro['tipo'] ?? '') == 'vinilica' ? 'selected' : '' ?>>Vinílica</option>
                        <option value="esmalte" <?= ($registro['tipo'] ?? '') == 'esmalte' ? 'selected' : '' ?>>Esmalte</option>
                        <option value="base agua" <?= ($registro['tipo'] ?? '') == 'base agua' ? 'selected' : '' ?>>Base agua</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-submit">Guardar cambios</button>
                </div>
            </form>
        <?php } ?>

        <?php if ($rol === 'producto') { ?>
            <!-- Formulario para Producto -->
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Nombre del Producto:</label>
                    <input type="text" name="nombre" value="<?= htmlspecialchars($registro['nombre']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Precio:</label>
                    <input type="number" id="precio" name="precio" step="0.01" min="0" value="<?= htmlspecialchars(number_format($registro['precio'] ?? 0, 2)) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Imagen actual:</label>
                    <?php if (!empty($registro['imagen'])): ?>
                        <img src="<?= htmlspecialchars($registro['imagen']) ?>" width="150" alt="Imagen del producto">
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label>Cambiar imagen:</label>
                    <input type="file" name="imagen" accept="image/*">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-submit">Guardar cambios</button>
                </div>
            </form>
        <?php } ?>
    </div>
</body>
</html>