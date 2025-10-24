<?php
include 'conexion.php';

// Generar token CSRF
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Obtener datos iniciales para el reporte de ventas por cajero
$ventas_por_cajero = [];
$total_ventas = 0;
$total_transacciones = 0;

$stmt = $conexion->prepare("
    SELECT c.nombre, SUM(v.total) as total_ventas, COUNT(v.id_venta) as num_ventas
    FROM venta v
    JOIN cajero c ON v.id_cajero = c.id_cajero
    GROUP BY c.id_cajero, c.nombre
    ORDER BY total_ventas DESC
");
if ($stmt) {
    $stmt->execute();
    $resultado = $stmt->get_result();
    $ventas_por_cajero = $resultado->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    // Calcular totales
    foreach ($ventas_por_cajero as $venta) {
        $total_ventas += (float)$venta['total_ventas'];
        $total_transacciones += (int)$venta['num_ventas'];
    }
} else {
    error_log("Error en prepare (ventas_por_cajero): " . $conexion->error, 3, 'errors.log');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pollos Nymos - Reportes</title>
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
    .report-section {
      background: white;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .report-section h2 {
      color: #c0392b;
      margin-top: 0;
      border-bottom: 2px solid #e74c3c;
      padding-bottom: 5px;
      font-size: 20px;
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
    .table-responsive {
      overflow-x: auto;
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
    canvas {
      max-width: 100%;
      margin-top: 20px;
      max-height: 300px;
    }
    .filter-group {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 15px;
    }
    .filter-group label {
      font-weight: bold;
      color: #2c3e50;
    }
    .filter-group input {
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .filter-group button, .export-btn {
      padding: 8px 15px;
      border: none;
      border-radius: 5px;
      background: #27ae60;
      color: white;
      cursor: pointer;
      transition: background 0.2s;
    }
    .filter-group button:hover, .export-btn:hover {
      background: #2ecc71;
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
      <li class="nav-item"><a class="nav-link active" href="reportes.php">Reportes</a></li>
      <li class="nav-item"><a class="nav-link" href="admin_users.php">Cajeros</a></li>
    </ul>
  </nav>

  <main>
    <div class="bienvenida">Reportes 📊</div>

    <section class="report-section">
      <h2>Ventas por Cajero</h2>
      <div class="stats-section">
        <div class="stat-card">
          <h3>Total Ventas (Bs.)</h3>
          <p id="total-ventas"><?= number_format($total_ventas, 2, '.', ',') ?></p>
        </div>
        <div class="stat-card">
          <h3>Total Transacciones</h3>
          <p id="total-transacciones"><?= htmlspecialchars($total_transacciones) ?></p>
        </div>
      </div>
      <div class="filter-group">
        <label for="fecha-inicio">Fecha Inicio:</label>
        <input type="date" id="fecha-inicio" name="fecha_inicio">
        <label for="fecha-fin">Fecha Fin:</label>
        <input type="date" id="fecha-fin" name="fecha_fin">
        <button onclick="filtrarVentas()">Filtrar</button>
        <button class="export-btn" onclick="exportarCSV()">Exportar a CSV</button>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Cajero</th>
              <th>Total Ventas (Bs.)</th>
              <th>Nº Ventas</th>
            </tr>
          </thead>
          <tbody id="tabla-ventas">
            <?php if (empty($ventas_por_cajero)): ?>
              <tr><td colspan="3" style="text-align: center;">No hay datos.</td></tr>
            <?php else: ?>
              <?php foreach ($ventas_por_cajero as $venta): ?>
                <tr>
                  <td><?= htmlspecialchars($venta['nombre']) ?></td>
                  <td><?= number_format((float)$venta['total_ventas'], 2, '.', ',') ?></td>
                  <td><?= htmlspecialchars($venta['num_ventas']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <canvas id="ventasPorCajeroChart"></canvas>
    </section>
  </main>

  <script>
    let chartInstance = null; // Almacenar instancia del gráfico para actualizarlo

    function actualizarVentas(fechaInicio = '', fechaFin = '') {
      const url = new URL('fetch_ventas_cajero.php');
      if (fechaInicio) url.searchParams.append('fecha_inicio', fechaInicio);
      if (fechaFin) url.searchParams.append('fecha_fin', fechaFin);

      fetch(url)
        .then(response => response.json())
        .then(data => {
          if (data.error) {
            console.error('Error:', data.error);
            return;
          }
          // Actualizar estadísticas
          document.getElementById('total-ventas').textContent = data.total_ventas ? 
            parseFloat(data.total_ventas).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '0.00';
          document.getElementById('total-transacciones').textContent = data.total_transacciones || 0;
          // Actualizar tabla
          const tabla = document.getElementById('tabla-ventas');
          tabla.innerHTML = '';
          if (!data.ventas || data.ventas.length === 0) {
            tabla.innerHTML = '<tr><td colspan="3" style="text-align: center;">No hay datos.</td></tr>';
          } else {
            data.ventas.forEach(venta => {
              const tr = document.createElement('tr');
              tr.innerHTML = `
                <td>${venta.nombre}</td>
                <td>${parseFloat(venta.total_ventas).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}</td>
                <td>${venta.num_ventas}</td>
              `;
              tabla.appendChild(tr);
            });
          }
          // Actualizar gráfico
          if (chartInstance) chartInstance.destroy();
          chartInstance = new Chart(document.getElementById('ventasPorCajeroChart'), {
            type: 'bar',
            data: {
              labels: data.ventas.map(v => v.nombre),
              datasets: [{
                label: 'Total Ventas (Bs.)',
                data: data.ventas.map(v => v.total_ventas),
                backgroundColor: '#27ae60',
                borderColor: '#219653',
                borderWidth: 1
              }]
            },
            options: {
              scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Total Ventas (Bs.)' } },
                x: { title: { display: true, text: 'Cajero' } }
              },
              plugins: { legend: { display: true } },
              responsive: true,
              maintainAspectRatio: false
            }
          });
        })
        .catch(error => console.error('Error al actualizar datos:', error));
    }

    function filtrarVentas() {
      const fechaInicio = document.getElementById('fecha-inicio').value;
      const fechaFin = document.getElementById('fecha-fin').value;
      actualizarVentas(fechaInicio, fechaFin);
    }

    function exportarCSV() {
      const fechaInicio = document.getElementById('fecha-inicio').value;
      const fechaFin = document.getElementById('fecha-fin').value;
      const url = new URL('fetch_ventas_cajero.php');
      if (fechaInicio) url.searchParams.append('fecha_inicio', fechaInicio);
      if (fechaFin) url.searchParams.append('fecha_fin', fechaFin);

      fetch(url)
        .then(response => response.json())
        .then(data => {
          if (data.error || !data.ventas || data.ventas.length === 0) {
            alert('No hay datos para exportar.');
            return;
          }
          const csv = [
            'Cajero,Total Ventas (Bs.),Nº Ventas',
            ...data.ventas.map(v => `${v.nombre},${parseFloat(v.total_ventas).toFixed(2)},${v.num_ventas}`)
          ].join('\n');
          const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
          const link = document.createElement('a');
          const periodo = fechaInicio && fechaFin ? `${fechaInicio}_al_${fechaFin}` : 'todos';
          link.setAttribute('href', URL.createObjectURL(blob));
          link.setAttribute('download', `ventas_por_cajero_${periodo}.csv`);
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
        })
        .catch(error => {
          console.error('Error al exportar:', error);
          alert('Error al generar el archivo CSV.');
        });
    }

    // Inicializar
    actualizarVentas();
    setInterval(() => actualizarVentas(
      document.getElementById('fecha-inicio').value,
      document.getElementById('fecha-fin').value
    ), 10000);
  </script>
</body>
</html>