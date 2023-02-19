<?php
//INICIO DEL SCRIPT
include("alt_autoload.php-dist");
$config = include('config/config.php');
$ruta = "C:\AceptaService\simi_prod\pdf";

$parser = new \Smalot\PdfParser\Parser();

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
                $n_caja = buscar_texto_entre($text, "Nro. Caja:", "Fecha");
    
                echo "$numero_remision - $n_boleta - $n_caja<br>";
            }
        }
    }

    closedir($handle);
}

function buscar_texto_entre($texto, $inicio, $termino){
    $texto = ' ' . $texto;
    $ini = strpos($texto, $inicio);
    if ($ini == 0) return '';
    $ini += strlen($inicio);
    $len = strpos($texto, $termino, $ini) - $ini;
    return substr($texto, $ini, $len);
}
// mysqli_close($enlace);
?>