<?php
session_start();
if (!isset($_SESSION['empleado']) || !isset($_SESSION['empleado_carnet'])) {
    header("Location: ../login_empleado.php");
    exit;
}

include("../conexion.php");

// Inicializar carrito
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Agregar producto al carrito
if (isset($_POST['agregar'])) {
    $id_producto = $_POST['id_producto'];
    $cantidad = (int)$_POST['cantidad'];

    if (isset($_SESSION['carrito'][$id_producto])) {
        $_SESSION['carrito'][$id_producto] += $cantidad;
    } else {
        $_SESSION['carrito'][$id_producto] = $cantidad;
    }

    header("Location: panel_empleado.php");
    exit;
}

// Eliminar producto del carrito
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    unset($_SESSION['carrito'][$id]);
    header("Location: panel_empleado.php");
    exit;
}

// FINALIZAR VENTA
if (isset($_POST['finalizar'])) {
    $carnet_empleado = $_SESSION['empleado_carnet'];

    // Datos del cliente
    $carnet_cliente = trim($_POST['carnet_cliente']);
    $nombre_cliente = trim($_POST['nombre_cliente']);
    $apellido_paterno = trim($_POST['apellido_paterno']);
    $apellido_materno = trim($_POST['apellido_materno']);

    if (empty($_SESSION['carrito'])) {
        die("El carrito está vacío, no se puede finalizar la venta.");
    }

    // Registrar cliente si no existe
    $check = $conn->prepare("SELECT carnet FROM usuarios WHERE carnet = ?");
    $check->bind_param("s", $carnet_cliente);
    $check->execute();
    $resultado = $check->get_result();

    if ($resultado->num_rows == 0) {
        // Insertar cliente con rol 'empleado' temporal o 'empleado'
        $insertCliente = $conn->prepare("INSERT INTO usuarios (carnet, nombre, apellido_paterno, apellido_materno, usuario, contrasena, rol) VALUES (?, ?, ?, ?, ?, ?, 'empleado')");
        $usuario_generico = strtolower($nombre_cliente . $carnet_cliente);
        $pass = password_hash("12345", PASSWORD_DEFAULT);
        $insertCliente->bind_param("ssssss", $carnet_cliente, $nombre_cliente, $apellido_paterno, $apellido_materno, $usuario_generico, $pass);
        $insertCliente->execute();
        $insertCliente->close();
    }

    $total = 0;

    // Calcular total
    foreach ($_SESSION['carrito'] as $id_producto => $cantidad) {
        $stmt = $conn->prepare("SELECT precio FROM productos WHERE id_producto = ?");
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $res = $stmt->get_result();
        $fila = $res->fetch_assoc();
        $subtotal = $fila['precio'] * $cantidad;
        $total += $subtotal;
        $stmt->close();
    }

    // Insertar venta
    $stmt = $conn->prepare("INSERT INTO ventas (carnet_usuario, total) VALUES (?, ?)");
    $stmt->bind_param("sd", $carnet_empleado, $total);
    $stmt->execute();
    $id_venta = $stmt->insert_id;
    $stmt->close();

    // Insertar detalle venta
    foreach ($_SESSION['carrito'] as $id_producto => $cantidad) {
        $stmt = $conn->prepare("SELECT precio FROM productos WHERE id_producto = ?");
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $res = $stmt->get_result();
        $fila = $res->fetch_assoc();
        $subtotal = $fila['precio'] * $cantidad;
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO detalle_venta (id_venta, id_producto, cantidad, subtotal) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $id_venta, $id_producto, $cantidad, $subtotal);
        $stmt->execute();
        $stmt->close();
    }

    $_SESSION['carrito'] = [];

    header("Location: ticket.php?id_venta=$id_venta");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Empleado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
<div class="container">
    <h3 class="mb-4">👨‍🍳 Bienvenido, <?php echo $_SESSION['empleado']; ?></h3>

    <h5>Lista de Productos</h5>
    <table class="table table-striped mt-3">
        <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Precio (Bs)</th>
            <th>Categoría</th>
            <th>Cantidad</th>
            <th>Acción</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $productos = $conn->query("SELECT * FROM productos");
        while ($fila = $productos->fetch_assoc()):
            ?>
            <tr>
                <td><?php echo $fila['id_producto']; ?></td>
                <td><?php echo $fila['nombre']; ?></td>
                <td><?php echo $fila['descripcion']; ?></td>
                <td><?php echo $fila['precio']; ?></td>
                <td><?php echo $fila['categoria']; ?></td>
                <td>
                    <form method="POST" class="d-flex">
                        <input type="number" name="cantidad" value="1" min="1" class="form-control form-control-sm me-1">
                        <input type="hidden" name="id_producto" value="<?php echo $fila['id_producto']; ?>">
                        <button type="submit" name="agregar" class="btn btn-primary btn-sm">Agregar</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <h5 class="mt-5">🛒 Carrito de Venta</h5>
    <table class="table table-bordered">
        <thead class="table-secondary">
        <tr>
            <th>Nombre</th>
            <th>Cantidad</th>
            <th>Subtotal (Bs)</th>
            <th>Acción</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $total = 0;
        if (!empty($_SESSION['carrito'])):
            foreach ($_SESSION['carrito'] as $id_producto => $cantidad):
                $res = $conn->query("SELECT nombre, precio FROM productos WHERE id_producto = $id_producto");
                $prod = $res->fetch_assoc();
                $subtotal = $prod['precio'] * $cantidad;
                $total += $subtotal;
                ?>
                <tr>
                    <td><?php echo $prod['nombre']; ?></td>
                    <td><?php echo $cantidad; ?></td>
                    <td><?php echo number_format($subtotal, 2); ?></td>
                    <td><a href="panel_empleado.php?eliminar=<?php echo $id_producto; ?>" class="btn btn-danger btn-sm">Eliminar</a></td>
                </tr>
            <?php endforeach;
        endif; ?>
        </tbody>
        <tfoot>
        <tr>
            <th colspan="2">Total</th>
            <th colspan="2"><?php echo number_format($total, 2); ?> Bs</th>
        </tr>
        </tfoot>
    </table>

    <?php if (!empty($_SESSION['carrito'])): ?>
        <h5 class="mt-4">🧍‍♂️ Datos del Cliente</h5>
        <form method="POST">
            <div class="row mb-3">
                <div class="col-md-3">
                    <input type="text" name="carnet_cliente" class="form-control" placeholder="Carnet del cliente" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="nombre_cliente" class="form-control" placeholder="Nombre" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="apellido_paterno" class="form-control" placeholder="Apellido paterno" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="apellido_materno" class="form-control" placeholder="Apellido materno">
                </div>
            </div>
            <button type="submit" name="finalizar" class="btn btn-success">Finalizar Venta</button>
        </form>
    <?php endif; ?>

    <a href="../index.php" class="btn btn-secondary mt-4">Cerrar sesión</a>
</div>
</body>
</html>
