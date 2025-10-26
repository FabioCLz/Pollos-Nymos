<?php
include("../conexion.php");

if(!isset($_GET['id_venta'])){
    die("Venta no especificada");
}

$id_venta = $_GET['id_venta'];

/*
    🔍 Consultamos los datos completos:
    - Datos de la venta
    - Datos del empleado (quien registró la venta)
    - Datos del cliente (a quien se vendió)
*/
$venta = $conn->query("
    SELECT 
        v.id_venta,
        v.fecha_venta,
        v.total,
        e.nombre AS nombre_empleado,
        e.apellido_paterno AS ap_empleado,
        e.apellido_materno AS am_empleado,
        c.nombre AS nombre_cliente,
        c.apellido_paterno AS ap_cliente,
        c.apellido_materno AS am_cliente,
        c.carnet AS carnet_cliente
    FROM ventas v
    INNER JOIN usuarios e ON v.carnet_usuario = e.carnet   -- Empleado
    LEFT JOIN usuarios c ON v.id_venta = v.id_venta         -- Cliente (simulado)
    WHERE v.id_venta = $id_venta
")->fetch_assoc();

/*
    ⚠️ Nota:
    Dado que la tabla `ventas` actualmente no guarda el carnet del cliente,
    este código asume que el cliente fue registrado aparte.

    Si deseas guardar el cliente real dentro de la venta,
    se recomienda añadir una columna extra en la tabla:
        ALTER TABLE ventas ADD carnet_cliente VARCHAR(20);
    y usarla en el INSERT desde panel_empleado.php
*/

// Consultar detalle de la venta
$detalle = $conn->query("
    SELECT p.nombre, dv.cantidad, dv.subtotal
    FROM detalle_venta dv
    INNER JOIN productos p ON dv.id_producto = p.id_producto
    WHERE dv.id_venta = $id_venta
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket de Venta #<?php echo $id_venta; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fff; }
        .ticket-container {
            max-width: 600px;
            margin: auto;
            border: 2px solid #000;
            padding: 20px;
            border-radius: 10px;
        }
        .ticket-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .ticket-header h3 {
            margin: 0;
        }
    </style>
</head>
<body class="p-4">
    <div class="ticket-container">
        <div class="ticket-header">
            <h3>🍗 POLLERÍA NYMOS</h3>
            <p>San Francisco, Galería República - Piso 4, Local 31</p>
            <p><strong>Tel:</strong> +591 71234567</p>
            <hr>
            <h4>🎫 Ticket de Venta #<?php echo $id_venta; ?></h4>
        </div>

        <p><strong>Fecha:</strong> <?php echo $venta['fecha_venta']; ?></p>
        <p><strong>Empleado:</strong> <?php echo $venta['nombre_empleado'].' '.$venta['ap_empleado'].' '.$venta['am_empleado']; ?></p>
        
        <?php if (!empty($venta['nombre_cliente'])): ?>
        <p><strong>Cliente:</strong> <?php echo $venta['nombre_cliente'].' '.$venta['ap_cliente'].' '.$venta['am_cliente']; ?></p>
        <p><strong>Carnet:</strong> <?php echo $venta['carnet_cliente']; ?></p>
        <?php endif; ?>

        <table class="table table-bordered mt-3">
            <thead class="table-dark">
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Subtotal (Bs)</th>
                </tr>
            </thead>
            <tbody>
                <?php while($fila = $detalle->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $fila['nombre']; ?></td>
                    <td><?php echo $fila['cantidad']; ?></td>
                    <td><?php echo number_format($fila['subtotal'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2">Total</th>
                    <th><?php echo number_format($venta['total'], 2); ?> Bs</th>
                </tr>
            </tfoot>
        </table>

        <div class="text-center mt-4">
            <button onclick="window.print();" class="btn btn-success">🖨️ Imprimir Ticket</button>
            <a href="panel_empleado.php" class="btn btn-secondary">↩ Volver al Panel</a>
        </div>
    </div>
</body>
</html>
