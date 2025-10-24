<?php
include 'conexion.php';

// Generar token CSRF
session_start();
$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));

// Manejar agregar cajero
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_cajero'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensaje = 'Error de seguridad: Token CSRF inválido.';
    } else {
        $nombre = trim($_POST['nombre']);
        $usuario = trim($_POST['usuario']);
        $contrasena = $_POST['contrasena'];
        $rol = 'Empleado';

        if (empty($nombre) || empty($usuario) || empty($contrasena)) {
            $mensaje = 'Todos los campos son obligatorios.';
        } else {
            $stmt = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ?");
            if ($stmt && $stmt->bind_param("s", $usuario) && $stmt->execute() && $stmt->store_result()) {
                if ($stmt->num_rows > 0) {
                    $mensaje = 'El cajero ya está registrado.';
                } else {
                    $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
                    $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, usuario, contrasena, rol) VALUES (?, ?, ?, ?)");
                    if ($stmt && $stmt->bind_param("ssss", $nombre, $usuario, $contrasena_hash, $rol) && $stmt->execute()) {
                        $mensaje = 'Cajero agregado exitosamente.';
                    } else {
                        error_log("Error (insert_cajero): " . ($stmt ? $stmt->error : $conexion->error), 3, 'errors.log');
                        $mensaje = 'Error al agregar cajero.';
                    }
                }
            } else {
                error_log("Error (check_cajero): " . $conexion->error, 3, 'errors.log');
                $mensaje = 'Error en la base de datos.';
            }
            $stmt?->close();
        }
    }
}

// Obtener estadísticas y cajeros
$total_cajeros = $conexion->query("SELECT COUNT(*) as total FROM usuarios") ? $conexion->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total'] : 0;
$total_productos = $conexion->query("SELECT SUM(total_productos) as total FROM inventario") ? $conexion->query("SELECT SUM(total_productos) as total FROM inventario")->fetch_assoc()['total'] : 0;
$total_pedidos = $conexion->query("SELECT COUNT(*) as total FROM pedido") ? $conexion->query("SELECT COUNT(*) as total FROM pedido")->fetch_assoc()['total'] : 0;
$cajeros = $conexion->query("SELECT id_usuario, nombre, usuario, rol FROM usuarios") ? $conexion->query("SELECT id_usuario, nombre, usuario, rol FROM usuarios")->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pollos Nymos - Gestión de Cajeros</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; font-family: 'Poppins', sans-serif; display: flex; height: 100vh; background: #f5f6fa; }
    header { background: #e74c3c; color: white; padding: 10px; display: flex; justify-content: space-between; position: fixed; width: 100%; z-index: 1000; }
    .logo { font-size: 24px; font-weight: bold; }
    .sidebar { width: 180px; height: 100vh; background: #f4f4f4; padding-top: 70px; position: fixed; top: 0; left: 0; }
    .sidebar .nav-link { color: #2c3e50; font-weight: bold; padding: 10px; border-radius: 4px; margin-bottom: 10px; transition: background 0.2s; }
    .sidebar .nav-link:hover { background: #e0e0e0; }
    .sidebar .nav-link.active { background: #e74c3c; color: white; }
    main { margin-left: 180px; padding: 80px 20px; flex-grow: 1; }
    .bienvenida { font-size: 18px; font-weight: bold; margin-bottom: 20px; color: #2c3e50; }
    .stats-section { display: flex; gap: 20px; margin-bottom: 20px; }
    .stat-card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); flex: 1; text-align: center; }
    .stat-card h3 { margin: 0 0 10px; color: #c0392b; font-size: 16px; }
    .stat-card p { margin: 0; font-size: 24px; font-weight: bold; color: #2c3e50; }
    .form-section { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .form-section h2 { color: #c0392b; margin-top: 0; border-bottom: 2px solid #e74c3c; padding-bottom: 5px; font-size: 20px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #2c3e50; }
    .form-group input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px; }
    .form-group input:focus { border-color: #e74c3c; outline: none; }
    button { padding: 10px 20px; border: none; border-radius: 5px; background: #d35400; color: white; cursor: pointer; font-size: 16px; transition: background 0.2s; margin-right: 10px; }
    button:hover { background: #e67e22; }
    .btn-agregar { background: #27ae60; }
    .btn-agregar:hover { background: #2ecc71; }
    .mensaje { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-weight: bold; }
    .mensaje.exito { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .mensaje.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    th { background: #f4f4f4; font-weight: bold; }
    .modal-content { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
    .clock-section { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
    #clock { font-size: 24px; font-weight: bold; color: #2c3e50; }
  </style>
</head>
<body>
  <header><div class="logo">🐔 POLLOS NYMOS</div></header>
  <nav class="sidebar bg-light">
    <ul class="nav flex-column">
      <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Inicio</a></li>
      <li class="nav-item"><a class="nav-link" href="#">Inventario</a></li>
      <li class="nav-item"><a class="nav-link" href="reportes.php">Reportes</a></li>
      <li class="nav-item"><a class="nav-link active" href="admin_users.php">Cajeros</a></li>
    </ul>
  </nav>
  <main>
    <div class="bienvenida">Panel de Cajeros 👋</div>
    <section class="stats-section">
      <div class="stat-card"><h3>Total Cajeros</h3><p><?= htmlspecialchars($total_cajeros) ?></p></div>
      <div class="stat-card"><h3>Total Productos</h3><p><?= htmlspecialchars($total_productos) ?></p></div>
      <div class="stat-card"><h3>Total Pedidos</h3><p><?= htmlspecialchars($total_pedidos) ?></p></div>
    </section>
    <?php if ($mensaje): ?><div class="mensaje <?= strpos($mensaje, 'exitosamente') !== false ? 'exito' : 'error' ?>"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
    <section class="form-section">
      <h2>Agregar Cajero</h2>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="form-group"><label for="nombre">Nombre:</label><input type="text" id="nombre" name="nombre" required></div>
        <div class="form-group"><label for="usuario">Usuario:</label><input type="text" id="usuario" name="usuario" required></div>
        <div class="form-group"><label for="contrasena">Contraseña:</label><input type="password" id="contrasena" name="contrasena" required minlength="6"></div>
        <button type="submit" name="agregar_cajero" class="btn-agregar">Agregar</button>
      </form>
    </section>
    <section class="form-section">
      <h2>Lista de Cajeros</h2>
      <table>
        <thead><tr><th>ID</th><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($cajeros as $c): ?>
            <tr data-id="<?= $c['id_usuario'] ?>">
              <td><?= $c['id_usuario'] ?></td>
              <td><?= $c['nombre'] ?></td>
              <td><?= $c['usuario'] ?></td>
              <td><?= $c['rol'] ?></td>
              <td>
                <button class="btn-editar" onclick="abrirModalEditar(<?= $c['id_usuario'] ?>, '<?= $c['nombre'] ?>', '<?= $c['usuario'] ?>', '<?= $c['rol'] ?>')" style="margin-right: 10px;">Editar</button>
                <button class="btn-eliminar" onclick="eliminarCajero(<?= $c['id_usuario'] ?>)">Eliminar</button>
              </td>
            </tr>
          <?php endforeach; if (empty($cajeros)) echo '<tr><td colspan="5" style="text-align:center;">No hay cajeros.</td></tr>'; ?>
        </tbody>
      </table>
    </section>
    <section class="chart-section"><h2>Inventario por Fecha</h2><canvas id="inventarioChart"></canvas></section>
    <section class="chart-section"><h2>Ventas por Fecha</h2><canvas id="ventasChart"></canvas></section>
    <section class="clock-section">
      <h2>Reloj Actual</h2>
      <div id="clock"></div>
    </section>
    <div class="modal fade" id="modal-editar">
      <div class="modal-dialog">
        <div class="modal-content">
          <h2>Editar Cajero</h2>
          <form id="form-editar">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" id="editar-id" name="id">
            <div class="form-group"><label for="editar-nombre">Nombre:</label><input type="text" id="editar-nombre" name="nombre" required></div>
            <div class="form-group"><label for="editar-usuario">Usuario:</label><input type="text" id="editar-usuario" name="usuario" required></div>
            <div class="form-group"><label for="editar-contrasena">Contraseña:</label><input type="password" id="editar-contrasena" name="contrasena" placeholder="Dejar en blanco para no cambiar"></div>
            <div class="form-group"><label for="editar-rol">Rol:</label><select id="editar-rol" name="rol"><option value="Administrador">Administrador</option><option value="Empleado">Empleado</option></select></div>
            <button type="submit" class="btn-agregar" style="margin-right: 10px;">Guardar</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </form>
        </div>
      </div>
    </div>
  </main>
  <script>
    function eliminarCajero(id) {
      if (confirm('¿Eliminar cajero?')) {
        fetch('', { method: 'POST', body: new FormData().appendAll({ eliminar_cajero: true, id, csrf_token: '<?= $_SESSION['csrf_token'] ?>' }) })
          .then(r => r.json()).then(d => { alert(d.message); if (d.success) location.reload(); })
          .catch(e => { console.error(e); alert('Error.'); });
      }
    }
    function abrirModalEditar(id, nombre, usuario, rol) {
      document.getElementById('editar-id').value = id;
      document.getElementById('editar-nombre').value = nombre;
      document.getElementById('editar-usuario').value = usuario;
      document.getElementById('editar-rol').value = rol;
      document.getElementById('editar-contrasena').value = '';
      new bootstrap.Modal(document.getElementById('modal-editar')).show();
    }
    document.getElementById('form-editar').addEventListener('submit', e => {
      e.preventDefault();
      fetch('editar_usuario.php', { method: 'POST', body: new FormData(e.target).append('editar_cajero', true) })
        .then(r => r.json()).then(d => { alert(d.message); if (d.success) { location.reload(); bootstrap.Modal.getInstance(document.getElementById('modal-editar')).hide(); } })
        .catch(e => { console.error(e); alert('Error.'); });
    });
    new Chart(document.getElementById('inventarioChart'), {"type":"bar","data":{"labels":["<?php echo implode('","', array_column([], 'fecha_resgistro')); ?>"],"datasets":[{"label":"Total Productos","data":[<?php echo implode(',', array_column([], 'total')); ?>],"backgroundColor":"#e74c3c","borderColor":"#c0392b","borderWidth":1}]},"options":{"scales":{"y":{"beginAtZero":true},"x":{}},"plugins":{"legend":{"display":true}}}});
    new Chart(document.getElementById('ventasChart'), {"type":"line","data":{"labels":["<?php echo implode('","', array_column([], 'fecha')); ?>"],"datasets":[{"label":"Total Ventas","data":[<?php echo implode(',', array_column([], 'total_ventas')); ?>],"borderColor":"#d35400","backgroundColor":"rgba(211,84,0,0.2)","fill":true,"tension":0.4}]},"options":{"scales":{"y":{"beginAtZero":true},"x":{}},"plugins":{"legend":{"display":true}}}});
    function updateClock() {
      const now = new Date();
      const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
      const months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
      const day = days[now.getDay()];
      const date = now.getDate();
      const month = months[now.getMonth()];
      const year = now.getFullYear();
      const time = now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false, timeZone: 'America/Caracas' });
      document.getElementById('clock').textContent = `${day}, ${date} de ${month} de ${year} - ${time} -04`;
    }
    updateClock();
    setInterval(updateClock, 1000);
  </script>
</body>
</html>