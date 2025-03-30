<?php
session_start(); // Iniciar sesión

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['user_id']) || !$_SESSION['es_administrador']) {
    header('Location: index.php');
    exit();
}

include 'includes.php'; // Incluir la conexión a la base de datos

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar si la conexión a la base de datos está activa
    if (!$conexion) {
        die("Error: No se pudo conectar a la base de datos.");
    }

    // Recuperar los datos del formulario
    $proovedor_id = $_POST['proovedor_id'];
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $imagen = $_FILES['imagen']['name'];
    $ruta = "uploads/" . basename($imagen);

    // Mover la imagen al directorio "uploads"
    if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta)) {
        die("Error: No se pudo subir la imagen.");
    }

    // Insertar el producto en la base de datosº
    $sql = "INSERT INTO productos (provedor_id, nombre, precio, tipo, imagen) 
            VALUES (?, ?, ?, '1', ?)";
    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conexion->error);
    }

    // Vincular los parámetros
    $stmt->bind_param("isds", $proovedor_id, $nombre, $precio, $ruta);

    // Ejecutar la consulta
    if ($stmt->execute()) {
        // Redirigir a la página de administración con un mensaje de éxito
        header('Location: adm.php?mensaje=Herramienta registrada correctamente');
        exit();
    } else {
        die("Error al ejecutar la consulta: " . $stmt->error);
    }

    // Cerrar la conexión
    $stmt->close();
    $conexion->close();
} else {
    // Si no se envió el formulario por POST, redirigir al formulario
    header('Location: adm.php');
    exit();
}
?>
