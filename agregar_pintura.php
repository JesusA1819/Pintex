<?php
session_start();
require_once 'includes.php';

// Verificar conexión a la base de datos
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Verificar sesión y permisos
if (!isset($_SESSION['user_id']) || !$_SESSION['es_administrador']) {
    header('Location: index.php');
    exit();
}

// Obtener y validar ID del proveedor
$proveedor_id = filter_input(INPUT_GET, 'proveedor_id', FILTER_VALIDATE_INT);
if (!$proveedor_id) {
    $_SESSION['error'] = "Proveedor no válido";
    header('Location: admin_panel.php');
    exit();
}

// Inicializar variable con valor por defecto
$proveedor_nombre = "Proveedor no encontrado";

// Obtener datos del proveedor
$query = "SELECT nombre FROM proveedores WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $proveedor_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $proveedor_nombre = $row['nombre'];
        }
    } else {
        error_log("Error al ejecutar consulta: " . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);
} else {
    error_log("Error en consulta: " . mysqli_error($conexion));
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y sanitizar los datos del formulario
    $nombre = trim($_POST['nombre'] ?? '');
    $precio = str_replace(',', '.', $_POST['precio'] ?? '');
    $precio = filter_var($precio, FILTER_VALIDATE_FLOAT);
    $marca = trim($_POST['marca'] ?? '');
    $tamano = $_POST['tamano'] ?? '';
    $tipo_pintura = $_POST['tipo'] ?? '';
    $colores = json_decode($_POST['colores_json'] ?? '[]', true);

    // Validación robusta de campos
    $errores = [];
    if (empty($nombre)) $errores[] = "El nombre del producto es requerido";
    if ($precio === false || $precio <= 0) $errores[] = "Ingrese un precio válido mayor a 0";
    if (empty($marca)) $errores[] = "La marca es requerida";
    if (empty($tamano)) $errores[] = "Seleccione un tamaño";
    if (empty($tipo_pintura)) $errores[] = "Seleccione un tipo de pintura";
    
    // Validación de imagen
    $imagen_path = null;
    if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
        $errores[] = "Debe seleccionar una imagen del producto";
    } else {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['imagen']['type'];
        if (!in_array($file_type, $allowed_types)) {
            $errores[] = "Solo se permiten imágenes JPEG, PNG o GIF";
        }
    }

    // Validación de colores
    if (empty($colores)) {
        $errores[] = "Debe agregar al menos un color";
    }

    // Si hay errores, mostrar y redireccionar
    if (!empty($errores)) {
        $_SESSION['error'] = implode("<br>", $errores);
        header("Location: agregar_pintura.php?proveedor_id=$proveedor_id");
        exit();
    }

    // Procesar la imagen
    $upload_dir = 'uploads/productos/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_name = uniqid() . '_' . basename($_FILES['imagen']['name']);
    $target_path = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $target_path)) {
        $imagen_path = $target_path;
    }

    // Insertar en la base de datos usando transacciones
    mysqli_begin_transaction($conexion);
    
    try {
        // Insertar en productos (CORRECCIÓN: usar provedor_id)
        $query = "INSERT INTO productos (provedor_id, nombre, precio, tipo, imagen) VALUES (?, ?, ?, 1, ?)";
        $stmt = mysqli_prepare($conexion, $query);
  
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta de productos: " . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param($stmt, 'isds', $proveedor_id, $nombre, $precio, $imagen_path);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error al ejecutar la consulta de productos: " . mysqli_stmt_error($stmt));
        }
        
        $producto_id = mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt);

        // Insertar en tabla pinturas (CORRECCIÓN: usar tannano)
        $query = "INSERT INTO pinturas (producto_id, marca, tamano, tipo) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conexion, $query);
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta de pinturas: " . mysqli_error($conexion));
        }
        
        mysqli_stmt_bind_param($stmt, 'isss', $producto_id, $marca, $tamano, $tipo_pintura);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error al ejecutar la consulta de pinturas: " . mysqli_stmt_error($stmt));
        }
        
        $pintura_id = mysqli_insert_id($conexion);
        mysqli_stmt_close($stmt);

        // Insertar colores
        foreach ($colores as $color) {
            if (!empty($color['nombre']) && !empty($color['rgb'])) {
                $query = "INSERT INTO colores_pintura (pintura_id, nombre_color, codigo_rgb) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conexion, $query);
                
                if (!$stmt) {
                    throw new Exception("Error al preparar la consulta de colores: " . mysqli_error($conexion));
                }
                
                mysqli_stmt_bind_param($stmt, 'iss', $pintura_id, $color['nombre'], $color['rgb']);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error al ejecutar la consulta de colores: " . mysqli_stmt_error($stmt));
                }
                
                mysqli_stmt_close($stmt);
            }
        }

        // Confirmar transacción
        mysqli_commit($conexion);
        $_SESSION['exito'] = "Pintura guardada correctamente";
        header("Location: adm.php");
        exit();
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        mysqli_rollback($conexion);
        $_SESSION['error'] = $e->getMessage();
        header("Location: agregar_pintura.php?proveedor_id=$proveedor_id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Pintura - Pintex</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn-submit {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-submit:hover {
            background-color: #45a049;
        }
        .error {
            color: #f44336;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #ffebee;
            border-radius: 4px;
        }
        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-block;
            margin-left: 10px;
            vertical-align: middle;
            border: 1px solid #ccc;
        }
        .color-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .color-inputs {
            display: flex;
            gap: 10px;
            flex-grow: 1;
        }
        .color-inputs input {
            flex: 1;
        }
        .remove-color {
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
            margin-left: 10px;
        }
        #colores-container {
            margin-top: 20px;
        }
        #add-color {
            margin-top: 10px;
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Agregar Nueva Pintura</h1>
        <p>Proveedor: <strong><?= htmlspecialchars($proveedor_nombre) ?></strong></p>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data" id="pinturaForm">
            <input type="hidden" name="proveedor_id" value="<?= $proveedor_id ?>">
            
            <div class="form-group">
                <label for="nombre">Nombre del Producto:</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            
            <div class="form-group">
                <label for="precio">Precio:</label>
                <input type="number" id="precio" name="precio" step="0.01" min="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="marca">Marca:</label>
                <input type="text" id="marca" name="marca" required>
            </div>
            
            <div class="form-group">
                <label for="tamano">Tamaño:</label>
                <select id="tamano" name="tamano" required>
                    <option value="">Seleccione un tamaño</option>
                    <option value="1lt">1 litro</option>
                    <option value="5lts">Galón (5 litros)</option>
                    <option value="20lts">Cubeta (20 litros)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="tipo">Tipo de Pintura:</label>
                <select id="tipo" name="tipo" required>
                    <option value="">Seleccione un tipo</option>
                    <option value="vinilica">Vinílica</option>
                    <option value="esmalte">Esmalte</option>
                    <option value="base agua">Base agua</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="imagen">Imagen del Producto:</label>
                <input type="file" id="imagen" name="imagen" accept="image/*" required>
            </div>
            
            <div class="form-group">
                <label>Colores Disponibles:</label>
                <div id="colores-container">
                    <!-- Los colores se agregarán aquí dinámicamente -->
                </div>
                <button type="button" id="add-color">Agregar Color</button>
                <input type="hidden" id="colores_json" name="colores_json">
            </div>
            
            <button type="submit" class="btn-submit">Guardar Pintura</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Función para actualizar el color de vista previa
            function updateColorPreview(input, preview) {
                const rgbValue = input.value;
                if (rgbValue.match(/^rgb\(\d{1,3},\s*\d{1,3},\s*\d{1,3}\)$/)) {
                    preview.style.backgroundColor = rgbValue;
                }
            }

            // Función para agregar un nuevo campo de color
            function addColorField(nombre = '', rgb = '') {
                const container = document.getElementById('colores-container');
                const colorId = Date.now();
                
                const colorItem = document.createElement('div');
                colorItem.className = 'color-item';
                
                const colorInputs = document.createElement('div');
                colorInputs.className = 'color-inputs';
                
                const nombreInput = document.createElement('input');
                nombreInput.type = 'text';
                nombreInput.placeholder = 'Nombre del color';
                nombreInput.value = nombre;
                
                const rgbInput = document.createElement('input');
                rgbInput.type = 'text';
                rgbInput.placeholder = 'rgb(R, G, B)';
                rgbInput.value = rgb;
                
                const preview = document.createElement('div');
                preview.className = 'color-preview';
                
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'remove-color';
                removeBtn.innerHTML = '×';
                removeBtn.addEventListener('click', function() {
                    container.removeChild(colorItem);
                    updateColorsJSON();
                });
                
                // Configurar eventos
                rgbInput.addEventListener('input', function() {
                    updateColorPreview(this, preview);
                    updateColorsJSON();
                });
                
                nombreInput.addEventListener('input', updateColorsJSON);
                
                // Construir estructura
                colorInputs.appendChild(nombreInput);
                colorInputs.appendChild(rgbInput);
                
                colorItem.appendChild(colorInputs);
                colorItem.appendChild(preview);
                colorItem.appendChild(removeBtn);
                
                container.appendChild(colorItem);
                
                // Actualizar vista previa inicial
                if (rgb) updateColorPreview(rgbInput, preview);
            }

            // Función para actualizar el JSON oculto
            function updateColorsJSON() {
                const colorItems = document.querySelectorAll('.color-item');
                const colors = [];
                
                colorItems.forEach(item => {
                    const nombre = item.querySelector('input[type="text"]').value;
                    const rgb = item.querySelector('input[type="text"]:last-child').value;
                    if (nombre && rgb) {
                        colors.push({ nombre, rgb });
                    }
                });
                
                document.getElementById('colores_json').value = JSON.stringify(colors);
            }

            // Evento para agregar nuevo color
            document.getElementById('add-color').addEventListener('click', function() {
                addColorField();
            });

            // Validación antes de enviar el formulario
            document.getElementById('pinturaForm').addEventListener('submit', function(e) {
                updateColorsJSON();
                const colores = JSON.parse(document.getElementById('colores_json').value);
                
                if (colores.length === 0) {
                    e.preventDefault();
                    alert('Debe agregar al menos un color');
                    return false;
                }
                
                return true;
            });

            // Agregar un color inicial al cargar la página
            addColorField();
        });
    </script>
</body>
</html>