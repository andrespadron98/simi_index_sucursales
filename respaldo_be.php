<?php
//TAREAS 

//cd C:\xampp\htdocs && git clone https://github.com/andrespadron98/simi_index_sucursales.git . && mkdir config && type nul > config/config.php
//schtasks /create /tn "ActualizarBESimiData" /tr "C:\xampp\php\php-win.exe C:\xampp\htdocs\respaldo_be.php" /sc minute /mo 5
//schtasks /create /tn "ActualizarScriptSimi" /tr "C:\xampp\htdocs\pull.sh" /sc minute /mo 1
//git config --global --add safe.directory C:/xampp/htdocs

//INICIO DEL SCRIPT
include("alt_autoload.php-dist");
$config = include('config/config.php');
$ruta = "C:\AceptaService\simi_prod\pdf";
$parser = new \Smalot\PdfParser\Parser();
$ultBoletaArchivo = "C:\\xampp\htdocs\ultima_be.txt";

ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');

//Revisar si existe y Leer la ultima boleta del archivo ultima_be.txt
if(file_exists($ultBoletaArchivo)){
    $archivo = fopen($ultBoletaArchivo, "r");
    $ultima_boleta = fgets($archivo);
    fclose($archivo);
}else{
    echo "No se pudo obtener la ultima boleta\n";
    $ultima_boleta = 0;
}

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
            // echo "$entry<br>";
            //Verificar que los archivos no tengan mas de 15 días de antiguedad
            $fecha_archivo = date("Y-m-d", filemtime($ruta.'\\'.$entry));
            $fecha_actual = date("Y-m-d");
            $fecha_archivo = strtotime($fecha_archivo);
            $fecha_actual = strtotime($fecha_actual);
            $diferencia = $fecha_actual - $fecha_archivo;
            $dias = floor($diferencia / (60 * 60 * 24));
            if($dias <= 1){
                //Verificar que los archivos sean .pdf
                $extension = pathinfo($entry, PATHINFO_EXTENSION);
                if($extension == 'pdf'){
                    //Obtener el folio de la boleta del nombre del archivo EJ 76553560-3_T39_F3309071.pdf
                    $n_boleta = explode("_", $entry);
                    $n_boleta = explode(".", $n_boleta[2]);
                    $n_boleta = $n_boleta[0];
                    $n_boleta = str_replace("F", "", $n_boleta);
                    $n_boleta = intval($n_boleta);
                    echo "$n_boleta - $ultima_boleta\n";

                    //Verificar que el folio de la boleta sea mayor al ultimo folio registrado
                    if($n_boleta > $ultima_boleta){
                        //Actualizar el archivo ultima_be.txt con el nuevo folio de la boleta
                        $archivo = fopen($ultBoletaArchivo, "w");
                        fwrite($archivo, $n_boleta);
                        fclose($archivo);
                        // echo "$n_boleta<br>";

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
        }
    }
    closedir($handle);
}else{
    echo "No se pudo abrir el directorio";
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