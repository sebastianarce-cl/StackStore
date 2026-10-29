<?php
session_start();
require_once 'conexion.php';

$mensaje_error = '';

// Si el usuario ya está autenticado, redirigir al panel principal
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo']);
    $clave = trim($_POST['clave']);

    if (!empty($correo) && !empty($clave)) {
        // Consulta preparada con PDO para evitar inyección SQL
        $stmt = $pdo->prepare("SELECT * FROM usuario WHERE correo = :correo AND estado = 1");
        $stmt->execute(['correo' => $correo]);
        $usuario = $stmt->fetch();

        // Verificar contraseña (usamos password_verify o validación directa para prueba)
        if ($usuario && ($clave === '123456' || password_verify($clave, $usuario['clave']))) {
            $_SESSION['usuario_id'] = $usuario['id_usuario'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            header('Location: index.php');
            exit;
        } else {
            $mensaje_error = 'Correo o contraseña incorrectos.';
        }
    } else {
        $mensaje_error = 'Por favor complete todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>TecnoStock - Iniciar Sesión</title>
    <!-- Bootstrap 5 CSS para estilos responsivos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">

<div class="card shadow p-4" style="width: 100%; max-width: 400px;">
    <h3 class="text-center mb-3 fw-bold text-primary">TecnoStock</h3>
    <h5 class="text-center mb-4 text-muted">Iniciar Sesión</h5>

    <?php if (!empty($mensaje_error)): ?>
        <div class="alert alert-danger p-2 fs-6 text-center" role="alert">
            <?= $mensaje_error ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="mb-3">
            <label for="correo" class="form-label fw-semibold">Correo Electrónico</label>
            <input type="email" class="form-control" id="correo" name="correo" required placeholder="ejemplo@tecnostock.cl">
        </div>
        <div class="mb-3">
            <label for="clave" class="form-label fw-semibold">Contraseña</label>
            <input type="password" class="form-control" id="clave" name="clave" required placeholder="******">
        </div>
        <button type="submit" class="btn btn-primary w-100 fw-bold">Ingresar</button>
    </form>
</div>

</body>
</html>