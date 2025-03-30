<?php
session_start();
require_once 'includes.php';

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['user_id']) || !$_SESSION['es_administrador']) {
    header('Location: index.php');
    exit();
}

// Verificar que se haya proporcionado un ID de proveedor válido
$proveedor_id = filter_input(INPUT_GET, 'proveedor_id', FILTER_VALIDATE_INT);
if (!$proveedor_id) {
    $_SESSION['error'] = "Proveedor no válido";
    header('Location: admin_panel.php');
    exit();
}

// Procesar la selección del tipo de producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo_producto'])) {
    $tipo = $_POST['tipo_producto'];
    
    // Redirigir al formulario correspondiente
    if ($tipo === 'herramienta') {
        header("Location: agregar_herramienta.php?proveedor_id=$proveedor_id");
    } elseif ($tipo === 'pintura') {
        header("Location: agregar_pintura.php?proveedor_id=$proveedor_id");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Nuevo Producto</title>
    <link rel="stylesheet" href="style.css">
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
            text-align: center;
            color: #333;
        }
        .option-container {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 40px;
        }
        .option-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 30px;
            width: 200px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #4CAF50;
        }
        .option-card img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 15px;
        }
        .option-card h3 {
            margin: 0;
            color: #333;
        }
        .hidden-radio {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .hidden-radio:checked + .option-card {
            border-color: #4CAF50;
            background-color: #f0fff0;
        }
        form {
            margin-top: 20px;
        }
        .btn-submit {
            display: block;
            width: 200px;
            margin: 30px auto 0;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-submit:hover {
            background-color: #45a049;
        }
        .btn-submit:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Registrar Nuevo Producto</h1>
        <p style="text-align: center;">Seleccione el tipo de producto que desea registrar:</p>
        
        <form method="POST" action="registrar_producto.php?proveedor_id=<?= $proveedor_id ?>">
            <div class="option-container">
                <label>
                    <input type="radio" name="tipo_producto" value="herramienta" class="hidden-radio" required>
                    <div class="option-card">
                        <img src="imagenes/herramienta.png" alt="Herramienta">
                        <h3>Herramienta</h3>
                    </div>
                </label>
                
                <label>
                    <input type="radio" name="tipo_producto" value="pintura" class="hidden-radio" required>
                    <div class="option-card">
                        <img src="imagenes/pintura.png" alt="Pintura">
                        <h3>Pintura</h3>
                    </div>
                </label>
            </div>
            
            <input type="hidden" name="proveedor_id" value="<?= $proveedor_id ?>">
            <button type="submit" class="btn-submit" id="submit-btn" disabled>Continuar</button>
        </form>
    </div>

    <script>
        // Habilitar el botón solo cuando se seleccione una opción
        const radios = document.querySelectorAll('.hidden-radio');
        const submitBtn = document.getElementById('submit-btn');
        
        radios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (document.querySelector('input[name="tipo_producto"]:checked')) {
                    submitBtn.disabled = false;
                }
            });
        });
    </script>
</body>
</html>