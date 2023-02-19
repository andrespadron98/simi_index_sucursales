<?php
//INICIO DEL SCRIPT
include("alt_autoload.php-dist");
$config = include('config/config.php');
$ruta = "C:\AceptaService\simi_prod\pdf";

$parser = new \Smalot\PdfParser\Parser();


function buscar_texto_entre($texto, $inicio, $termino){
    $texto = ' ' . $texto;
    $ini = strpos($texto, $inicio);
    if ($ini == 0) return '';
    $ini += strlen($inicio);
    $len = strpos($texto, $termino, $ini) - $ini;
    return substr($texto, $ini, $len);
}

if ($handle = opendir($ruta)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
            //Verificar que los archivos sean pdf y no tengan mas de 5 días de antiguedad
            $fecha_archivo = date("Y-m-d", filemtime($ruta.'\\'.$entry));
            $fecha_actual = date("Y-m-d");
            $fecha_archivo = strtotime($fecha_archivo);
            $fecha_actual = strtotime($fecha_actual);
            $diferencia = $fecha_actual - $fecha_archivo;
            $dias = floor($diferencia / (60 * 60 * 24));
            if($dias <= 15){
                $pdf = $parser->parseFile($ruta.'\\'.$entry);
                $text = $pdf->getText();
    
                $numero_remision = buscar_texto_entre($text, "REMISION:", "Nro. Caja:");
                $n_boleta = buscar_texto_entre($text, "Nro. Boleta:", "Hora");
                // echo "$numero_remision - $n_boleta<br>";
                $array[] = array(
                    'numero_remision' => $numero_remision,
                    'n_boleta' => $n_boleta
                );
            }
        }
    }
    closedir($handle);
}

//Revisar si el array no esta vacio y enviarselo al servidor
if(!empty($array)){
    $enlace = mysqli_connect($config['host'], $config['user'], $config['clave'], $config['bbdd']);
    if (!$enlace) {
        echo "Error: No se pudo conectar a MySQL." . PHP_EOL;
        echo "errno de depuración: " . mysqli_connect_errno() . PHP_EOL;
        echo "error de depuración: " . mysqli_connect_error() . PHP_EOL;
        exit;
    }
    foreach($array as $key => $value){
        //Actualizar el N_Boleta en la tabla Venta segun el Id_Venta que es la remisión
        $sql = "UPDATE Venta SET N_Boleta = '".$value['n_boleta']."' WHERE Id_Venta = '".$value['numero_remision']."'";
        if (mysqli_query($enlace, $sql)) {
            echo "Nueva boleta actualizada<br>";
        } else {
            echo "Error: " . $sql . "<br>" . mysqli_error($enlace);
        }
    }
    mysqli_close($enlace);
}

?>