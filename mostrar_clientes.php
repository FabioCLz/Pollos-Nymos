<?php
include("conexion.php");

$sql = "SELECT * FROM cliente";
$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Clientes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f6f8fa;
            padding: 40px;
        }
        h1 {
            color: #333;
        }
        table {
            border-collapse: collapse;
            width: 80%;
            margin: 20px 0;
            background: white;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }
        th {
            background: #efefef;
        }
    </style>
</head>
<body>
    <h1>🐔 Lista de Clientes</h1>
    <table>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Teléfono</th>
            <th>Dirección</th>
        </tr>
        <?php
        if ($resultado->num_rows > 0) {
            while ($fila = $resultado->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $fila["id_cliente"] . "</td>";
                echo "<td>" . $fila["nombre"] . "</td>";
                echo "<td>" . $fila["telefono"] . "</td>";
                echo "<td>" . $fila["direccion"] . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4'>No hay clientes registrados.</td></tr>";
        }
        $conexion->close();
        ?>
    </table>
</body>
</html>
