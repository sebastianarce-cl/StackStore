<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id_producto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$es_edicion = $id_producto > 0;

$codigo = '';
$nombre = '';
$descripcion = '';
$precio = '';
$stock_actual = '';
$stock_minimo = '';
$id_categoria = '';

$error = '';

// Cargar categorías activas para el desplegable
$stmt_cat = $pdo->query("SELECT * FROM categoria WHERE estado = 1 ORDER BY nombre ASC");
$categorias = $stmt_cat->fetchAll();

// Si es edición, obtener los datos del producto
if ($es_edicion) {
    $stmt = $pdo->prepare("SELECT * FROM producto WHERE id_producto = :id");
    $stmt->execute(['id' => $id_producto]);
    $prod = $stmt->fetch();

    if ($prod) {
        $codigo = $prod['codigo'];
        $nombre = $prod['nombre'];
        $descripcion = $prod['descripcion'];
        $precio = $prod['precio'];
        $stock_actual = $prod['stock_actual'];
        $stock_minimo = $prod['stock_minimo'];
        $id_categoria = $prod['id_categoria'];
    } else {
        header('Location: index.php');
        exit;
    }
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = (float)$_POST['precio'];
    $stock_actual = (int)$_POST['stock_actual'];
    $stock_minimo = (int)$_POST['stock_minimo'];
    $id_categoria = (int)$_POST['id_categoria'];

    // Validaciones en el Servidor (PHP)
    if (empty($codigo) || empty($nombre) || $id_categoria <= 0) {
        $error = 'Por favor complete todos los campos obligatorios.';
    } elseif ($precio < 0 || $stock_actual < 0 || $stock_minimo < 0) {
        $error = 'El precio, el stock actual y el stock mínimo no pueden ser valores negativos.';
    } else {
        // Validar que el código no esté repetido
        $sql_cod = "SELECT id_producto FROM producto WHERE codigo = :codigo";
        if ($es_edicion) {
            $sql_cod .= " AND id_producto != " . $id_producto;
        }
        $stmt_cod = $pdo->prepare($sql_cod);
        $stmt_cod->execute(['codigo' => $codigo]);

        if ($stmt_cod->fetch()) {
            $error = 'El código ingresado ya pertenece a otro producto.';
        } else {
            if ($es_edicion) {
                // Actualizar producto
                $sql_upd = "UPDATE producto SET 
                            codigo = :codigo, 
                            nombre = :nombre, 
                            descripcion = :descripcion, 
                            precio = :precio, 
                            stock_actual = :stock_actual, 
                            stock_minimo = :stock_minimo, 
                            id_categoria = :id_categoria 
                            WHERE id_producto = :id";
                $stmt = $pdo->prepare($sql_upd);
                $stmt->execute([
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'precio' => $precio,
                    'stock_actual' => $stock_actual,
                    'stock_minimo' => $stock_minimo,
                    'id_categoria' => $id_categoria,
                    'id' => $id_producto
                ]);
            } else {
                // Insertar nuevo producto
                $sql_ins = "INSERT INTO producto (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, id_categoria) 
                            VALUES (:codigo, :nombre, :descripcion, :precio, :stock_actual, :stock_minimo, :id_categoria)";
                $stmt = $pdo->prepare($sql_ins);
                $stmt->execute([
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'precio' => $precio,
                    'stock_actual' => $stock_actual,
                    'stock_minimo' => $stock_minimo,
                    'id_categoria' => $id_categoria
                ]);
            }
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $es_edicion ? 'Editar' : 'Nuevo' ?> Producto - TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container my-5" style="max-width: 650px;">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><?= $es_edicion ? 'Editar Producto' : 'Registrar Nuevo Producto' ?></h4>
        </div>
        <div class="card-body">

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger p-2 fs-6 text-center" role="alert">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="formProducto">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="codigo" class="form-label fw-bold">Código *</label>
                        <input type="text" class="form-control" id="codigo" name="codigo" value="<?= htmlspecialchars($codigo) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="id_categoria" class="form-label fw-bold">Categoría *</label>
                        <select class="form-select" id="id_categoria" name="id_categoria" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?= $c['id_categoria'] ?>" <?= $id_categoria == $c['id_categoria'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="nombre" class="form-label fw-bold">Nombre del Producto *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>
                    </div>

                    <div class="col-12">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="2"><?= htmlspecialchars($descripcion) ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label for="precio" class="form-label fw-bold">Precio ($) *</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="precio" name="precio" value="<?= htmlspecialchars($precio) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="stock_actual" class="form-label fw-bold">Stock Actual *</label>
                        <input type="number" min="0" class="form-control" id="stock_actual" name="stock_actual" value="<?= htmlspecialchars($stock_actual) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="stock_minimo" class="form-label fw-bold">Stock Mínimo *</label>
                        <input type="number" min="0" class="form-control" id="stock_minimo" name="stock_minimo" value="<?= htmlspecialchars($stock_minimo) ?>" required>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary fw-bold">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Validaciones Cliente con JS -->
<script>
document.getElementById('formProducto').addEventListener('submit', function(e) {
    const precio = parseFloat(document.getElementById('precio').value);
    const stockActual = parseInt(document.getElementById('stock_actual').value);
    const stockMinimo = parseInt(document.getElementById('stock_minimo').value);

    if (precio < 0 || stockActual < 0 || stockMinimo < 0) {
        e.preventDefault();
        alert('Por favor ingrese valores no negativos para precio y stock.');
    }
});
</script>
</body>
</html>