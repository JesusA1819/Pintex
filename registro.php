<?php
session_start();
include 'includes.php'; // Incluir la conexión a la base de datos

// Procesar el formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validar que los campos no estén vacíos
    if (empty($nombre) || empty($email) || empty($password)) {
        $error_registro = "Todos los campos son obligatorios.";
    } else {
        // Validar formato del correo electrónico
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_registro = "El correo electrónico no es válido.";
        } else {
            // Validar que la contraseña tenga al menos 8 caracteres, una mayúscula, un número y un carácter especial
            if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W_]/', $password)) {
                $error_registro = "La contraseña debe tener al menos 8 caracteres, incluir una mayúscula, un número y un carácter especial.";
            } else {
                // Verificar si el usuario ya existe
                $sql = 'SELECT id FROM usuarios WHERE email = ?';
                $stmt = $conexion->prepare($sql);

                if (!$stmt) {
                    die('Error en la preparación de la consulta: ' . $conexion->error);
                }

                $stmt->bind_param('s', $email);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $error_registro = "El correo electrónico ya está en uso.";
                } else {
                    // Insertar el nuevo usuario en la base de datos
                    $sql = 'INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)';
                    $stmt = $conexion->prepare($sql);

                    if (!$stmt) {
                        die('Error en la preparación de la consulta: ' . $conexion->error);
                    }

                    // Encriptar la contraseña
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    $stmt->bind_param('sss', $nombre, $email, $hashed_password);
                    $stmt->execute();

                    // Redirigir al formulario de inicio de sesión
                    header('Location: index.php');
                    exit();
                }

                $stmt->close();
            }
        }
    }
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regístrate - Pintex</title>
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
        .register-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .register-container h3 {
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
    <div class="register-container">
        <div class="text-center mb-4">
            <img class="img-fluid d-block mx-auto mb-3" src="imagen/logo.jpg" alt="Logo Pintex" style="width: 100px;">
            <h3>¡Regístrate!</h3>
            <p class="text-muted">Crea una cuenta para continuar</p>
        
        </div>
        <?php if (isset($error_registro)): ?>
            <p class="text-danger text-center"><?php echo $error_registro; ?></p>
        <?php endif; ?>
        <form method="POST" action="registro.php">
            <div class="form-group">
                <input id="nombre" name="nombre" class="form-control" type="text" placeholder="Nombre Completo" value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>" required>
            </div>
            <div class="form-group">
                <input id="email" name="email" class="form-control" type="email" placeholder="Correo Electrónico" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
            </div>
            <div class="form-group">
                <input id="password" name="password" class="form-control" type="password" placeholder="Contraseña" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Registrarse</button>
            <div class="text-center mt-3">
                <a href="index.php" class="text-muted">¿Ya tienes una cuenta? Inicia sesión</a>
    
        </form>
    </div>
</body>
</html>
