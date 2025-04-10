<?php
session_start(); // Iniciar sesión

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['user_id']) || !$_SESSION['es_administrador']) {
    header('Location: index.php');
    exit();
}

include 'includes.php'; // Incluir la conexión a la base de datos

// Procesar eliminación de proveedor si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_proveedor'])) {
    $proveedor_id = $_POST['proveedor_id'];

    // Primero verificar si el proveedor tiene productos asociados
    $check_query = "SELECT COUNT(*) as total FROM productos WHERE proovedor_id = $proveedor_id";
    $check_result = mysqli_query($conexion, $check_query);
    $row = mysqli_fetch_assoc($check_result);

    if ($row['total'] > 0) {
        $mensaje = "No se puede eliminar el proveedor porque tiene productos asociados";
    } else {
        // Eliminar la imagen del logo si existe
        $logo_query = "SELECT logo FROM proveedores WHERE id = $proveedor_id";
        $logo_result = mysqli_query($conexion, $logo_query);
        $logo_data = mysqli_fetch_assoc($logo_result);

        if ($logo_data && $logo_data['logo'] && file_exists($logo_data['logo'])) {
            unlink($logo_data['logo']);
        }

        // Eliminar el proveedor
        $delete_query = "DELETE FROM proveedores WHERE id = $proveedor_id";
        if (mysqli_query($conexion, $delete_query)) {
            $mensaje = "Proveedor eliminado correctamente";
        } else {
            $mensaje = "Error al eliminar el proveedor: " . mysqli_error($conexion);
        }
    }

    header("Location: admin_panel.php?mensaje=" . urlencode($mensaje));
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administrador - Pintex</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            margin: 20px auto;
            max-width: 800px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .form-container {
            margin-top: 15px;
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 5px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-submit {
            background-color: #4CAF50;
            color: white;
        }

        .btn-edit {
            background-color: #2196F3;
            color: white;
        }

        .btn-view {
            background-color: #FF9800;
            color: white;
        }

        .btn-delete {
            background-color: #f44336;
            color: white;
        }

        .proveedor-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .proveedor-info {
            flex-grow: 1;
            display: flex;
            align-items: center;
        }

        .proveedor-logo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 1px solid #ddd;
        }

        .proveedor-details {
            flex-grow: 1;
        }

        .proveedor-actions {
            display: flex;
            gap: 5px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            width: 80%;
            max-width: 600px;
        }

        .productos-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .producto-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
        }

        .producto-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            margin-right: 10px;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header>
        <div class="header-content">
            <img src="imagen/logo.jpg" alt="Logo Pintex" class="logo">
            <h1>Panel de Administrador</h1>
        </div>
    </header>

    <!-- Mostrar mensajes de éxito/error -->
    <?php if (isset($_GET['mensaje'])): ?>
        <div class="container">
            <p class="mensaje"><?php echo htmlspecialchars($_GET['mensaje']); ?></p>
        </div>
    <?php endif; ?>

    <!-- Botón para ir al registro de proveedores -->
    <section class="container">
        <button id="btn-registro-proveedores" class="btn btn-submit">Registro de Proveedores</button>
    </section>

    <!-- Sección de Registro de Proveedores (oculta inicialmente) -->
    <section id="registro-proveedores" class="container" style="display: none;">
        <h2>Registro de Proveedores</h2>
        <div class="form-container">
            <form id="form-proveedor" action="registrar_proveedor.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Nombre del Proveedor:</label>
                    <input type="text" name="nombre" required>
                </div>

                <div class="form-group">
                    <label>Teléfono:</label>
                    <input type="text" name="telefono" required>
                </div>

                <div class="form-group">
                    <label>Dirección:</label>
                    <input type="text" name="direccion" required>
                </div>

                <div class="form-group">
                    <label>Colonia:</label>
                    <input type="text" name="colonia" required>
                </div>

                <div class="form-group">
                    <label>Logo:</label>
                    <input type="file" name="logo" accept="image/*">
                </div>

                <button type="submit" class="btn btn-submit">Registrar Proveedor</button>
            </form>
        </div>
    </section>

    <!-- Sección de Lista de Proveedores -->
    <section id="lista-proveedores" class="container">
        <h2>Lista de Proveedores</h2>
        <ul id="proveedores-lista">
            <?php
            $query = "SELECT * FROM proveedores";
            $result = mysqli_query($conexion, $query);

            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<li class='proveedor-item'>
                            <div class='proveedor-info'>
                                <img src='" . ($row['logo'] ? $row['logo'] : 'imagenes/default_logo.png') . "' class='proveedor-logo' alt='Logo'>
                                <div class='proveedor-details'>
                                    <strong>{$row['nombre']}</strong><br>
                                    Teléfono: {$row['telefono']}<br>
                                    Dirección: {$row['direccion']}, {$row['colonia']}
                                </div>
                            </div>
                            <div class='proveedor-actions'>
                                <a href='#' class='btn btn-view ver-productos' data-proveedor-id='{$row['id']}'>Ver</a>
                                <a href='editar_proveedor.php?id={$row['id']}' class='btn btn-edit'>Modificar</a>
                                <form method='POST' style='display:inline;' onsubmit='return confirm(\"¿Está seguro de eliminar este proveedor?\")'>
                                    <input type='hidden' name='proveedor_id' value='{$row['id']}'>
                                    <button type='submit' name='eliminar_proveedor' class='btn btn-delete'>Eliminar</button>
                                </form>
                                <a href='registrar_producto.php?proveedor_id={$row['id']}' class='btn btn-submit'>Agregar Producto</a>
                            </div>
                          </li>";
                }
            } else {
                echo "<li>No hay proveedores registrados.</li>";
            }
            ?>
        </ul>
    </section>

    <!-- Modal para ver productos -->
    <div id="productos-modal" class="modal">
        <div class="modal-content">
            <h2>Productos del Proveedor</h2>
            <div id="productos-container" class="productos-list">
                <!-- Los productos se cargarán aquí dinámicamente -->
            </div>
            <button id="cerrar-modal" class="btn btn-submit" style="margin-top: 15px;">Cerrar</button>
        </div>
    </div>

    <script>
    // Mostrar/ocultar el formulario de registro de proveedores
    document.getElementById("btn-registro-proveedores").addEventListener("click", function () {
        const section = document.getElementById("registro-proveedores");
        if (section.style.display === "none") {
            section.style.display = "block";
        } else {
            section.style.display = "none";
        }
    });

    // Mostrar modal con productos del proveedor
    document.querySelectorAll('.ver-productos').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const proveedorId = this.getAttribute('data-proveedor-id');
            const modal = document.getElementById('productos-modal');
            const container = document.getElementById('productos-container');

            // Limpiar container
            container.innerHTML = '<p>Cargando productos...</p>';

            // Mostrar modal
            modal.style.display = 'flex';

            // Cargar productos via AJAX
            fetch(`obtener_productos.php?proveedor_id=${proveedorId}`)
                .then(response => response.json())
                .then(data => {
                    let html = '';

                    // Verificar si hay datos
                    if (data.length > 0) {
    html += `
        <style>
            .producto-item, .pintura-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px;
                border-bottom: 1px solid #eee;
                margin-bottom: 8px;
            }
            .item-content {
                display: flex;
                align-items: center;
                gap: 15px;
            }
            .pintura-placeholder {
                width: 50px;
                text-align: center;
                color: #666;
                font-style: italic;
            }
            .producto-img {
                width: 50px;
                height: 50px;
                object-fit: cover;
                border-radius: 4px;
            }
        </style>
    `;

    data.forEach(item => {
        // Si es producto
        if (item.rol === 'producto') {
            html += `
                <div class="producto-item">
                    <div class="item-content">
                        <img src="${item.imagen || 'pintura.jpg'}" class="producto-img" alt="${item.nombre}">
                        <div>
                            <strong>${item.nombre}</strong> - $${parseFloat(item.precio).toFixed(2)}
                        </div>
                    </div>
                    <div>
                        <a href="editar_producto.php?id=${item.id}&rol=${item.rol}" class="btn btn-edit">Editar</a>
                    </div>
                </div>
            `;
        }
        // Si es pintura
        if (item.rol === 'pintura') {
            html += `
                <div class="pintura-item">
                    <div class="item-content">
                        <div class="pintura-placeholder">Pintura</div>
                        <div>
                            <strong>  ${item.marca}</strong> ${item.tamano}L - ${item.tipo} - $${parseFloat(item.precio).toFixed(2)}
                        </div>
                    </div>
                    <div>
                        <a href="editar_producto.php?id=${item.id}&rol=${item.rol}" class="btn btn-edit">Editar</a>
                    </div>
                </div>
            `;
        }
    });
} else {
    html = '<p>Este proveedor no tiene productos ni pinturas registrados.</p>';
}

                    // Insertar el HTML generado en el contenedor adecuado
                    container.innerHTML = html;
                })
                .catch(error => {
                    container.innerHTML = '<p>Error al cargar los productos y pinturas.</p>';
                    console.error('Error:', error);
                });

        });
    });

    // Cerrar modal
    document.getElementById('cerrar-modal').addEventListener('click', function () {
        document.getElementById('productos-modal').style.display = 'none';
    });
</script>

</body>

</html>