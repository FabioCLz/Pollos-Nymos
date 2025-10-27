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

// -------------------- AGREGAR PRODUCTO AL CARRITO --------------------
if (isset($_POST['agregar'])) {
    $id_producto = (int)$_POST['id_producto'];
    $cantidad = (int)$_POST['cantidad'];

    $stmt = $conn->prepare("SELECT stock_actual FROM inventario WHERE id_producto = ?");
    $stmt->bind_param("i", $id_producto);
    $stmt->execute();
    $res = $stmt->get_result();
    $filaStock = $res->fetch_assoc();
    $stmt->close();

    $stock_actual = $filaStock['stock_actual'] ?? 0;

    if ($cantidad <= 0) {
        echo "<script>alert('Cantidad inválida.');</script>";
    } elseif ($stock_actual <= 0) {
        echo "<script>alert('⚠️ Producto agotado.');</script>";
    } elseif ($cantidad > $stock_actual) {
        echo "<script>alert('⚠️ No hay suficiente stock. Stock actual: $stock_actual');</script>";
    } else {
        if (isset($_SESSION['carrito'][$id_producto])) {
            $_SESSION['carrito'][$id_producto] += $cantidad;
        } else {
            $_SESSION['carrito'][$id_producto] = $cantidad;
        }
    }

    header("Location: panel_empleado.php");
    exit;
}

// -------------------- ELIMINAR PRODUCTO DEL CARRITO --------------------
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    unset($_SESSION['carrito'][$id]);
    header("Location: panel_empleado.php");
    exit;
}

// -------------------- FINALIZAR VENTA --------------------
if (isset($_POST['finalizar'])) {
    $carnet_empleado = $_SESSION['empleado_carnet'];
    $carnet_cliente = trim($_POST['carnet_cliente']);
    $nombre_cliente = trim($_POST['nombre_cliente']);
    $apellido_paterno = trim($_POST['apellido_paterno']);
    $apellido_materno = trim($_POST['apellido_materno']);
    $id_metodo = (int)$_POST['metodo_pago'];

    if (empty($_SESSION['carrito'])) {
        die("El carrito está vacío, no se puede finalizar la venta.");
    }

    foreach ($_SESSION['carrito'] as $id_producto => $cantidad) {
        $res = $conn->query("SELECT stock_actual FROM inventario WHERE id_producto = $id_producto");
        $r = $res->fetch_assoc();
        $stock_actual = $r['stock_actual'] ?? 0;
        if ($cantidad > $stock_actual) {
            die("No hay suficiente stock para el producto ID $id_producto. Stock actual: $stock_actual");
        }
    }

    $check = $conn->prepare("SELECT carnet FROM usuarios WHERE carnet = ?");
    $check->bind_param("s", $carnet_cliente);
    $check->execute();
    $resultado = $check->get_result();
    $check->close();

    if ($resultado->num_rows == 0) {
        $insertCliente = $conn->prepare("INSERT INTO usuarios (carnet, nombre, apellido_paterno, apellido_materno, usuario, contrasena, rol) VALUES (?, ?, ?, ?, ?, ?, 'empleado')");
        $usuario_generico = strtolower($nombre_cliente . $carnet_cliente);
        $pass = password_hash("12345", PASSWORD_DEFAULT);
        $insertCliente->bind_param("ssssss", $carnet_cliente, $nombre_cliente, $apellido_paterno, $apellido_materno, $usuario_generico, $pass);
        $insertCliente->execute();
        $insertCliente->close();
    }

    $total = 0;
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

    $stmt = $conn->prepare("INSERT INTO ventas (carnet_usuario, total) VALUES (?, ?)");
    $stmt->bind_param("sd", $carnet_empleado, $total);
    $stmt->execute();
    $id_venta = $stmt->insert_id;
    $stmt->close();

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

        $conn->query("UPDATE inventario SET stock_actual = stock_actual - $cantidad WHERE id_producto = $id_producto");
    }

    $stmt = $conn->prepare("INSERT INTO pagos (id_venta, id_metodo, monto) VALUES (?, ?, ?)");
    $stmt->bind_param("iid", $id_venta, $id_metodo, $total);
    $stmt->execute();
    $stmt->close();

    $_SESSION['carrito'] = [];

    header("Location: ticket.php?id_venta=$id_venta");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Empleado - Pollos Nymos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #FFE082, #FFB300, #F57C00);
            min-height: 100vh;
            padding: 30px;
            font-family: 'Poppins', sans-serif;
        }

        .container {
            background: #fff;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.25);
        }

        h3, h5 {
            color: #E65100;
            font-weight: 700;
        }

        .table thead {
            background-color: #FFA000;
            color: white;
        }

        .btn-primary {
            background-color: #FB8C00;
            border: none;
        }

        .btn-primary:hover {
            background-color: #EF6C00;
        }

        .btn-success {
            background-color: #43A047;
            border: none;
        }

        .btn-success:hover {
            background-color: #2E7D32;
        }

        .btn-secondary {
            background-color: #6D4C41;
            border: none;
        }

        .btn-secondary:hover {
            background-color: #4E342E;
        }

        .badge {
            font-size: 0.8rem;
        }

        .table-danger {
            background-color: #FFCDD2 !important;
        }
    </style>
</head>
<body>
<div class="container">
    <h3 class="mb-4">👨‍🍳 Bienvenido, <?php echo $_SESSION['empleado']; ?></h3>

    <?php
    $alerta = $conn->query("SELECT COUNT(*) AS total FROM inventario WHERE stock_actual <= stock_minimo");
    $row = $alerta->fetch_assoc();
    if ($row && $row['total'] > 0): ?>
        <div class="alert alert-danger">⚠️ Hay productos con stock bajo. Avisa al administrador o revisa el inventario.</div>
    <?php endif; ?>

    <h5>📦 Lista de Productos</h5>
    <table class="table table-striped mt-3">
        <thead>
        <tr>
            <th>ID</th><th>Nombre</th><th>Descripción</th><th>Precio (Bs)</th><th>Categoría</th><th>Stock</th><th>Cantidad</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $productos = $conn->query("SELECT p.*, i.stock_actual, i.stock_minimo FROM productos p LEFT JOIN inventario i ON p.id_producto = i.id_producto");
        while ($fila = $productos->fetch_assoc()):
            $stock_actual = $fila['stock_actual'] ?? 0;
            $es_bajo = ($stock_actual <= ($fila['stock_minimo'] ?? 0));
        ?>
        <tr <?php if($es_bajo) echo 'class="table-danger"'; ?>>
            <td><?php echo $fila['id_producto']; ?></td>
            <td><?php echo $fila['nombre']; ?> <?php if($es_bajo): ?><span class="badge bg-danger ms-2">Stock bajo</span><?php endif; ?></td>
            <td><?php echo $fila['descripcion']; ?></td>
            <td><?php echo $fila['precio']; ?></td>
            <td><?php echo $fila['categoria']; ?></td>
            <td><?php echo $stock_actual; ?></td>
            <td>
                <?php if ($stock_actual <= 0): ?>
                    <span class="text-danger">Agotado</span>
                <?php else: ?>
                    <form method="POST" class="d-flex">
                        <input type="number" name="cantidad" value="1" min="1" max="<?php echo $stock_actual; ?>" class="form-control form-control-sm me-1" required>
                        <input type="hidden" name="id_producto" value="<?php echo $fila['id_producto']; ?>">
                        <button type="submit" name="agregar" class="btn btn-primary btn-sm">Agregar</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <h5 class="mt-5">🛒 Carrito de Venta</h5>
    <table class="table table-bordered">
        <thead class="table-secondary">
        <tr>
            <th>Nombre</th><th>Cantidad</th><th>Subtotal (Bs)</th><th>Acción</th>
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
        <?php endforeach; endif; ?>
        </tbody>
        <tfoot>
        <tr><th colspan="2">Total</th><th colspan="2"><?php echo number_format($total, 2); ?> Bs</th></tr>
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
            <div class="row mb-3">
                <div class="col-md-3">
                    <select name="metodo_pago" class="form-control" required>
                        <option value="">Seleccione Método de Pago</option>
                        <?php
                        $metodos = $conn->query("SELECT * FROM metodos_pago");
                        while ($m = $metodos->fetch_assoc()):
                        ?>
                            <option value="<?php echo $m['id_metodo']; ?>"><?php echo ucfirst($m['tipo_pago']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <button type="submit" name="finalizar" class="btn btn-success">✅ Finalizar Venta</button>
        </form>
    <?php endif; ?>

    <a href="../index.php" class="btn btn-secondary mt-4">Cerrar sesión</a>
</div>
</body>
</html>
