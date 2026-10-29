<?php
session_start();
require_once 'conexion.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id_producto = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_producto > 0) {
    // Cambiar el estado a 0 (desactivado) en lugar de DELETE
    $stmt = $pdo->prepare("UPDATE producto SET estado = 0 WHERE id_producto = :id");
    $stmt->execute(['id' => $id_producto]);
}

// Redirigir de vuelta al catálogo
header('Location: index.php');
exit;
?>