<?php
session_start();

// Destruir la sesión
session_destroy();

// Redirigir al formulario de login
header('Location: index.php');
exit();
?>