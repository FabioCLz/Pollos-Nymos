<!-- productos.php -->
<?php include("conexion.php"); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - POLLOS NYMOS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>🐔 Inventario de POLLOS NYMOS</h1>
    </header>
    <nav>
        <ul>
            <li><a href="index.php">Inicio</a></li>
            <li><a href="pedidos.php">Pedidos</a></li>
            <li><a href="clientes.php">Clientes</a></li>
            <li><a href="ventas.php">Ventas</a></li>
        </ul>
    </nav>

    <main>
        <h2>Productos Disponibles</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Precio</th>
                <th>Cantidad Disponible</th>
            </tr>
            <?php
            $sql = "SELECT * FROM producto";
            $resultado = $conexion->query($sql);
            while ($producto = $resultado->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $producto['id_producto'] . "</td>";
                echo "<td>" . $producto['nombre'] . "</td>";
                echo "<td>" . $producto['precio'] . "</td>";
                echo "<td>" . $producto['cantidad_disponible'] . "</td>";
                echo "</tr>";
            }
            ?>
        </table>
    </main>
</body>
</html>
