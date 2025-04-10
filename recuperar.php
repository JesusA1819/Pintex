<?php
session_start();
include 'includes.php'; // Incluir la conexión a la base de datos

$error = '';

// Procesar el formulario de recuperación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "Por favor ingresa tu correo electrónico.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El correo electrónico no es válido.";
    } else {
        // Verificar si el email existe en la base de datos
        $sql = 'SELECT id FROM usuarios WHERE email = ?';
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows === 0) {
            $error = "No existe una cuenta asociada a este correo electrónico.";
        } else {
            // Generar token único
            $token = bin2hex(random_bytes(32));
            $expiracion = date("Y-m-d H:i:s", strtotime("+1 hour")); // Token válido por 1 hora
            
            // Guardar token en la base de datos
            $sql = 'UPDATE usuarios SET reset_token = ?, token_expiracion = ? WHERE email = ?';
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param('sss', $token, $expiracion, $email);
            $stmt->execute();
            
            // Enviar email con el enlace de recuperación
            $asunto = "Recuperación de contraseña - Pintex";
            $mensaje = "Hola,\n\nPara restablecer tu contraseña, haz clic en el siguiente enlace:\n\n";
            $mensaje .= "http://tudominio.com/nueva_contraseña.php?token=$token\n\n";
            $mensaje .= "Este enlace expirará en 1 hora.\n\nSi no solicitaste este cambio, ignora este mensaje.";
            $headers = "From: no-reply@tudominio.com";
            
            if (mail($email, $asunto, $mensaje, $headers)) {
                $mensaje_exito = "Se ha enviado un correo con instrucciones para restablecer tu contraseña.";
            } else {
                $error = "Hubo un error al enviar el correo. Por favor intenta nuevamente.";
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
    <title>🖌 Recuperar contraseña • Pintex<</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="pintex.ico" type="image/x-icon">
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
        .recover-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .recover-container h3 {
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
    <div class="recover-container">
        <div class="text-center mb-4">
            <img class="img-fluid d-block mx-auto mb-3" src="imagen/logo.jpg" alt="Logo Pintex" style="width: 100px;">
            <h3>Recuperar Contraseña</h3>
            <p class="text-muted">Ingresa tu correo electrónico para recibir instrucciones</p>
        </div>
        
        <?php if (isset($error)): ?>
            <p class="text-danger text-center"><?php echo $error; ?></p>
        <?php endif; ?>
        
        <?php if (isset($mensaje_exito)): ?>
            <p class="text-success text-center"><?php echo $mensaje_exito; ?></p>
            <div class="text-center mt-3">
                <a href="index.php" class="btn btn-primary">Volver al inicio</a>
            </div>
        <?php else: ?>
            <form method="POST" action="recuperar.php">
                <div class="form-group">
                    <input id="email" name="email" class="form-control" type="email" placeholder="Correo Electrónico" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Enviar Instrucciones</button>
                <div class="text-center mt-3">
                    <a href="index.php" class="text-muted">Volver al inicio de sesión</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>