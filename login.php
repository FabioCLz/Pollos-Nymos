<?php
session_start();

// Si ya está logueado, redirigir según rol
if (isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit;
}

include 'conexion.php';
$mensaje = '';

// Generar token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensaje = 'Error: Token inválido.';
    } else {
        $usuario = trim($_POST['usuario']);
        $contrasena = $_POST['contrasena'];

        if (empty($usuario) || empty($contrasena)) {
            $mensaje = 'Usuario y contraseña son obligatorios.';
        } else {
            $stmt = $conexion->prepare("SELECT id_usuario, nombre, contrasena, rol FROM usuarios WHERE usuario = ?");
            if (!$stmt) {
                error_log("Error prepare login: " . $conexion->error, 3, 'errors.log');
                $mensaje = 'Error del sistema.';
            } else {
                $stmt->bind_param("s", $usuario);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    if (password_verify($contrasena, $user['contrasena'])) {
                        // Login exitoso
                        $_SESSION['id_usuario'] = $user['id_usuario'];
                        $_SESSION['nombre'] = $user['nombre'];
                        $_SESSION['rol'] = $user['rol'];

                        session_regenerate_id(true); // Seguridad
                        header("Location: index.php");
                        exit;
                    } else {
                        $mensaje = 'Contraseña incorrecta.';
                    }
                } else {
                    $mensaje = 'Usuario no encontrado.';
                }
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pollos Nymos - Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      font-family: 'Poppins', sans-serif;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .login-card {
      background: white;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 15px 35px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 420px;
    }
    .logo {
      text-align: center;
      font-size: 36px;
      font-weight: bold;
      color: #e74c3c;
      margin-bottom: 10px;
    }
    .subtitle {
      text-align: center;
      color: #7f8c8d;
      margin-bottom: 30px;
    }
    .form-control {
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #ddd;
    }
    .form-control:focus {
      border-color: #e74c3c;
      box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.25);
    }
    .btn-login {
      background: #e74c3c;
      color: white;
      padding: 12px;
      font-weight: bold;
      border-radius: 8px;
      width: 100%;
      font-size: 16px;
      transition: 0.3s;
    }
    .btn-login:hover {
      background: #c0392b;
    }
    .mensaje {
      padding: 12px;
      margin: 15px 0;
      border-radius: 8px;
      text-align: center;
      font-weight: 500;
    }
    .mensaje.error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="logo">POLLOS NYMOS</div>
    <p class="subtitle">Sistema de Gestión</p>

    <?php if ($mensaje): ?>
      <div class="mensaje error"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
      
      <div class="mb-3">
        <label class="form-label">Usuario</label>
        <input type="text" class="form-control" name="usuario" required autofocus>
      </div>

      <div class="mb-3">
        <label class="form-label">Contraseña</label>
        <input type="password" class="form-control" name="contrasena" required>
      </div>

      <button type="submit" class="btn-login">Iniciar Sesión</button>
    </form>
  </div>
</body>
</html>