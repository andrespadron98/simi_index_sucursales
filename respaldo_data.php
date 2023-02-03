<?php
$config = include('config/config.php');
$connectionInfo = array( "Database"=> $config['bbddSposaa'], "UID"=> $config['userSposaa'], "PWD"=> $config['claveSposaa']);
$conn = sqlsrv_connect($config['hostSposaa'], $connectionInfo);

if( $conn ) {
     echo "Conexión establecida.<br />";
}else{
     echo "Conexión no se pudo establecer.<br />";
     die( print_r( sqlsrv_errors(), true));
}

$sql_exportar = "";

//Pedido Detalle
$sql = "SELECT * FROM Pedido_Detalle ORDER BY Id_Pedido ASC";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $row->UltimaVenta = $row->UltimaVenta->format('Y-m-d H:i:s');
    $sql_exportar .= "INSERT INTO `Pedido_Detalle`(`Id_Pedido`, `Id_Producto`, `UltimaVenta`, `Sugerencia`, `Pedido`, `ExistenciaTeorica`, `CostoUnitario`) VALUES ('$row->Id_Pedido', '$row->Id_Producto', '$row->UltimaVenta', '$row->Sugerencia', '$row->Pedido', '$row->ExistenciaTeorica', '$row->CostoUnitario');\n";
}
//Fin de Pedido Detalle


$myfile = fopen("exportaciones/bbdd.sql", "w") or die("Unable to open file!");
fwrite($myfile, $sql_exportar);
fclose($myfile);