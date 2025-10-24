<?php
include 'conexion.php';

// Generar token CSRF
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Manejar agregar usuario
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensaje = 'Error: Token CSRF inválido.';
    } else {
        $nombre = trim($_POST['nombre']);
        $usuario = trim($_POST['usuario']);
        $contrasena = password_hash(trim($_POST['contrasena']), PASSWORD_DEFAULT);
        $rol = isset($_POST['rol']) ? trim($_POST['rol']) : 'Empleado';

        if (empty($nombre) || empty($usuario) || empty($_POST['contrasena'])) {
            $mensaje = 'Los campos Nombre, Usuario y Contraseña son obligatorios.';
        } else {
            $stmt = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ?");
            if (!$stmt) {
                error_log("Error en prepare (agregar_usuario): " . $conexion->error, 3, 'errors.log');
                $mensaje = 'Error en la base de datos.';
            } else {
                $stmt->bind_param("s", $usuario);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $mensaje = 'El usuario ya está registrado.';
                } else {
                    $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, usuario, contrasena, rol) VALUES (?, ?, ?, ?)");
                    if (!$stmt) {
                        error_log("Error en prepare (insert_usuario): " . $conexion->error, 3, 'errors.log');
                        $mensaje = 'Error en la base de datos.';
                    } else {
                        $stmt->bind_param("ssss", $nombre, $usuario, $contrasena, $rol);
                        if ($stmt->execute()) {
                            $mensaje = 'Usuario agregado exitosamente.';
                        } else {
                            error_log("Error en execute (insert_usuario): " . $stmt->error, 3, 'errors.log');
                            $mensaje = 'Error al agregar usuario.';
                        }
                    }
                }
                $stmt->close();
            }
        }
    }
}

// Manejar editar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_usuario'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensaje = 'Error: Token CSRF inválido.';
    } else {
        $id = $_POST['id'];
        $nombre = trim($_POST['nombre']);
        $usuario = trim($_POST['usuario']);
        $contrasena = !empty($_POST['contrasena']) ? password_hash(trim($_POST['contrasena']), PASSWORD_DEFAULT) : null;
        $rol = isset($_POST['rol']) ? trim($_POST['rol']) : 'Empleado';

        if (empty($nombre) || empty($usuario)) {
            $mensaje = 'Los campos Nombre y Usuario son obligatorios.';
        } else {
            $stmt = $conexion->prepare("UPDATE usuarios SET nombre = ?, usuario = ?, rol = ? WHERE id_usuario = ?");
            if (!$stmt) {
                error_log("Error en prepare (editar_usuario): " . $conexion->error, 3, 'errors.log');
                $mensaje = 'Error en la base de datos.';
            } else {
                $stmt->bind_param("sssi", $nombre, $usuario, $rol, $id);
                if ($stmt->execute()) {
                    if ($contrasena) {
                        $stmt = $conexion->prepare("UPDATE usuarios SET contrasena = ? WHERE id_usuario = ?");
                        $stmt->bind_param("si", $contrasena, $id);
                        $stmt->execute();
                        $stmt->close();
                    }
                    $mensaje = 'Usuario actualizado exitosamente.';
                } else {
                    error_log("Error en execute (editar_usuario): " . $stmt->error, 3, 'errors.log');
                    $mensaje = 'Error al actualizar usuario.';
                }
                $stmt->close();
            }
        }
    }
}

// Manejar eliminar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_usuario'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensaje = 'Error: Token CSRF inválido.';
    } else {
        $id = $_POST['id'];
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        if (!$stmt) {
            error_log("Error en prepare (eliminar_usuario): " . $conexion->error, 3, 'errors.log');
            $mensaje = 'Error en la base de datos.';
        } else {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $mensaje = 'Usuario eliminado exitosamente.';
            } else {
                error_log("Error en execute (eliminar_usuario): " . $stmt->error, 3, 'errors.log');
                $mensaje = 'Error al eliminar usuario.';
            }
            $stmt->close();
        }
    }
}

// Obtener estadística de usuarios
$total_usuarios = 0;
$stmt = $conexion->prepare("SELECT COUNT(*) as total_usuarios FROM usuarios");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $total_usuarios = $result->fetch_assoc()['total_usuarios'];
    $stmt->close();
} else {
    error_log("Error en prepare (total_usuarios): " . $conexion->error, 3, 'errors.log');
}

// Obtener todos los usuarios directamente desde la base de datos
$usuarios = [];
$stmt = $conexion->prepare("SELECT id_usuario, nombre, usuario, rol FROM usuarios");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $usuarios = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    error_log("Error en prepare (usuarios): " . $conexion->error, 3, 'errors.log');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pollos Nymos - Gestión de Usuarios</title>
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
    .form-group select {
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
    #buscar-usuario {
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
      <li class="nav-item"><a class="nav-link active" href="admin_users.php">Usuarios</a></li>
    </ul>
  </nav>

  <main>
    <div class="bienvenida">Panel de Usuarios 👋</div>

    <section class="stats-section">
      <div class="stat-card">
        <h3>Total Usuarios</h3>
        <p id="total-usuarios"><?= htmlspecialchars($total_usuarios) ?></p>
      </div>
    </section>

    <?php if ($mensaje): ?>
      <div class="mensaje <?= strpos($mensaje, 'exitosamente') !== false ? 'exito' : 'error' ?>">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <section class="form-section">
      <h2>Agregar Usuario</h2>
      <form method="POST" id="form-agregar">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <div class="form-group">
          <label for="nombre">Nombre:</label>
          <input type="text" id="nombre" name="nombre" required>
        </div>
        <div class="form-group">
          <label for="usuario">Usuario:</label>
          <input type="text" id="usuario" name="usuario" required>
        </div>
        <div class="form-group">
          <label for="contrasena">Contraseña:</label>
          <input type="password" id="contrasena" name="contrasena" required>
        </div>
        <div class="form-group">
          <label for="rol">Rol:</label>
          <select id="rol" name="rol" required>
            <option value="Administrador">Administrador</option>
            <option value="Empleado" selected>Empleado</option>
          </select>
        </div>
        <button type="submit" name="usuario" class="btn-agregar">Agregar</button>
      </form>
    </section>

    <section class="form-section">
      <h2>Lista de Usuarios</h2>
      <input type="text" id="buscar-usuario" placeholder="Buscar por nombre o usuario">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Usuario</th>
            <th>Rol</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($usuarios as $usuario): ?>
            <tr data-id="<?= $usuario['id_usuario'] ?>">
              <td><?= htmlspecialchars($usuario['id_usuario']) ?></td>
              <td><?= htmlspecialchars($usuario['nombre']) ?></td>
              <td><?= htmlspecialchars($usuario['usuario']) ?></td>
              <td><?= htmlspecialchars($usuario['rol']) ?></td>
              <td>
                <button class="btn-editar" onclick="abrirModalEditar(<?= $usuario['id_usuario'] ?>, '<?= $usuario['nombre'] ?>', '<?= $usuario['usuario'] ?>', '<?= $usuario['rol'] ?>')">Editar</button>
                <button class="btn-eliminar" onclick="eliminarUsuario(<?= $usuario['id_usuario'] ?>)">Eliminar</button>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($usuarios)): ?>
            <tr><td colspan="5" style="text-align: center;">No hay usuarios.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>

    <div class="modal fade" id="modal-editar" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <h2>Editar Usuario</h2>
          <form method="POST" id="form-editar">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <input type="hidden" id="editar-id" name="id">
            <div class="form-group">
              <label for="editar-nombre">Nombre:</label>
              <input type="text" id="editar-nombre" name="nombre" required>
            </div>
            <div class="form-group">
              <label for="editar-usuario">Usuario:</label>
              <input type="text" id="editar-usuario" name="usuario" required>
            </div>
            <div class="form-group">
              <label for="editar-contrasena">Contraseña (dejar vacío para no cambiar):</label>
              <input type="password" id="editar-contrasena" name="contrasena">
            </div>
            <div class="form-group">
              <label for="editar-rol">Rol:</label>
              <select id="editar-rol" name="rol" required>
                <option value="Administrador">Administrador</option>
                <option value="Empleado">Empleado</option>
              </select>
            </div>
            <button type="submit" name="editar_usuario" class="btn-agregar">Guardar</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </form>
        </div>
      </div>
    </div>
  </main>

  <script>
    // Filtrar usuarios según búsqueda
    function filtrarUsuarios(busqueda) {
      const tabla = document.getElementById('tabla-usuarios');
      const filas = tabla.getElementsByTagName('tr');
      for (let i = 0; i < filas.length; i++) {
        const nombre = filas[i].getElementsByTagName('td')[1].textContent.toLowerCase();
        const usuario = filas[i].getElementsByTagName('td')[2].textContent.toLowerCase();
        if (nombre.includes(busqueda.toLowerCase()) || usuario.includes(busqueda.toLowerCase())) {
          filas[i].style.display = '';
        } else {
          filas[i].style.display = 'none';
        }
      }
    }

    // Evento de búsqueda
    document.getElementById('buscar-usuario').addEventListener('input', function() {
      filtrarUsuarios(this.value);
    });

    // Eliminar usuario
    function eliminarUsuario(id) {
      if (confirm('¿Seguro que quieres eliminar este usuario?')) {
        const data = new FormData();
        data.append('eliminar_usuario', true);
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
    function abrirModalEditar(id, nombre, usuario, rol) {
      document.getElementById('editar-id').value = id;
      document.getElementById('editar-nombre').value = nombre;
      document.getElementById('editar-usuario').value = usuario;
      document.getElementById('editar-rol').value = rol;
      new bootstrap.Modal(document.getElementById('modal-editar')).show();
    }
  </script>
</body>
</html>