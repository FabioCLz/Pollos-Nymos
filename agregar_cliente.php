<?php
include("conexion.php"); // Usamos la conexión existente

// Verificar si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'];

    // Insertar en la base de datos
    $sql = "INSERT INTO cliente (nombre, telefono, direccion) VALUES ('$nombre', '$telefono', '$direccion')";

    if ($conexion->query($sql) === TRUE) {
        echo "<script>alert('✅ Cliente agregado correctamente'); window.location='mostrar_clientes.php';</script>";
    } else {
        echo "❌ Error: " . $conexion->error;
    }

    $conexion->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Cliente</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f6f8fa;
            padding: 40px;
        }
        form {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            width: 350px;
        }
        input[type="text"], input[type="tel"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 12px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        input[type="submit"] {
            background-color: #28a745;
            border: none;
            color: white;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }
        input[type="submit"]:hover {
            background-color: #218838;
        }
        a {
            display: inline-block;
            margin-top: 10px;
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>🐔 Agregar Nuevo Cliente</h1>
    <form action="" method="POST">
        <label>Nombre:</label><br>
        <input type="text" name="nombre" required><br>

        <label>Teléfono:</label><br>
        <input type="tel" name="telefono"><br>

        <label>Dirección:</label><br>
        <input type="text" name="direccion"><br>

        <input type="submit" value="Guardar Cliente">
    </form>

    <a href="mostrar_clientes.php">← Volver a la lista de clientes</a>
</body>
</html>
