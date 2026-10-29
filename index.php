<?php
session_start();
require_once 'conexion.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Lógica de búsqueda
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// Consulta de productos (activos e inactivos) con su categoría
$sql = "SELECT p.*, c.nombre AS categoria_nombre 
        FROM producto p 
        INNER JOIN categoria c ON p.id_categoria = c.id_categoria 
        WHERE 1=1";

$params = [];

if (!empty($busqueda)) {
    $sql .= " AND (p.nombre LIKE :busqueda OR p.codigo LIKE :busqueda OR c.nombre LIKE :busqueda)";
    $params['busqueda'] = '%' . $busqueda . '%';
}

// Ordenar: primero los activos (estado = 1), luego por ID descendente
$sql .= " ORDER BY p.estado DESC, p.id_producto DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();

// Contar cuántos productos activos tienen alerta de bajo stock
$stmt_alerta = $pdo->query("SELECT COUNT(*) FROM producto WHERE stock_actual <= stock_minimo AND estado = 1");
$cant_bajo_stock = $stmt_alerta->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>TecnoStock - Panel de Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Barra de Navegación -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="index.php">TecnoStock</a>
        <div class="d-flex align-items-center text-white">
            <span class="me-3">Hola, <strong><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong></span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Cerrar Sesión</a>
        </div>
    </div>
</nav>

<div class="container">

    <!-- Alerta de Stock Bajo -->
    <?php if ($cant_bajo_stock > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
            <span class="fs-4 me-2">⚠️</span>
            <div>
                <strong>¡Atención!</strong> Hay <strong><?= $cant_bajo_stock ?></strong> producto(s) activo(s) con stock igual o menor al límite mínimo definido.
            </div>
        </div>
    <?php endif; ?>

    <!-- Encabezado y Acciones -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Catálogo de Productos</h2>
        <div>
            <a href="producto_form.php" class="btn btn-success">+ Nuevo Producto</a>
            <a href="movimiento_form.php" class="btn btn-primary">📦 Registrar Movimiento</a>
        </div>
    </div>

    <!-- Buscador -->
    <form method="GET" action="index.php" class="row g-2 mb-4">
        <div class="col-md-10">
            <input type="text" name="buscar" class="form-control" placeholder="Buscar por código, nombre o categoría..." value="<?= htmlspecialchars($busqueda) ?>">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-secondary w-100">Buscar</button>
            <?php if (!empty($busqueda)): ?>
                <a href="index.php" class="btn btn-outline-secondary">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Tabla de Productos -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Stock Actual</th>
                        <th>Stock Mín.</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productos)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No se encontraron productos registrados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($productos as $p): ?>
                            <tr class="<?= ($p['estado'] == 0) ? 'table-secondary text-muted' : (($p['stock_actual'] <= $p['stock_minimo']) ? 'table-danger' : '') ?>">
                                <td><code><?= htmlspecialchars($p['codigo']) ?></code></td>
                                <td class="fw-bold">
                                    <?= htmlspecialchars($p['nombre']) ?>
                                    <?php if ($p['estado'] == 0): ?>
                                        <span class="badge bg-secondary ms-1">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($p['categoria_nombre']) ?></span></td>
                                <td>$<?= number_format($p['precio'], 0, ',', '.') ?></td>
                                <td>
                                    <span class="fw-bold <?= ($p['estado'] == 0 ? 'text-muted' : (($p['stock_actual'] <= $p['stock_minimo']) ? 'text-danger' : 'text-success')) ?>">
                                        <?= $p['stock_actual'] ?>
                                    </span>
                                </td>
                                <td><?= $p['stock_minimo'] ?></td>
                                <td>
                                    <?php if ($p['estado'] == 1): ?>
                                        <a href="producto_form.php?id=<?= $p['id_producto'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                        <a href="producto_desactivar.php?id=<?= $p['id_producto'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Está seguro de desactivar este producto?')">Desactivar</a>
                                    <?php else: ?>
                                        <a href="producto_activar.php?id=<?= $p['id_producto'] ?>" class="btn btn-sm btn-success" onclick="return confirm('¿Desea reactivar este producto?')">Activar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>