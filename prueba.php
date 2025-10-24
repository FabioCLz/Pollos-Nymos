<?php
include 'conexion.php';

// Generar token CSRF
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Manejar agregar cliente
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cliente'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensaje = 'Error: Token CSRF inválido.';
    } else {
        $nombre = trim($_POST['nombre']);
        $telefono = trim($_POST['telefono']);
        
        if (empty($nombre) || empty($telefono)) {
            $mensaje = 'Los campos Nombre y Teléfono son obligatorios.';
        } else {
            $stmt = $conexion->prepare("SELECT id_cliente FROM cliente WHERE nombre = ? AND telefono = ?");
            if (!$stmt) {
                error_log("Error en prepare (agregar_cliente): " . $conexion->error, 3, 'errors.log');
                $mensaje = 'Error en la base de datos.';
            } else {
                $stmt->bind_param("ss", $nombre, $telefono);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $mensaje = 'El cliente con ese nombre y teléfono ya está registrado.';
                } else {
                    $stmt = $conexion->prepare("INSERT INTO cliente (nombre, telefono) VALUES (?, ?)");
                    if (!$stmt) {
                        error_log("Error en prepare (insert_cliente): " . $conexion->error, 3, 'errors.log');
                        $mensaje = 'Error en la base de datos.';
                    } else {
                        $stmt->bind_param("ss", $nombre, $telefono);
                        if ($stmt->execute()) {
                            $mensaje = 'Cliente agregado exitosamente.';
                        } else {
                            error_log("Error en execute (insert_cliente): " . $stmt->error, 3, 'errors.log');
                            $mensaje = 'Error al agregar cliente.';
                        }
                    }
                }
                $stmt->close();
            }
        }
    }
}

// Obtener estadística de clientes
$total_clientes = 0;
$stmt = $conexion->prepare("SELECT COUNT(*) as total_clientes FROM cliente");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $total_clientes = $result->fetch_assoc()['total_clientes'];
    $stmt->close();
} else {
    error_log("Error en prepare (total_clientes): " . $conexion->error, 3, 'errors.log');
}

// Obtener todos los clientes directamente desde la base de datos
$clientes = [];
$stmt = $conexion->prepare("SELECT id_cliente, nombre, telefono FROM cliente");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $clientes = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    error_log("Error en prepare (clientes): " . $conexion->error, 3, 'errors.log');
}

// Datos para el reporte (opcional, ajustado a cliente si aplica)
$ventas_por_cliente = [];
$stmt = $conexion->prepare("
    SELECT c.nombre, COUNT(v.id_venta) as num_ventas
    FROM cliente c
    LEFT JOIN venta v ON v.id_cliente = c.id_cliente
    GROUP BY c.id_cliente, c.nombre
    ORDER BY num_ventas DESC
");
if ($stmt) {
    $stmt->execute();
    $resultado = $stmt->get_result();
    $ventas_por_cliente = $resultado->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    error_log("Error en prepare (ventas_por_cliente): " . $conexion->error, 3, 'errors.log');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pollos Nymos - Gestión de Clientes</title>
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
    .form-group input {
      width: 100%;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 16px;
    }
    .form-group input:focus {
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
    #buscar-cliente {
      margin-bottom: 10px;
      padding: 8px;
      width: 100%;
      max-width: 300px;
      border-radius: 4px;
      border: 1px solid #ccc;
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
      <li class="nav-item"><a class="nav-link" href="#">Inventario</a></li>
      <li class="nav-item"><a class="nav-link" href="reportes.php">Reportes</a></li>
      <li class="nav-item"><a class="nav-link active" href="admin_users.php">Clientes</a></li>
    </ul>
  </nav>

  <main>
    <div class="bienvenida">Panel de Cajeros 👋</div>

    <section class="stats-section">
      <div class="stat-card">
        <h3>Total Cajeros</h3>
        <p id="total-clientes"><?= htmlspecialchars($total_clientes) ?></p>
      </div>
    </section>

    <?php if ($mensaje): ?>
      <div class="mensaje <?= strpos($mensaje, 'exitosamente') !== false ? 'exito' : 'error' ?>">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <section class="form-section">
      <h2>Agregar Cajero</h2>
      <form method="POST" id="form-agregar">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <div class="form-group">
          <label for="nombre">Nombre:</label>
          <input type="text" id="nombre" name="nombre" required>
        </div>
        <div class="form-group">
          <label for="telefono">Teléfono:</label>
          <input type="text" id="telefono" name="telefono" required minlength="6">
        </div>
        <button type="submit" name="cliente" class="btn-agregar">Agregar</button>
      </form>
    </section>

    <section class="form-section">
      <h2>Lista de Cajeros</h2>
      <input type="text" id="buscar-cliente" placeholder="Buscar por nombre o teléfono">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Teléfono</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($clientes as $cliente): ?>
            <tr data-id="<?= $cliente['id_cliente'] ?>">
              <td><?= htmlspecialchars($cliente['id_cliente']) ?></td>
              <td><?= htmlspecialchars($cliente['nombre']) ?></td>
              <td><?= htmlspecialchars($cliente['telefono']) ?></td>
              <td>
                <button class="btn-editar" onclick="abrirModalEditar(<?= $cliente['id_cliente'] ?>, '<?= $cliente['nombre'] ?>', '<?= $cliente['telefono'] ?>')">Editar</button>
                <button class="btn-eliminar" onclick="eliminarCliente(<?= $cliente['id_cliente'] ?>)">Eliminar</button>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($clientes)): ?>
            <tr><td colspan="4" style="text-align: center;">No hay clientes.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>

    <section class="form-section">
      <h2>Ventas por Cajero</h2>
      <table>
        <thead>
          <tr>
            <th>Cliente</th>
            <th>Nº Ventas</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($ventas_por_cliente)): ?>
            <tr><td colspan="2" style="text-align: center;">No hay datos.</td></tr>
          <?php else: ?>
            <?php foreach ($ventas_por_cliente as $venta): ?>
              <tr>
                <td><?= htmlspecialchars($venta['nombre']) ?></td>
                <td><?= htmlspecialchars($venta['num_ventas']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
      <canvas id="ventasPorClienteChart" style="margin-top: 20px; max-height: 300px;"></canvas>
    </section>

    <div class="modal fade" id="modal-editar" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <h2>Editar Cliente</h2>
          <form id="form-editar">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" id="editar-id" name="id">
            <div class="form-group">
              <label for="editar-nombre">Nombre:</label>
              <input type="text" id="editar-nombre" name="nombre" required>
            </div>
            <div class="form-group">
              <label for="editar-telefono">Teléfono:</label>
              <input type="text" id="editar-telefono" name="telefono" required minlength="6">
            </div>
            <button type="submit" class="btn-agregar">Guardar</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </form>
        </div>
      </div>
    </div>
  </main>

  <script>
    // Filtrar clientes según búsqueda
    function filtrarClientes(busqueda) {
      const tabla = document.getElementById('tabla-clientes');
      const filas = tabla.getElementsByTagName('tr');
      for (let i = 0; i < filas.length; i++) {
        const nombre = filas[i].getElementsByTagName('td')[1].textContent.toLowerCase();
        const telefono = filas[i].getElementsByTagName('td')[2].textContent.toLowerCase();
        if (nombre.includes(busqueda.toLowerCase()) || telefono.includes(busqueda.toLowerCase())) {
          filas[i].style.display = '';
        } else {
          filas[i].style.display = 'none';
        }
      }
    }

    // Evento de búsqueda
    document.getElementById('buscar-cliente').addEventListener('input', function() {
      filtrarClientes(this.value);
    });

    // Eliminar cliente
    function eliminarCliente(id) {
      if (confirm('¿Seguro que quieres eliminar este cliente?')) {
        const data = new FormData();
        data.append('eliminar_cliente', true);
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
    function abrirModalEditar(id, nombre, telefono) {
      document.getElementById('editar-id').value = id;
      document.getElementById('editar-nombre').value = nombre;
      document.getElementById('editar-telefono').value = telefono;
      new bootstrap.Modal(document.getElementById('modal-editar')).show();
    }

    // Manejar edición
    document.getElementById('form-editar').addEventListener('submit', function(e) {
      e.preventDefault();
      const data = new FormData(this);
      data.append('editar_cliente', true);

      fetch('editar_cliente.php', {
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
    });

    // Gráfico de ventas por cliente
    new Chart(document.getElementById('ventasPorClienteChart'), {
      type: 'bar',
      data: {
        labels: [<?php echo '"' . implode('","', array_column($ventas_por_cliente, 'nombre')) . '"'; ?>],
        datasets: [{
          label: 'Nº Ventas',
          data: [<?php echo implode(',', array_column($ventas_por_cliente, 'num_ventas')); ?>],
          backgroundColor: '#27ae60',
          borderColor: '#219653',
          borderWidth: 1
        }]
      },
      options: {
        scales: {
          y: { beginAtZero: true, title: { display: true, text: 'Nº Ventas' } },
          x: { title: { display: true, text: 'Cliente' } }
        },
        plugins: { legend: { display: true } },
        responsive: true,
        maintainAspectRatio: false
      }
    });
  </script>
</body>
</html>