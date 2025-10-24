<?php
include 'conexion.php';

// Generar token CSRF (por consistencia, aunque no se use en este archivo)
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Obtener estadísticas iniciales
$total_cajeros = 0;
$total_productos = 0;
$total_pedidos = 0;
$total_ventas = 0;

$stmt = $conexion->prepare("SELECT COUNT(*) as total_cajeros FROM cajero");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $total_cajeros = $result->fetch_assoc()['total_cajeros'];
    $stmt->close();
} else {
    error_log("Error en prepare (total_cajeros): " . $conexion->error, 3, 'errors.log');
}

$stmt = $conexion->prepare("SELECT SUM(total_productos) as total_productos FROM inventario");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $total_productos = $result->fetch_assoc()['total_productos'] ?? 0;
    $stmt->close();
} else {
    error_log("Error en prepare (total_productos): " . $conexion->error, 3, 'errors.log');
}

$stmt = $conexion->prepare("SELECT COUNT(*) as total_pedidos FROM pedido");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $total_pedidos = $result->fetch_assoc()['total_pedidos'];
    $stmt->close();
} else {
    error_log("Error en prepare (total_pedidos): " . $conexion->error, 3, 'errors.log');
}

$stmt = $conexion->prepare("SELECT SUM(total) as total_ventas FROM venta");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $total_ventas = $result->fetch_assoc()['total_ventas'] ?? 0;
    $stmt->close();
} else {
    error_log("Error en prepare (total_ventas): " . $conexion->error, 3, 'errors.log');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pollos Nymos - Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
      display: flex;
      gap: 20px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .stat-card {
      background: white;
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      flex: 1;
      min-width: 200px;
      text-align: center;
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
  </style>
</head>
<body>
  <header>
    <div class="logo">🐔 POLLOS NYMOS</div>
  </header>

  <nav class="sidebar bg-light">
    <ul class="nav flex-column">
      <li class="nav-item"><a class="nav-link active" href="admin_dashboard.php">Inicio</a></li>
      <li class="nav-item"><a class="nav-link" href="#">Inventario</a></li>
      <li class="nav-item"><a class="nav-link" href="reportes.php">Reportes</a></li>
      <li class="nav-item"><a class="nav-link" href="admin_users.php">Cajeros</a></li>
    </ul>
  </nav>

  <main>
    <div class="bienvenida">Bienvenido al Dashboard 👋</div>

    <section class="stats-section">
      <div class="stat-card">
        <h3>Total Cajeros</h3>
        <p id="total-cajeros"><?= htmlspecialchars($total_cajeros) ?></p>
      </div>
      <div class="stat-card">
        <h3>Total Productos</h3>
        <p id="total-productos"><?= htmlspecialchars($total_productos) ?></p>
      </div>
      <div class="stat-card">
        <h3>Total Pedidos</h3>
        <p id="total-pedidos"><?= htmlspecialchars($total_pedidos) ?></p>
      </div>
      <div class="stat-card">
        <h3>Total Ventas (Bs.)</h3>
        <p id="total-ventas"><?= number_format((float)$total_ventas, 2, '.', ',') ?></p>
      </div>
    </section>
  </main>

  <script>
    // Actualizar estadísticas en tiempo real
    function actualizarDatos() {
      fetch('fetch_stats.php')
        .then(response => response.json())
        .then(data => {
          if (data.error) {
            console.error('Error:', data.error);
            return;
          }
          document.getElementById('total-cajeros').textContent = data.total_cajeros || 0;
          document.getElementById('total-productos').textContent = data.total_productos || 0;
          document.getElementById('total-pedidos').textContent = data.total_pedidos || 0;
          document.getElementById('total-ventas').textContent = data.total_ventas ? parseFloat(data.total_ventas).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '0.00';
        })
        .catch(error => console.error('Error al actualizar datos:', error));
    }

    // Inicializar y actualizar cada 10 segundos
    actualizarDatos();
    setInterval(actualizarDatos, 10000);
  </script>
</body>
</html>