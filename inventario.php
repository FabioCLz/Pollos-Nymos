<?php
include 'conexion.php';

// Generar token CSRF
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Manejar agregar producto
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['producto'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensaje = 'Error: Token CSRF inválido.';
    } else {
        $nombre = trim($_POST['nombre']);
        $tipo = trim($_POST['tipo']);
        $precio = trim($_POST['precio']);
        $cantidad_disponible = trim($_POST['cantidad_disponible']);
        $id_inventario = trim($_POST['id_inventario']);

        if (empty($nombre) || empty($precio) || empty($cantidad_disponible) || empty($id_inventario)) {
            $mensaje = 'Todos los campos son obligatorios.';
        } else {
            $stmt = $conexion->prepare("INSERT INTO producto (nombre, tipo, precio, cantidad_disponible, id_inventario) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                error_log("Error en prepare (insert_producto): " . $conexion->error, 3, 'errors.log');
                $mensaje = 'Error en la base de datos.';
            } else {
                $stmt->bind_param("ssdii", $nombre, $tipo, $precio, $cantidad_disponible, $id_inventario);
                if ($stmt->execute()) {
                    $mensaje = 'Producto agregado exitosamente.';
                } else {
                    error_log("Error en execute (insert_producto): " . $stmt->error, 3, 'errors.log');
                    $mensaje = 'Error al agregar producto.';
                }
                $stmt->close();
            }
        }
    }
}

// Manejar editar producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_producto'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensaje = 'Error: Token CSRF inválido.';
    } else {
        $id = $_POST['id'];
        $nombre = trim($_POST['nombre']);
        $tipo = trim($_POST['tipo']);
        $precio = trim($_POST['precio']);
        $cantidad_disponible = trim($_POST['cantidad_disponible']);
        $id_inventario = trim($_POST['id_inventario']);

        if (empty($nombre) || empty($precio) || empty($cantidad_disponible) || empty($id_inventario)) {
            $mensaje = 'Todos los campos son obligatorios.';
        } else {
            $stmt = $conexion->prepare("UPDATE producto SET nombre = ?, tipo = ?, precio = ?, cantidad_disponible = ?, id_inventario = ? WHERE id_producto = ?");
            if (!$stmt) {
                error_log("Error en prepare (editar_producto): " . $conexion->error, 3, 'errors.log');
                $mensaje = 'Error en la base de datos.';
            } else {
                $stmt->bind_param("ssdiii", $nombre, $tipo, $precio, $cantidad_disponible, $id_inventario, $id);
                if ($stmt->execute()) {
                    $mensaje = 'Producto actualizado exitosamente.';
                } else {
                    error_log("Error en execute (editar_producto): " . $stmt->error, 3, 'errors.log');
                    $mensaje = 'Error al actualizar producto.';
                }
                $stmt->close();
            }
        }
    }
}

// Manejar eliminar producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_producto'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensaje = 'Error: Token CSRF inválido.';
    } else {
        $id = $_POST['id'];
        $stmt = $conexion->prepare("DELETE FROM producto WHERE id_producto = ?");
        if (!$stmt) {
            error_log("Error en prepare (eliminar_producto): " . $conexion->error, 3, 'errors.log');
            $mensaje = 'Error en la base de datos.';
        } else {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $mensaje = 'Producto eliminado exitosamente.';
            } else {
                error_log("Error en execute (eliminar_producto): " . $stmt->error, 3, 'errors.log');
                $mensaje = 'Error al eliminar producto.';
            }
            $stmt->close();
        }
    }
}

// Nueva función: Agregar registro de inventario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_inventario'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensaje = 'Error: Token CSRF inválido.';
    } else {
        $fecha_registro = date('Y-m-d');
        $stmt = $conexion->prepare("INSERT INTO inventario (fecha_registro) VALUES (?)");
        if (!$stmt) {
            error_log("Error en prepare (insert_inventario): " . $conexion->error, 3, 'errors.log');
            $mensaje = 'Error en la base de datos.';
        } else {
            $stmt->bind_param("s", $fecha_registro);
            if ($stmt->execute()) {
                $mensaje = 'Inventario agregado exitosamente.';
            } else {
                error_log("Error en execute (insert_inventario): " . $stmt->error, 3, 'errors.log');
                $mensaje = 'Error al agregar inventario.';
            }
            $stmt->close();
        }
    }
}

// Nueva función: Verificar alertas de stock bajo
$productos_bajo_stock = [];
$stmt = $conexion->prepare("SELECT nombre, cantidad_disponible FROM producto WHERE cantidad_disponible < 10");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $productos_bajo_stock = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    error_log("Error en prepare (bajo_stock): " . $conexion->error, 3, 'errors.log');
}

// Obtener inventarios
$inventarios = [];
$stmt = $conexion->prepare("SELECT id_inventario, fecha_registro FROM inventario");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $inventarios = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    error_log("Error en prepare (inventarios): " . $conexion->error, 3, 'errors.log');
}

// Obtener productos
$productos = [];
$stmt = $conexion->prepare("SELECT id_producto, nombre, tipo, precio, cantidad_disponible, id_inventario FROM producto");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $productos = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    error_log("Error en prepare (productos): " . $conexion->error, 3, 'errors.log');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pollos Nymos - Gestión de Inventario</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      display: flex;
      height: 100vh;
      background: #f5f6fa;
    }
    header {
      background: #e74c3c;
      color: white;
      padding: 10px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      position: fixed;
      width: 100%;
      z-index: 1000;
    }
    .logo { font-size: 24px; font-weight: bold; }
    .sidebar {
      width: 180px;
      height: 100vh;
      background: #f4f4f4;
      padding-top: 70px;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 900;
    }
    .sidebar .nav-link {
      color: #2c3e50;
      font-weight: bold;
      padding: 10px 15px;
      border-radius: 4px;
      margin-bottom: 10px;
      transition: background 0.2s;
    }
    .sidebar .nav-link:hover { background: #e0e0e0; }
    .sidebar .nav-link.active { background: #e74c3c; color: white; }
    main {
      margin-left: 180px;
      padding: 80px 20px 20px;
      flex-grow: 1;
    }
    .bienvenida {
      font-size: 18px;
      font-weight: bold;
      margin-bottom: 20px;
      color: #2c3e50;
    }
    .stats-section {
      margin-bottom: 20px;
      text-align: center;
    }
    .stat-card {
      background: white;
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      display: inline-block;
    }
    .stat-card h3 {
      margin: 0 0 10px;
      color: #c0392b;
      font-size: 16px;
    }
    .stat-card p {
      margin: 0;
      font-size: 24px;
      font-weight: bold;
      color: #2c3e50;
    }
    .form-section, .chart-section {
      background: white;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .form-section h2, .chart-section h2 {
      color: #c0392b;
      margin-top: 0;
      border-bottom: 2px solid #e74c3c;
      padding-bottom: 5px;
      font-size: 20px;
    }
    .form-group {
      margin-bottom: 15px;
    }
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
      color: #2c3e50;
    }
    .form-group input, .form-group select {
      width: 100%;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 16px;
    }
    .form-group input:focus, .form-group select:focus {
      border-color: #e74c3c;
      outline: none;
    }
    button {
      padding: 10px 20px;
      border: none;
      border-radius: 5px;
      background: #d35400;
      color: white;
      cursor: pointer;
      font-size: 16px;
      transition: background 0.2s;
    }
    button:hover { background: #e67e22; }
    .btn-agregar { background: #27ae60; }
    .btn-agregar:hover { background: #2ecc71; }
    .btn-editar { background: #3498db; }
    .btn-editar:hover { background: #2980b9; }
    .btn-eliminar { background: #c0392b; }
    .btn-eliminar:hover { background: #e74c3c; }
    .mensaje {
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 4px;
      font-weight: bold;
    }
    .mensaje.exito {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    .mensaje.error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 8px;
      text-align: left;
    }
    th {
      background: #f4f4f4;
      font-weight: bold;
    }
    .chart-section canvas {
      max-width: 100%;
      margin-top: 10px;
    }
    .modal-content {
      background: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .modal-content h2 {
      color: #c0392b;
      margin-top: 0;
    }
    #buscar-producto {
      margin-bottom: 10px;
      padding: 8px;
      width: 100%;
      max-width: 300px;
      border-radius: 4px;
      border: 1px solid #ccc;
    }
    .alert-section {
      background: #fff3cd;
      color: #856404;
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 15px;
      border: 1px solid #ffeeba;
    }
  </style>
</head>
<body>
  <header>
    <div class="logo">🐔 POLLOS NYMOS</div>
  </header>

  <nav class="sidebar bg-light">
    <ul class="nav flex-column">
      <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Inicio</a></li>
      <li class="nav-item"><a class="nav-link active" href="inventario.php">Inventario</a></li>
      <li class="nav-item"><a class="nav-link" href="reportes.php">Reportes</a></li>
      <li class="nav-item"><a class="nav-link" href="admin_users.php">Cajeros</a></li>
    </ul>
  </nav>

  <main>
    <div class="bienvenida">Panel de Inventario 👋</div>

    <?php if ($mensaje): ?>
      <div class="mensaje <?= strpos($mensaje, 'exitosamente') !== false ? 'exito' : 'error' ?>">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <!-- Nueva sección para agregar inventario -->
    <section class="form-section">
      <h2>Agregar Nuevo Inventario</h2>
      <form method="POST" id="form-agregar-inventario">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <button type="submit" name="agregar_inventario" class="btn-agregar">Agregar Inventario (Fecha Actual)</button>
      </form>
    </section>

    <!-- Alerta de stock bajo -->
    <?php if (!empty($productos_bajo_stock)): ?>
      <div class="alert-section">
        <strong>¡Atención!</strong> Productos con stock bajo:
        <ul>
          <?php foreach ($productos_bajo_stock as $producto): ?>
            <li><?= htmlspecialchars($producto['nombre']) ?> (Disponible: <?= htmlspecialchars($producto['cantidad_disponible']) ?>)</li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <section class="form-section">
      <h2>Agregar Producto</h2>
      <form method="POST" id="form-agregar">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <div class="form-group">
          <label for="nombre">Nombre:</label>
          <input type="text" id="nombre" name="nombre" required>
        </div>
        <div class="form-group">
          <label for="tipo">Tipo:</label>
          <select id="tipo" name="tipo" required>
            <option value="Entero">Entero</option>
            <option value="Broaster">Broaster</option>
            <option value="Al Horno">Al Horno</option>
            <option value="Porción">Porción</option>
            <option value="Combo">Combo</option>
          </select>
        </div>
        <div class="form-group">
          <label for="precio">Precio:</label>
          <input type="number" id="precio" name="precio" step="0.01" required>
        </div>
        <div class="form-group">
          <label for="cantidad_disponible">Cantidad Disponible:</label>
          <input type="number" id="cantidad_disponible" name="cantidad_disponible" required>
        </div>
        <div class="form-group">
          <label for="id_inventario">Inventario:</label>
          <select id="id_inventario" name="id_inventario" required>
            <?php foreach ($inventarios as $inventario): ?>
              <option value="<?= htmlspecialchars($inventario['id_inventario']) ?>">
                <?= htmlspecialchars($inventario['fecha_registro']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" name="producto" class="btn-agregar">Agregar</button>
      </form>
    </section>

    <section class="form-section">
      <h2>Lista de Productos</h2>
      <input type="text" id="buscar-producto" placeholder="Buscar por nombre">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Precio</th>
            <th>Cantidad Disponible</th>
            <th>Inventario</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($productos as $producto): ?>
            <tr data-id="<?= $producto['id_producto'] ?>">
              <td><?= htmlspecialchars($producto['id_producto']) ?></td>
              <td><?= htmlspecialchars($producto['nombre']) ?></td>
              <td><?= htmlspecialchars($producto['tipo']) ?></td>
              <td><?= htmlspecialchars(number_format($producto['precio'], 2)) ?></td>
              <td><?= htmlspecialchars($producto['cantidad_disponible']) ?></td>
              <td><?= htmlspecialchars($producto['id_inventario']) ?></td>
              <td>
                <button class="btn-editar" onclick="abrirModalEditar(<?= $producto['id_producto'] ?>, '<?= $producto['nombre'] ?>', '<?= $producto['tipo'] ?>', <?= $producto['precio'] ?>, <?= $producto['cantidad_disponible'] ?>, <?= $producto['id_inventario'] ?>)">Editar</button>
                <button class="btn-eliminar" onclick="eliminarProducto(<?= $producto['id_producto'] ?>)">Eliminar</button>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($productos)): ?>
            <tr><td colspan="7" style="text-align: center;">No hay productos.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>

    <div class="modal fade" id="modal-editar" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <h2>Editar Producto</h2>
          <form method="POST" id="form-editar">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" id="editar-id" name="id">
            <div class="form-group">
              <label for="editar-nombre">Nombre:</label>
              <input type="text" id="editar-nombre" name="nombre" required>
            </div>
            <div class="form-group">
              <label for="editar-tipo">Tipo:</label>
              <select id="editar-tipo" name="tipo" required>
                <option value="Entero">Entero</option>
                <option value="Broaster">Broaster</option>
                <option value="Al Horno">Al Horno</option>
                <option value="Porción">Porción</option>
                <option value="Combo">Combo</option>
              </select>
            </div>
            <div class="form-group">
              <label for="editar-precio">Precio:</label>
              <input type="number" id="editar-precio" name="precio" step="0.01" required>
            </div>
            <div class="form-group">
              <label for="editar-cantidad_disponible">Cantidad Disponible:</label>
              <input type="number" id="editar-cantidad_disponible" name="cantidad_disponible" required>
            </div>
            <div class="form-group">
              <label for="editar-id_inventario">Inventario:</label>
              <select id="editar-id_inventario" name="id_inventario" required>
                <?php foreach ($inventarios as $inventario): ?>
                  <option value="<?= htmlspecialchars($inventario['id_inventario']) ?>">
                    <?= htmlspecialchars($inventario['fecha_registro']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="submit" name="editar_producto" class="btn-agregar">Guardar</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </form>
        </div>
      </div>
    </div>
  </main>

  <script>
    // Filtrar productos según búsqueda
    function filtrarProductos(busqueda) {
      const tabla = document.getElementById('tabla-productos');
      const filas = tabla.getElementsByTagName('tr');
      for (let i = 0; i < filas.length; i++) {
        const nombre = filas[i].getElementsByTagName('td')[1].textContent.toLowerCase();
        if (nombre.includes(busqueda.toLowerCase())) {
          filas[i].style.display = '';
        } else {
          filas[i].style.display = 'none';
        }
      }
    }

    // Evento de búsqueda
    document.getElementById('buscar-producto').addEventListener('input', function() {
      filtrarProductos(this.value);
    });

    // Eliminar producto
    function eliminarProducto(id) {
      if (confirm('¿Seguro que quieres eliminar este producto?')) {
        const data = new FormData();
        data.append('eliminar_producto', true);
        data.append('id', id);
        data.append('csrf_token', '<?= htmlspecialchars($_SESSION['csrf_token']) ?>');

        fetch('', {
          method: 'POST',
          body: data
        })
        .then(response => response.json())
        .then(data => {
          alert(data.message);
          if (data.success) location.reload();
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error al procesar la solicitud.');
        });
      }
    }

    // Abrir modal de edición
    function abrirModalEditar(id, nombre, tipo, precio, cantidad_disponible, id_inventario) {
      document.getElementById('editar-id').value = id;
      document.getElementById('editar-nombre').value = nombre;
      document.getElementById('editar-tipo').value = tipo;
      document.getElementById('editar-precio').value = precio;
      document.getElementById('editar-cantidad_disponible').value = cantidad_disponible;
      document.getElementById('editar-id_inventario').value = id_inventario;
      new bootstrap.Modal(document.getElementById('modal-editar')).show();
    }
  </script>
</body>
</html>