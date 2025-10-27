<?php
session_start();
include("conexion.php");

if(isset($_POST['login'])){
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];

    // Consultar usuario en la DB
    $stmt = $conn->prepare("SELECT carnet, nombre, apellido_paterno, contrasena, rol FROM usuarios WHERE usuario = ? AND rol='empleado'");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res->num_rows > 0){
        $fila = $res->fetch_assoc();

        // Comparación simple de contraseña (texto plano)
        if($contrasena === $fila['contrasena']){
            $_SESSION['empleado'] = $fila['nombre'] . ' ' . $fila['apellido_paterno'];
            $_SESSION['empleado_carnet'] = $fila['carnet'];
            header("Location: empleado/panel_empleado.php");
            exit;
        } else {
            $error = "⚠️ Contraseña incorrecta";
        }
    } else {
        $error = "⚠️ Usuario no encontrado o no es empleado";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login Empleado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #FFD54F, #FFB300, #FF8F00);
            background-size: 400% 400%;
            animation: moverFondo 10s ease infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        @keyframes moverFondo {
            0% {background-position: 0% 50%;}
            50% {background-position: 100% 50%;}
            100% {background-position: 0% 50%;}
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }

        h3 {
            text-align: center;
            color: #ff6f00;
            font-weight: bold;
            margin-bottom: 1.5rem;
        }

        .btn-ingresar {
            background-color: #ff8f00;
            border: none;
            transition: 0.3s;
            font-weight: bold;
        }

        .btn-ingresar:hover {
            background-color: #ff6f00;
        }

        label {
            color: #333;
            font-weight: 500;
        }

        .alert {
            font-size: 14px;
            padding: 0.5rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h3>👨‍🍳 Login Empleado</h3>
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="POST">
            <div class="mb-3">
                <label>Usuario:</label>
                <input type="text" name="usuario" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Contraseña:</label>
                <input type="password" name="contrasena" class="form-control" required>
            </div>
            <button type="submit" name="login" class="btn btn-ingresar w-100 text-white">Ingresar</button>
        </form>
    </div>
</body>
</html>
