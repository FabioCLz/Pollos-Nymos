<?php
include 'conexion.php';

// Iniciar sesión y generar token CSRF
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// === PROTECCIÓN: Solo administradores ===
if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'Administrador') {
    header("Location: login.php");
    exit;
}

// Mensaje de retroalimentación
$mensaje = '';

// === AGREGAR USUARIO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensaje = 'Error: Token CSRF inválido.';
    } else {
        $nombre = trim($_POST['nombre']);
        $usuario = trim($_POST['usuario']);
        $contrasena_raw = $_POST['contrasena'];
        $rol = in_array($_POST['rol'], ['Administrador', 'Empleado']) ? $_POST['rol'] : 'Empleado';

        if (empty($nombre) || empty($usuario) || empty($contrasena_raw)) {
            $mensaje = 'Nombre, Usuario y Contraseña son obligatorios.';
        } else {
            // Verificar si el usuario ya existe
            $stmt = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ?");
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $mensaje = 'El usuario ya está registrado.';
            } else {
                $contrasena = password_hash($contrasena_raw, PASSWORD_DEFAULT);
                $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, usuario, contrasena, rol) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $nombre, $usuario, $contrasena, $rol);

                if ($stmt->execute()) {
                    $mensaje = 'Usuario agregado exitosamente.';
                } else {
                    error_log("Error INSERT: " . $stmt->error, 3, 'errors.log');
                    $mensaje = 'Error al guardar el usuario.';
                }
            }
            $stmt->close();
        }
    }
}

// === EDITAR USUARIO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_usuario'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensaje = 'Error: Token CSRF inválido.';
    } else {
        $id = (int)$_POST['id'];
        $nombre = trim($_POST['nombre']);
        $usuario = trim($_POST['usuario']);
        $rol = in_array($_POST['rol'], ['Administrador', 'Empleado']) ? $_POST['rol'] : 'Empleado';

        if (empty($nombre) || empty($usuario)) {
            $mensaje = 'Nombre y Usuario son obligatorios.';
        } else {
            // Verificar duplicado (excluyendo el usuario actual)
            $stmt = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ? AND id_usuario != ?");
            $stmt->bind_param("si", $usuario, $id);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $mensaje = 'El usuario ya está registrado.';
            } else {
                $stmt->close();
                $stmt = $conexion->prepare("UPDATE usuarios SET nombre = ?, usuario = ?, rol = ? WHERE id_usuario = ?");
                $stmt->bind_param("sssi", $nombre, $usuario, $rol, $id);

                if ($stmt->execute()) {
                    // Actualizar contraseña si se ingresó
                    if (!empty($_POST['contrasena'])) {
                        $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
                        $stmt2 = $conexion->prepare("UPDATE usuarios SET contrasena = ? WHERE id_usuario = ?");
                        $stmt2->bind_param("si", $contrasena, $id);
                        $stmt2->execute();
                        $stmt2->close();
                    }
                    $mensaje = 'Usuario actualizado exitosamente.';
                } else {
                    $mensaje = 'Error al actualizar usuario.';
                }
                $stmt->close();
            }
        }
    }
}

// === ELIMINAR USUARIO (AJAX) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_usuario'])) {
    header('Content-Type: application/json');
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Token inválido.']);
        exit;
    }

    $id = (int)$_POST['id'];
    $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Usuario eliminado.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar.']);
    }
    $stmt->close();
    exit;
}

// === OBTENER DATOS ===
$total_usuarios = 0;
$stmt = $conexion->prepare("SELECT COUNT(*) as total FROM usuarios");
$stmt->execute();
$total_usuarios = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$usuarios = [];
$stmt = $conexion->prepare("SELECT id_usuario, nombre, usuario, rol FROM usuarios ORDER BY id_usuario");
$stmt->execute();
$usuarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pollos Nymos - Usuarios</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    body { font-family: 'Poppins', sans-serif; background: #f5f6fa; display: flex; }
    header { background: #e74c3c; color: white; padding: 15px; position: fixed; width: 100%; z-index: 1000; }
    .logo { font-size: 24px; font-weight: bold; }
    .sidebar { width: 180px; background: #f4f4f4; padding-top: 70px; position: fixed; height: 100vh; }
    .sidebar .nav-link { color: #2c3e50; font-weight: bold; padding: 12px; border-radius: 6px; margin: 5px 10px; }
    .sidebar .nav-link:hover { background: #e0e0e0; }
    .sidebar .nav-link.active { background: #e74c3c; color: white; }
    main { margin-left: 180px; padding: 90px 20px 20px; }
    .form-section, .table-section { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
    .form-section h2, .table-section h2 { color: #c0392b; border-bottom: 2px solid #e74c3c; padding-bottom: 8px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { font-weight: bold; color: #2c3e50; }
    .form-control, select { border-radius: 6px; }
    .btn-agregar { background: #27ae60; border: none; }
    .btn-agregar:hover { background: #219653; }
    .btn-editar { background: #3498db; font-size: 0.9rem; padding: 5px 10px; }
    .btn-eliminar { background: #c0392b; font-size: 0.9rem; padding: 5px 10px; }
    .mensaje { padding: 12px; border-radius: 6px; margin-bottom: 15px; font-weight: bold; }
    .mensaje.exito { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .mensaje.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    #buscar-usuario { max-width: 300px; margin-bottom: 15px; }
  </style>
</head>
<body>
  <header>
    <div class="logo">POLLOS NYMOS</div>
  </header>

  <nav class="sidebar">
    <ul class="nav flex-column">
      <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
      <li class="nav-item"><a class="nav-link" href="#">Inventario</a></li>
      <li class="nav-item"><a class="nav-link" href="reportes.php">Reportes</a></li>
      <li class="nav-item"><a class="nav-link active" href="admin_users.php">Usuarios</a></li>
    </ul>
  </nav>

  <main>
    <h2 class="mb-4">Gestión de Usuarios</h2>

    <?php if ($mensaje): ?>
      <div class="mensaje <?= strpos($mensaje, 'exitosamente') !== false ? 'exito' : 'error' ?>">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <div class="form-section">
      <h2>Agregar Usuario</h2>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <div class="row">
          <div class="col-md-6 form-group">
            <label>Nombre</label>
            <input type="text" class="form-control" name="nombre" required>
          </div>
          <div class="col-md-6 form-group">
            <label>Usuario</label>
            <input type="text" class="form-control" name="usuario" required>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 form-group">
            <label>Contraseña</label>
            <input type="password" class="form-control" name="contrasena" required>
          </div>
          <div class="col-md-6 form-group">
            <label>Rol</label>
            <select class="form-control" name="rol">
              <option value="Administrador">Administrador</option>
              <option value="Empleado" selected>Empleado</option>
            </select>
          </div>
        </div>
        <button type="submit" name="usuario" class="btn btn-agregar text-white">Agregar Usuario</button>
      </form>
    </div>

    <div class="table-section">
      <h2>Lista de Usuarios (<?= $total_usuarios ?>)</h2>
      <input type="text" id="buscar-usuario" class="form-control" placeholder="Buscar por nombre o usuario...">
      <div class="table-responsive">
        <table class="table table-bordered table-hover" id="tabla-usuarios">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Usuario</th>
              <th>Rol</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($usuarios as $u): ?>
              <tr>
                <td><?= $u['id_usuario'] ?></td>
                <td><?= htmlspecialchars($u['nombre']) ?></td>
                <td><?= htmlspecialchars($u['usuario']) ?></td>
                <td><span class="badge bg-<?= $u['rol'] === 'Administrador' ? 'danger' : 'success' ?>"><?= $u['rol'] ?></span></td>
                <td>
                  <button class="btn btn-editar btn-sm" onclick="abrirEditar(<?= $u['id_usuario'] ?>, '<?= addslashes($u['nombre']) ?>', '<?= addslashes($u['usuario']) ?>', '<?= $u['rol'] ?>')">Editar</button>
                  <button class="btn btn-eliminar btn-sm" onclick="eliminarUsuario(<?= $u['id_usuario'] ?>)">Eliminar</button>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($usuarios)): ?>
              <tr><td colspan="5" class="text-center">No hay usuarios registrados.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- Modal Editar -->
  <div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Editar Usuario</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" id="edit-id" name="id">
            <div class="mb-3">
              <label>Nombre</label>
              <input type="text" class="form-control" id="edit-nombre" name="nombre" required>
            </div>
            <div class="mb-3">
              <label>Usuario</label>
              <input type="text" class="form-control" id="edit-usuario" name="usuario" required>
            </div>
            <div class="mb-3">
              <label>Contraseña (dejar vacío para no cambiar)</label>
              <input type="password" class="form-control" id="edit-contrasena" name="contrasena">
            </div>
            <div class="mb-3">
              <label>Rol</label>
              <select class="form-control" id="edit-rol" name="rol">
                <option value="Administrador">Administrador</option>
                <option value="Empleado">Empleado</option>
              </select>
            </div>
            <button type="submit" name="editar_usuario" class="btn btn-success">Guardar Cambios</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Búsqueda en tabla
    document.getElementById('buscar-usuario').addEventListener('input', function() {
      const term = this.value.toLowerCase();
      const filas = document.querySelectorAll('#tabla-usuarios tbody tr');
      filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        fila.style.display = texto.includes(term) ? '' : 'none';
      });
    });

    // Eliminar con AJAX
    function eliminarUsuario(id) {
      if (!confirm('¿Eliminar este usuario?')) return;
      const data = new FormData();
      data.append('eliminar_usuario', '1');
      data.append('id', id);
      data.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');

      fetch('', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
          alert(res.message);
          if (res.success) location.reload();
        });
    }

    // Abrir modal editar
    function abrirEditar(id, nombre, usuario, rol) {
      document.getElementById('edit-id').value = id;
      document.getElementById('edit-nombre').value = nombre;
      document.getElementById('edit-usuario').value = usuario;
      document.getElementById('edit-rol').value = rol;
      new bootstrap.Modal(document.getElementById('modalEditar')).show();
    }
  </script>
</body>
</html>