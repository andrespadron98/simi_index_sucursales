<?php
//TAREAS PARA WINDOWS
//schtasks /create /tn "InsertarDatosSimiData" /tr "C:\xampp\php\php-win.exe C:\xampp\htdocs\index.php" /sc minute /mo 1
//schtasks /create /tn "ActualizarScriptSimi" /tr "C:\xampp\htdocs\pull.sh" /sc minute /mo 1

//INICIO DEL SCRIPT
$config = include('config/config.php');

$enlace = mysqli_connect($config['host'], $config['user'], $config['clave'], $config['bbdd']);
if (!$enlace) {
    echo "Error: No se pudo conectar a MySQL." . PHP_EOL;
    echo "errno de depuración: " . mysqli_connect_errno() . PHP_EOL;
    echo "error de depuración: " . mysqli_connect_error() . PHP_EOL;
    exit;
}

?>

