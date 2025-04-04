<?php
session_start();
include 'includes.php'; // Incluir la conexión a la base de datos

$error = '';
$token_valido = false;

// Verificar si hay un token en la URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verificar si el token es válido y no ha expirado
    $sql = 'SELECT id FROM usuarios WHERE reset_token = ? AND token_expiracion > NOW()';
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows === 1) {
        $token_valido = true;
        
        // Procesar el formulario de nueva contraseña
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Validar que las contraseñas coincidan
            if ($password !== $confirm_password) {
                $error = "Las contraseñas no coinciden.";
            } 
            // Validar requisitos de contraseña
            elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W_]/', $password)) {
                $error = "La contraseña debe tener al menos 8 caracteres, incluir una mayúscula, un número y un carácter especial.";
            } else {
                // Actualizar la contraseña en la base de datos
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = 'UPDATE usuarios SET password = ?, reset_token = NULL, token_expiracion = NULL WHERE reset_token = ?';
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param('ss', $hashed_password, $token);
                $stmt->execute();
                
                $mensaje_exito = "Tu contraseña ha sido actualizada correctamente. Ahora puedes iniciar sesión.";
                $token_valido = false; // Para mostrar el mensaje de éxito
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
    <title>Nueva Contraseña - Pintex</title>
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
        .new-password-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .new-password-container h3 {
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
        .text-success {
            color: #198754 !important;
        }
    </style>
</head>
<body>
    <div class="new-password-container">
        <div class="text-center mb-4">
            <img class="img-fluid d-block mx-auto mb-3" src="imagen/logo.jpg" alt="Logo Pintex" style="width: 100px;">
            <h3>Nueva Contraseña</h3>
        </div>
        
        <?php if (isset($mensaje_exito)): ?>
            <p class="text-success text-center"><?php echo $mensaje_exito; ?></p>
            <div class="text-center mt-3">
                <a href="index.php" class="btn btn-primary">Iniciar Sesión</a>
            </div>
        <?php elseif (!$token_valido): ?>
            <p class="text-danger text-center">El enlace de recuperación no es válido o ha expirado.</p>
            <div class="text-center mt-3">
                <a href="recuperar.php" class="text-muted">Solicitar nuevo enlace de recuperación</a>
            </div>
        <?php else: ?>
            <?php if (isset($error)): ?>
                <p class="text-danger text-center"><?php echo $error; ?></p>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <input id="password" name="password" class="form-control" type="password" placeholder="Nueva Contraseña" required>
                </div>
                <div class="form-group">
                    <input id="confirm_password" name="confirm_password" class="form-control" type="password" placeholder="Confirmar Nueva Contraseña" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Actualizar Contraseña</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>