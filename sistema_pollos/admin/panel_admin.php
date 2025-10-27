<?php
session_start();
if(!isset($_SESSION['admin']) || !isset($_SESSION['admin_carnet'])){
    header("Location: ../login_admin.php");
    exit;
}

include("../conexion.php");

// -------------------- ACCIONES --------------------
// ---------------- USUARIOS ----------------
if(isset($_POST['agregar_usuario'])){
    $carnet = $_POST['carnet'];
    $nombre = $_POST['nombre'];
    $apellido_paterno = $_POST['apellido_paterno'];
    $apellido_materno = $_POST['apellido_materno'];
    $telefono = $_POST['telefono'];
    $usuario = $_POST['usuario'];
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
    $rol = $_POST['rol'];

    $stmt = $conn->prepare("INSERT INTO usuarios (carnet,nombre,apellido_paterno,apellido_materno,telefono,usuario,contrasena,rol) VALUES (?,?,?,?,?,?,?,?)");
    if($stmt){
        $stmt->bind_param("ssssssss",$carnet,$nombre,$apellido_paterno,$apellido_materno,$telefono,$usuario,$contrasena,$rol);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: panel_admin.php?opcion=usuarios");
    exit;
}

if(isset($_GET['eliminar_usuario'])){
    $carnet = $_GET['eliminar_usuario'];
    if($carnet != $_SESSION['admin_carnet']){
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE carnet = ?");
        if($stmt){
            $stmt->bind_param("s",$carnet);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: panel_admin.php?opcion=usuarios");
    exit;
}

// ---------------- PRODUCTOS ----------------
if(isset($_POST['agregar_producto'])){
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $categoria = $_POST['categoria'];

    $stmt = $conn->prepare("INSERT INTO productos (nombre, descripcion, precio, categoria) VALUES (?,?,?,?)");
    if($stmt){
        $stmt->bind_param("ssds",$nombre,$descripcion,$precio,$categoria);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: panel_admin.php?opcion=productos");
    exit;
}

if(isset($_GET['eliminar_producto'])){
    $id_producto = $_GET['eliminar_producto'];
    $stmt = $conn->prepare("DELETE FROM productos WHERE id_producto = ?");
    if($stmt){
        $stmt->bind_param("i",$id_producto);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: panel_admin.php?opcion=productos");
    exit;
}

// ---------------- INVENTARIO ----------------
if(isset($_POST['agregar_inventario'])){
    $id_producto = $_POST['id_producto'];
    $stock_actual = $_POST['stock_actual'];
    $stock_minimo = $_POST['stock_minimo'];

    $stmt = $conn->prepare("INSERT INTO inventario (id_producto, stock_actual, stock_minimo) VALUES (?,?,?)");
    if($stmt){
        $stmt->bind_param("iii",$id_producto,$stock_actual,$stock_minimo);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: panel_admin.php?opcion=inventario");
    exit;
}

if(isset($_POST['editar_inventario'])){
    $id_inventario = $_POST['id_inventario'];
    $stock_actual = $_POST['stock_actual'];
    $stock_minimo = $_POST['stock_minimo'];

    $stmt = $conn->prepare("UPDATE inventario SET stock_actual=?, stock_minimo=?, fecha_actualizacion=NOW() WHERE id_inventario=?");
    if($stmt){
        $stmt->bind_param("iii",$stock_actual,$stock_minimo,$id_inventario);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: panel_admin.php?opcion=inventario");
    exit;
}

if(isset($_GET['eliminar_inventario'])){
    $id_inventario = $_GET['eliminar_inventario'];
    $stmt = $conn->prepare("DELETE FROM inventario WHERE id_inventario=?");
    if($stmt){
        $stmt->bind_param("i",$id_inventario);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: panel_admin.php?opcion=inventario");
    exit;
}

// -------------------- SECCIÓN ACTIVA --------------------
$opcion = isset($_GET['opcion']) ? $_GET['opcion'] : '';

// --- ALERTA: contar productos en o por debajo del stock mínimo
$alerta_stock = $conn->query("SELECT COUNT(*) AS total FROM inventario WHERE stock_actual <= stock_minimo");
$row_alerta = $alerta_stock->fetch_assoc();
$tiene_alerta = ($row_alerta && $row_alerta['total'] > 0);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel Administrador</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
<body class="p-4">
<div class="container">

<h3 class="mb-4">👑 Bienvenido, <?php echo $_SESSION['admin']; ?></h3>

<?php if($tiene_alerta): ?>
    <div class="alert alert-danger">⚠️ Atención: hay productos con stock en o por debajo del mínimo. Revisa la sección de Inventario.</div>
<?php endif; ?>

<div class="mb-4">
    <a href="panel_admin.php?opcion=usuarios" class="btn btn-primary">Usuarios</a>
    <a href="panel_admin.php?opcion=productos" class="btn btn-success">Productos</a>
    <a href="panel_admin.php?opcion=ventas" class="btn btn-warning">Ventas</a>
    <a href="panel_admin.php?opcion=inventario" class="btn btn-info">Inventario</a>
</div>

<!-- ================= USUARIOS ================= -->
<?php if($opcion == 'usuarios'): 
$usuarios = $conn->query("SELECT * FROM usuarios");
?>
<div class="card p-4 mb-4 shadow-sm">
<h5>Usuarios</h5>
<table class="table table-striped">
<thead class="table-dark">
<tr>
<th>Carnet</th><th>Nombre</th><th>Apellido Paterno</th><th>Apellido Materno</th><th>Teléfono</th><th>Usuario</th><th>Rol</th><th>Acción</th>
</tr>
</thead>
<tbody>
<?php while($fila = $usuarios->fetch_assoc()): ?>
<tr>
<td><?php echo $fila['carnet']; ?></td>
<td><?php echo $fila['nombre']; ?></td>
<td><?php echo $fila['apellido_paterno']; ?></td>
<td><?php echo $fila['apellido_materno']; ?></td>
<td><?php echo $fila['telefono']; ?></td>
<td><?php echo $fila['usuario']; ?></td>
<td><?php echo $fila['rol']; ?></td>
<td>
<?php if($fila['carnet'] != $_SESSION['admin_carnet']): ?>
<a href="panel_admin.php?opcion=usuarios&eliminar_usuario=<?php echo $fila['carnet']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar usuario?')">Eliminar</a>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<h6>Agregar Usuario</h6>
<form method="POST" class="row g-2 mb-0">
<input type="text" name="carnet" placeholder="Carnet" class="form-control col" required>
<input type="text" name="nombre" placeholder="Nombre" class="form-control col" required>
<input type="text" name="apellido_paterno" placeholder="Apellido Paterno" class="form-control col" required>
<input type="text" name="apellido_materno" placeholder="Apellido Materno" class="form-control col" required>
<input type="text" name="telefono" placeholder="Teléfono" class="form-control col">
<input type="text" name="usuario" placeholder="Usuario" class="form-control col" required>
<input type="text" name="contrasena" placeholder="Contraseña" class="form-control col" required>
<select name="rol" class="form-control col">
<option value="empleado">Empleado</option>
<option value="admin">Admin</option>
</select>
<button type="submit" name="agregar_usuario" class="btn btn-primary col">Agregar</button>
</form>
</div>

<!-- ================= PRODUCTOS ================= -->
<?php elseif($opcion == 'productos'):
$productos = $conn->query("SELECT * FROM productos");
?>
<div class="card p-4 mb-4 shadow-sm">
<h5>Productos</h5>
<table class="table table-striped">
<thead class="table-dark">
<tr>
<th>ID</th><th>Nombre</th><th>Descripción</th><th>Precio</th><th>Categoría</th><th>Acción</th>
</tr>
</thead>
<tbody>
<?php while($fila = $productos->fetch_assoc()): ?>
<tr>
<td><?php echo $fila['id_producto']; ?></td>
<td><?php echo $fila['nombre']; ?></td>
<td><?php echo $fila['descripcion']; ?></td>
<td><?php echo $fila['precio']; ?></td>
<td><?php echo $fila['categoria']; ?></td>
<td>
<a href="panel_admin.php?opcion=productos&eliminar_producto=<?php echo $fila['id_producto']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar producto?')">Eliminar</a>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<h6>Agregar Producto</h6>
<form method="POST" class="row g-2 mb-0">
<input type="text" name="nombre" placeholder="Nombre" class="form-control col" required>
<input type="text" name="descripcion" placeholder="Descripción" class="form-control col">
<input type="number" name="precio" placeholder="Precio" class="form-control col" step="0.01" required>
<select name="categoria" class="form-control col">
<option value="pollo">Pollo</option>
<option value="bebida">Bebida</option>
<option value="acompanamiento">Acompañamiento</option>
</select>
<button type="submit" name="agregar_producto" class="btn btn-success col">Agregar</button>
</form>
</div>

<!-- ================= INVENTARIO ================= -->
<?php elseif($opcion == 'inventario'):
$inventario = $conn->query("SELECT i.id_inventario, p.nombre, i.stock_actual, i.stock_minimo, i.fecha_actualizacion FROM inventario i INNER JOIN productos p ON i.id_producto = p.id_producto");
?>
<div class="card p-4 mb-4 shadow-sm">
<h5>Inventario</h5>
<table class="table table-striped">
<thead class="table-dark">
<tr>
<th>ID Inventario</th><th>Producto</th><th>Stock Actual</th><th>Stock Mínimo</th><th>Última Actualización</th><th>Acción</th>
</tr>
</thead>
<tbody>
<?php while($fila = $inventario->fetch_assoc()): 
    $es_bajo = ($fila['stock_actual'] <= $fila['stock_minimo']);
?>
<tr <?php if($es_bajo) echo 'class="table-danger"'; ?>>
<td><?php echo $fila['id_inventario']; ?></td>
<td>
    <?php echo $fila['nombre']; ?>
    <?php if($es_bajo): ?>
        <span class="badge bg-danger ms-2">Stock bajo</span>
    <?php endif; ?>
</td>
<td>
<form method="POST" class="d-flex gap-1">
<input type="hidden" name="id_inventario" value="<?php echo $fila['id_inventario']; ?>">
<input type="number" name="stock_actual" value="<?php echo $fila['stock_actual']; ?>" class="form-control form-control-sm" required>
</td>
<td>
<input type="number" name="stock_minimo" value="<?php echo $fila['stock_minimo']; ?>" class="form-control form-control-sm" required>
</td>
<td><?php echo $fila['fecha_actualizacion']; ?></td>
<td>
<button type="submit" name="editar_inventario" class="btn btn-primary btn-sm">Guardar</button>
<a href="panel_admin.php?opcion=inventario&eliminar_inventario=<?php echo $fila['id_inventario']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar inventario?')">Eliminar</a>
</form>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<h6>Agregar Inventario</h6>
<form method="POST" class="row g-2 mb-0">
<select name="id_producto" class="form-control col" required>
<option value="">Seleccionar Producto</option>
<?php
$productos = $conn->query("SELECT * FROM productos");
while($p = $productos->fetch_assoc()){
    echo '<option value="'.$p['id_producto'].'">'.$p['nombre'].'</option>';
}
?>
</select>
<input type="number" name="stock_actual" placeholder="Stock Actual" class="form-control col" required>
<input type="number" name="stock_minimo" placeholder="Stock Mínimo" class="form-control col" required>
<button type="submit" name="agregar_inventario" class="btn btn-success col">Agregar</button>
</form>
</div>

<!-- ================= VENTAS ================= -->
<?php elseif($opcion == 'ventas'):
$ventas = $conn->query("
SELECT v.id_venta, u.nombre, u.apellido_paterno, u.apellido_materno, v.total, v.fecha_venta 
FROM ventas v 
INNER JOIN usuarios u ON v.carnet_usuario = u.carnet
ORDER BY v.fecha_venta DESC
");
?>
<div class="card p-4 mb-4 shadow-sm">
<h5>Ventas</h5>
<table class="table table-striped">
<thead class="table-dark">
<tr>
<th>ID Venta</th><th>Empleado</th><th>Total</th><th>Fecha</th><th>Detalle</th>
</tr>
</thead>
<tbody>
<?php while($fila = $ventas->fetch_assoc()): ?>
<tr>
<td><?php echo $fila['id_venta']; ?></td>
<td><?php echo $fila['nombre'].' '.$fila['apellido_paterno'].' '.$fila['apellido_materno']; ?></td>
<td><?php echo number_format($fila['total'],2); ?></td>
<td><?php echo $fila['fecha_venta']; ?></td>
<td>
<button class="btn btn-info btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#detalle_<?php echo $fila['id_venta']; ?>">Ver Detalle</button>
</td>
</tr>
<tr class="collapse" id="detalle_<?php echo $fila['id_venta']; ?>">
<td colspan="5">
<table class="table table-bordered mb-0">
<thead>
<tr><th>Producto</th><th>Cantidad</th><th>Subtotal</th></tr>
</thead>
<tbody>
<?php
$detalles = $conn->query("
SELECT p.nombre, dv.cantidad, dv.subtotal
FROM detalle_venta dv 
INNER JOIN productos p ON dv.id_producto = p.id_producto
WHERE dv.id_venta = ".$fila['id_venta']
);
while($d = $detalles->fetch_assoc()):
?>
<tr>
<td><?php echo $d['nombre']; ?></td>
<td><?php echo $d['cantidad']; ?></td>
<td><?php echo number_format($d['subtotal'],2); ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php endif; ?>

<a href="../index.php" class="btn btn-secondary mt-3">Cerrar sesión</a>
</div>
</body>
</html>
