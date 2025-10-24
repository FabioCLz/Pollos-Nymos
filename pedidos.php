<!-- pedidos.php -->
<?php include("conexion.php"); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos - POLLOS NYMOS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>🐔 Pedidos de POLLOS NYMOS</h1>
    </header>
    <nav>
        <ul>
            <li><a href="index.php">Inicio</a></li>
            <li><a href="productos.php">Inventario</a></li>
            <li><a href="clientes.php">Clientes</a></li>
            <li><a href="ventas.php">Ventas</a></li>
        </ul>
    </nav>

    <main>
        <h2>Gestionar Pedidos</h2>
        <form action="crear_pedido.php" method="POST">
            <label for="id_cliente">Cliente:</label>
            <input type="number" name="id_cliente" id="id_cliente" required><br>
            <label for="id_producto">Producto:</label>
            <input type="number" name="id_producto" id="id_producto" required><br>
            <label for="cantidad">Cantidad:</label>
            <input type="number" name="cantidad" id="cantidad" required><br>
            <button type="submit">Agregar Pedido</button>
        </form>

        <h3>Pedidos Pendientes</h3>
        <table>
            <tr>
                <th>ID Pedido</th>
                <th>Cliente</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Estado</th>
            </tr>
            <?php
            $sql = "SELECT p.id_pedido, c.nombre AS cliente, pr.nombre AS producto, p.cantidad, p.estado
                    FROM pedido p
                    JOIN cliente c ON p.id_cliente = c.id_cliente
                    JOIN producto pr ON p.id_producto = pr.id_producto
                    WHERE p.estado = 'Pendiente'";

            $resultado = $conexion->query($sql);
            while ($pedido = $resultado->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $pedido['id_pedido'] . "</td>";
                echo "<td>" . $pedido['cliente'] . "</td>";
                echo "<td>" . $pedido['producto'] . "</td>";
                echo "<td>" . $pedido['cantidad'] . "</td>";
                echo "<td>" . $pedido['estado'] . "</td>";
                echo "</tr>";
            }
            ?>
        </table>
    </main>
</body>
</html>
