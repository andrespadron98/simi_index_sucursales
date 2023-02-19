<?php
//INICIO DEL SCRIPT
$config = include('config/config.php');

if ($handle = opendir('C:\AceptaService\simi_prod\pdf')) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {

            echo "$entry\n";
        }
    }

    closedir($handle);
}
// mysqli_close($enlace);
?>