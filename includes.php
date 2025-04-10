<?php
// includes.php

// Credenciales de acceso a la base de datos
$DATABASE_HOST = 'localhost';      // Servidor de la base de datos
$DATABASE_USER = 'root';           // Usuario de la base de datos
$DATABASE_PASS = 'root';           // Contraseña de la base de datos
$DATABASE_NAME = 'pintex_db';      // Nombre de la base de datos

// Conexión a la base de datos
$conexion = mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);

// Verificar si hay errores en la conexión
if (!$conexion) {
    die('Error: No se pudo conectar a la base de datos. ' . mysqli_connect_error());
}
// Elimina o comenta la siguiente línea:
// else {
//     echo "Conexión a la base de datos establecida correctamente.";
// }
?>