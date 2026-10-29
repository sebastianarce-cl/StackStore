<?php
// Parámetros de conexión a XAMPP / MySQL
$host = 'localhost';
$dbname = 'tecnostock_db';
$username = 'root'; // Usuario por defecto en XAMPP
$password = '';     // Contraseña por defecto en XAMPP (vacía)

try {
    // Creamos la conexión usando PDO (PHP Data Objects)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Configurar PDO para que lance excepciones en caso de error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Configurar el modo de obtención de datos por defecto a Array Asociativo
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si hay un error, interrumpe la ejecución y muestra el mensaje
    die("Error crítico de conexión a la base de datos: " . $e->getMessage());
}
?>