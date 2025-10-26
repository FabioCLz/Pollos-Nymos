<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Ventas - Pollos Nymos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            /* Fondo con la imagen del logo centrada y cubierta */
            background: url('Imagen de WhatsApp 2025-10-15 a las 11.05.04_6343d1ff.jpg') no-repeat center center;
            background-size: cover;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .card {
            border-radius: 20px;
            padding: 2rem;
            width: 360px;
            background-color: rgba(255, 255, 255, 0.9); /* Fondo semi-transparente para destacar botones */
        }
        h1 {
            font-weight: bold;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .btn-primary {
            background-color: #FF6600;
            border-color: #FF6600;
        }
        .btn-primary:hover {
            background-color: #e65c00;
            border-color: #e65c00;
        }
        .btn-danger {
            background-color: #cc0000;
            border-color: #cc0000;
        }
        .btn-danger:hover {
            background-color: #b30000;
            border-color: #b30000;
        }
    </style>
</head>
<body>
    <div class="card shadow-lg text-center">
        <!-- Título -->
        <h1>🍗 Sistema de Ventas <br> Pollos Nymos</h1>
        <!-- Botones -->
        <button onclick="window.location.href='login_empleado.php'" class="btn btn-primary w-100 mb-3">Cajero / Empleado</button>
        <button onclick="window.location.href='login_admin.php'" class="btn btn-danger w-100">Administrador</button>
    </div>
</body>
</html>
