<?php
session_start();
include 'includes.php'; // Incluir la conexión a la base de datos

// Procesar el formulario de inicio de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validar que los campos no estén vacíos
    if (empty($email) || empty($password)) {
        $error_login = "Todos los campos son obligatorios.";
    } else {
        // Verificar si el usuario existe
        $sql = 'SELECT id, nombre, password FROM usuarios WHERE email = ?';
        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            die('Error en la preparación de la consulta: ' . $conexion->error);
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $nombre, $hashed_password);
            $stmt->fetch();

            // Verificar la contraseña
            if (password_verify($password, $hashed_password)) {
                // Iniciar sesión
                $_SESSION['user_id'] = $id;
                $_SESSION['nombre'] = $nombre;
                $_SESSION['email'] = $email;

                // Verificar si el usuario es administrador
                if (strpos($email, '@pintex.com') !== false) {
                    $_SESSION['es_administrador'] = true; // Establecer como administrador
                    header('Location: adm.php'); // Redirigir a adm.php
                    exit(); // Asegurarse de que el script se detenga aquí
                } else {
                    $_SESSION['es_administrador'] = false; // No es administrador
                    header('Location: calculadora.html'); // Redirigir a calculadora.html
                    exit(); // Asegurarse de que el script se detenga aquí
                }
            } else {
                $error_login = "Contraseña incorrecta.";
            }
        } else {
            $error_login = "El correo electrónico no está registrado.";
        }

        $stmt->close();
    }
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Pintex</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0d6efd, #198754);
            font-family: Arial, sans-serif;
            color: #fff;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .login-container h3 {
            color: #333;
            margin-bottom: 1.5rem;
        }
        .form-control {
            border-radius: 5px;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 1rem;
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 5px rgba(13, 110, 253, 0.5);
        }
        .btn-primary {
            background: linear-gradient(135deg, #0d6efd, #198754);
            border: none;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            transition: background 0.3s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #0b5ed7, #157347);
        }
        .text-muted {
            color: #666 !important;
        }
        .text-danger {
            color: #dc3545 !important;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="text-center mb-4">
            <img class="img-fluid d-block mx-auto mb-3" src="imagen/logo.jpg" alt="Logo Pintex" style="width: 100px;">
            <h3>¡Inicia Sesión!</h3>
            <p class="text-muted">Ingresa tus credenciales para continuar</p>
        </div>
        <?php if (isset($error_login)): ?>
            <p class="text-danger text-center"><?php echo $error_login; ?></p>
        <?php endif; ?>
        <form method="POST" action="index.php">
            <div class="form-group">
                <input id="email" name="email" class="form-control" type="email" placeholder="Correo Electrónico" required>
            </div>
            <div class="form-group">
                <input id="password" name="password" class="form-control" type="password" placeholder="Contraseña" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Iniciar Sesión</button>
            <div class="text-center mt-3">
                <a href="registro.php" class="text-muted">¿No tienes una cuenta? Regístrate</a>
               
            </div>
        </form>
    </div>
</body>
</html>