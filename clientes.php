<!-- clientes.php -->
<?php include("conexion.php"); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - POLLOS NYMOS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>🐔 Gestionar Clientes</h1>
    </header>
    <nav>
        <ul>
            <li><a href="index.php">Inicio</a></li>
            <li><a href="productos.php">Inventario</a></li>
            <li><a href="pedidos.php">Pedidos</a></li>
            <li><a href="ventas.php">Ventas</a></li>
        </ul>
    </nav>

    <main>
        <h2>Registrar Nuevo Cliente</h2>
        <form action="crear_cliente.php" method="POST">
            <label for="nombre">Nombre:</label>
            <input type="text" name="nombre" id="nombre" required><br>
            <label for="telefono">Teléfono:</label>
            <input type="text" name="telefono" id="telefono"><br>
            <label for="direccion">Dirección:</label>
            <input type="text" name="direccion" id="direccion"><br>
            <button type="submit">Agregar Cliente</button>
        </form>
    </main>
</body>
</html>
