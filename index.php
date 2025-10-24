<?php
session_start();
include 'conexion.php';

// Validar sesión activa
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
  header('Location: login.php');
  exit;
}

// Recuperar nombre del usuario
$nombreUsuario = $_SESSION['user_name'];
$rolUsuario = $_SESSION['user_role'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Pollos Nymos - Cajero</title>
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      display: flex;
      flex-direction: column;
      height: 100vh;
    }
    header {
      background: #e74c3c;
      color: white;
      padding: 10px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .sidebar {
      position: fixed;
      top: 60px;
      left: 0;
      width: 180px;
      height: calc(100% - 60px);
      background: #f4f4f4;
      padding: 20px;
    }
    .sidebar ul {
      list-style: none;
      padding: 0;
    }
    .sidebar li {
      margin-bottom: 15px;
      font-weight: bold;
      cursor: pointer;
    }
    main {
      margin-left: 200px;
      padding: 20px;
      display: flex;
      gap: 40px;
    }
    .productos, .pedido {
      flex: 1;
    }
    .productos h2 {
      font-size: 24px;
      color: #c0392b;
      margin-top: 30px;
      margin-bottom: 10px;
      border-bottom: 2px solid #e74c3c;
      padding-bottom: 5px;
    }
    .grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 10px;
    }
    button {
      padding: 10px;
      border: none;
      border-radius: 5px;
      background: #d35400;
      color: white;
      cursor: pointer;
    }
    button:hover {
      background: #e67e22;
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
    td button {
      padding: 4px 8px;
      margin: 0 5px;
      font-weight: bold;
      background-color: #bdc3c7;
      color: #2c3e50;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    td button:hover {
      background-color: #95a5a6;
    }
    .total {
      margin-top: 10px;
      font-weight: bold;
      font-size: 18px;
    }
    .acciones {
      margin-top: 10px;
      display: flex;
      gap: 10px;
    }
    .aceptar {
      background: #27ae60;
    }
    .cancelar {
      background: #c0392b;
    }
    .mesa-selector {
      margin-bottom: 10px;
    }
    .mesa-selector select {
      padding: 5px;
      font-size: 16px;
    }
    .bienvenida {
      font-size: 18px;
      font-weight: bold;
      margin-bottom: 20px;
      color: #2c3e50;
    }
  </style>
</head>
<body>
  <header>
    <div class="logo">🐔 POLLOS NYMOS</div>
    <div class="user"><?= htmlspecialchars($rolUsuario) ?> <?= htmlspecialchars($nombreUsuario) ?></div>
    <button class="logout" onclick="location.href='logout.php'">Cerrar sesión</button>
  </header>

  <nav class="sidebar">
    <ul>
      <li>Inicio</li>
      <li>Inventario</li>
      <li>Recepción</li>
      <li>Delivery</li>
      <li>Ventas</li>
      <li>Reportes</li>
      <?php if ($rolUsuario === 'administrador'): ?>
        <li>Usuarios</li>
      <?php endif; ?>
    </ul>
  </nav>

  <main>
    <section class="productos">
      <div class="bienvenida">Bienvenido, <?= htmlspecialchars($nombreUsuario) ?> 👋</div>

      <h2>POLLOS</h2>
      <div class="grid">
        <button onclick="agregar('Pollo Broaster', 20)">Pollo Broaster</button>
        <button onclick="agregar('Pollo a la Plancha', 18)">Pollo a la Plancha</button>
        <button onclick="agregar('Pollo Crispy', 22)">Pollo Crispy</button>
        <button onclick="agregar('Pollo BBQ', 25)">Pollo BBQ</button>
        <button onclick="agregar('Combo Familiar', 45)">Combo Familiar</button>
        <button onclick="agregar('Combo Individual', 28)">Combo Individual</button>
      </div>

      <h2>BEBIDAS</h2>
      <div class="grid">
        <button onclick="agregar('Coca Cola 500ml', 8)">Coca Cola 500ml</button>
        <button onclick="agregar('Sprite 500ml', 8)">Sprite 500ml</button>
        <button onclick="agregar('Agua Mineral', 6)">Agua Mineral</button>
        <button onclick="agregar('Jugo de Maracuyá', 10)">Jugo de Maracuyá</button>
        <button onclick="agregar('Cerveza', 12)">Cerveza</button>
        <button onclick="agregar('Té Helado', 9)">Té Helado</button>
      </div>
    </section>

    <section class="pedido">
      <div class="mesa-selector">
        <label for="mesa">Seleccionar Mesa:</label>
        <select id="mesa" onchange="cambiarMesa()">
          <option value="mesa1">Mesa 1</option>
          <option value="mesa2">Mesa 2</option>
          <option value="mesa3">Mesa 3</option>
        </select>
      </div>
      <h2 id="mesa-titulo">Mesa 1</h2>
      <table>
        <thead>
          <tr><th>Descripción</th><th>Cantidad</th><th>Importe</th><th>Acción</th></tr>
        </thead>
        <tbody id="pedido-body"></tbody>
      </table>
      <div class="total">Total: Bs. <span id="total">0.00</span></div>
      <div class="acciones">
        <button class="aceptar">✅ Aceptar</button>
        <button class="cancelar" onclick="cancelarMesa()">❌ Cancelar</button>
      </div>
    </section>
  </main>

  <script>
    let pedidos = {
      mesa1: [],
      mesa2: [],
      mesa3: []
    };
    let totales = {
      mesa1: 0,
      mesa2: 0,
      mesa3: 0
    };
    let mesaActual = 'mesa1';

    function agregar(producto, precio) {
      const pedido = pedidos[mesaActual];
      const existente = pedido.find(item => item.producto === producto);
      if (existente) {
        existente.cantidad += 1;
        existente.precio += precio;
      } else {
        pedido.push({ producto, cantidad: 1, precio });
      }
      totales[mesaActual] += precio;
      actualizarTabla();
    }

    function modificarCantidad(index, cambio) {
      const pedido = pedidos[mesaActual];
      const item = pedido[index];
      const precioUnitario = item.precio / item.cantidad;

      item.cantidad += cambio;
      item.precio = precioUnitario * item.cantidad;

      if (item.cantidad <= 0) {
        totales[mesaActual] -= item.precio;
        pedido.splice(index, 1);
      } else {
        totales[mesaActual] += precioUnitario * cambio;
      }

      actualizarTabla();
    }

    function eliminarProducto(index) {
      const pedido = pedidos[mesaActual];
      const item = pedido[index];
      totales[mesaActual] -= item.precio;
      pedido.splice(index, 1);
      actualizarTabla();
    }

    function actualizarTabla() {
      const tbody = document.getElementById("pedido-body");
      tbody.innerHTML = "";
      pedidos[mesaActual].forEach((item, index) => {
        const row = `<tr>
          <td>${item.producto}</td>
          <td>
            <button onclick="modificarCantidad(${index}, -1)">–</button>
            ${item.cantidad}
            <button onclick="modificarCantidad(${index}, 1)">+</button>
          </td>
          <td>Bs. ${item.precio.toFixed(2)}</td>
          <td>
            <button onclick="eliminarProducto(${index})">🗑️</button>
          </td>
        </tr>`;
        tbody.innerHTML += row;
      });
      document.getElementById("total").textContent = totales[mesaActual].toFixed(2);
      document.getElementById("mesa-titulo").textContent = mesaActual.replace("mesa", "Mesa ");
    }

    function cambiarMesa() {
      mesaActual = document.getElementById("mesa").value;
      actualizarTabla();
    }

    function cancelarMesa() {
      pedidos[mesaActual] = [];
      totales[mesaActual] = 0;
      actualizarTabla();
    }
  </script>
</body>
</html>