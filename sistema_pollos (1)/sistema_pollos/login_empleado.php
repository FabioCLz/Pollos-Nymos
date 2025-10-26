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

        // 🔹 Comparación simple de contraseña
        if($contrasena === $fila['contrasena']){
            $_SESSION['empleado'] = $fila['nombre'] . ' ' . $fila['apellido_paterno'];
            $_SESSION['empleado_carnet'] = $fila['carnet'];
            header("Location: empleado/panel_empleado.php");
            exit;
        } else {
            $error = "Contraseña incorrecta";
        }
    } else {
        $error = "Usuario no encontrado o no es empleado";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login Empleado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
    <div class="container" style="max-width:400px;">
        <h3 class="mb-4">👨‍🍳 Login Empleado</h3>
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
            <button type="submit" name="login" class="btn btn-primary w-100">Ingresar</button>
        </form>
    </div>
</body>
</html>
