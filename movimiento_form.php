<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$exito = '';

// Cargar productos activos
$stmt_prod = $pdo->query("SELECT id_producto, codigo, nombre, stock_actual FROM producto WHERE estado = 1 ORDER BY nombre ASC");
$productos = $stmt_prod->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_producto = (int)$_POST['id_producto'];
    $tipo = $_POST['tipo']; // 'entrada' o 'salida'
    $cantidad = (int)$_POST['cantidad'];
    $observacion = trim($_POST['observacion']);
    $id_usuario = $_SESSION['usuario_id'];

    // Validaciones
    if ($id_producto <= 0 || !in_array($tipo, ['entrada', 'salida']) || $cantidad <= 0) {
        $error = 'Por favor complete todos los campos obligatorios con valores válidos.';
    } else {
        // Consultar el stock actual del producto seleccionado
        $stmt_check = $pdo->prepare("SELECT stock_actual FROM producto WHERE id_producto = :id AND estado = 1");
        $stmt_check->execute(['id' => $id_producto]);
        $prod = $stmt_check->fetch();

        if (!$prod) {
            $error = 'El producto seleccionado no existe o está desactivado.';
        } elseif ($tipo === 'salida' && $cantidad > $prod['stock_actual']) {
            // Regla de negocio: Una salida no puede dejar el stock bajo cero
            $error = "Stock insuficiente. Intenta retirar $cantidad unidades, pero solo hay {$prod['stock_actual']} disponibles.";
        } else {
            try {
                // Iniciar transacción SQL para garantizar que ambas operaciones se hagan juntas
                $pdo->beginTransaction();

                // 1. Insertar el historial del movimiento
                $sql_mov = "INSERT INTO movimiento (tipo, cantidad, observacion, id_producto, id_usuario) 
                            VALUES (:tipo, :cantidad, :observacion, :id_producto, :id_usuario)";
                $stmt_mov = $pdo->prepare($sql_mov);
                $stmt_mov->execute([
                    'tipo' => $tipo,
                    'cantidad' => $cantidad,
                    'observacion' => $observacion,
                    'id_producto' => $id_producto,
                    'id_usuario' => $id_usuario
                ]);

                // 2. Actualizar el stock actual en la tabla producto
                if ($tipo === 'entrada') {
                    $sql_stock = "UPDATE producto SET stock_actual = stock_actual + :cantidad WHERE id_producto = :id";
                } else {
                    $sql_stock = "UPDATE producto SET stock_actual = stock_actual - :cantidad WHERE id_producto = :id";
                }
                $stmt_stock = $pdo->prepare($sql_stock);
                $stmt_stock->execute([
                    'cantidad' => $cantidad,
                    'id' => $id_producto
                ]);

                // Confirmar transacción
                $pdo->commit();

                header('Location: index.php');
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Error al registrar el movimiento: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Movimiento - TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container my-5" style="max-width: 600px;">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Registrar Entradas / Salidas</h4>
        </div>
        <div class="card-body">

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger p-2 fs-6 text-center" role="alert">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="formMovimiento">
                <div class="mb-3">
                    <label for="id_producto" class="form-label fw-bold">Producto *</label>
                    <select class="form-select" id="id_producto" name="id_producto" required>
                        <option value="">Seleccione un producto...</option>
                        <?php foreach ($productos as $p): ?>
                            <option value="<?= $p['id_producto'] ?>">
                                [<?= htmlspecialchars($p['codigo']) ?>] <?= htmlspecialchars($p['nombre']) ?> (Disponibles: <?= $p['stock_actual'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="tipo" class="form-label fw-bold">Tipo de Movimiento *</label>
                    <select class="form-select" id="tipo" name="tipo" required>
                        <option value="entrada">📥 Entrada (Aumenta stock)</option>
                        <option value="salida">📤 Salida (Disminuye stock)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="cantidad" class="form-label fw-bold">Cantidad *</label>
                    <input type="number" min="1" class="form-control" id="cantidad" name="cantidad" required placeholder="Ej: 5">
                </div>

                <div class="mb-3">
                    <label for="observacion" class="form-label">Observación / Motivo</label>
                    <textarea class="form-control" id="observacion" name="observacion" rows="2" placeholder="Ej: Reposición de proveedor, venta directa..."></textarea>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary fw-bold">Registrar Movimiento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('formMovimiento').addEventListener('submit', function(e) {
    const cantidad = parseInt(document.getElementById('cantidad').value);
    if (cantidad <= 0) {
        e.preventDefault();
        alert('La cantidad del movimiento debe ser un número mayor a cero.');
    }
});
</script>
</body>
</html>