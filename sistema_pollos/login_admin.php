<?php
session_start();
include("conexion.php");

if(isset($_POST['login'])){
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];

    // Consultar usuario admin
    $stmt = $conn->prepare("SELECT carnet, nombre, apellido_paterno, contrasena, rol FROM usuarios WHERE usuario = ? AND rol='admin'");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res->num_rows > 0){
        $fila = $res->fetch_assoc();

        // Comparar contraseñas (texto plano, luego puedes usar hash)
        if($contrasena === $fila['contrasena']){
            $_SESSION['admin'] = $fila['nombre'] . ' ' . $fila['apellido_paterno'];
            $_SESSION['admin_carnet'] = $fila['carnet'];
            header("Location: admin/panel_admin.php");
            exit;
        } else {
            $error = "❌ Contraseña incorrecta";
        }
    } else {
        $error = "⚠️ Usuario no encontrado o no es administrador";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login Administrador - Pollos Nymos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            /* Fondo degradado cálido amarillo-naranja */
            background: linear-gradient(135deg, #FFD54F, #FFB300, #FB8C00);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Poppins', sans-serif;
        }

        .login-box {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h3 {
            color: #E65100;
            font-weight: bold;
            margin-bottom: 25px;
        }

        .btn-primary {
            background-color: #FF9800;
            border: none;
        }

        .btn-primary:hover {
            background-color: #F57C00;
        }

        label {
            font-weight: 500;
            color: #424242;
        }

        .alert {
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h3>👑 Login Administrador</h3>
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="POST">
            <div class="mb-3 text-start">
                <label>Usuario:</label>
                <input type="text" name="usuario" class="form-control" required>
            </div>
            <div class="mb-3 text-start">
                <label>Contraseña:</label>
                <input type="password" name="contrasena" class="form-control" required>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100">Ingresar</button>
        </form>
    </div>
</body>
</html>
