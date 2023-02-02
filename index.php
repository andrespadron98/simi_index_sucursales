<?php

//schtasks /create /tn "InsertarDatosSimiData" /tr "C:\xampp\php\php-win.exe C:\xampp\htdocs\index.php" /sc minute /mo 1
//TAREA PARA WINDOWS

//INICIO DEL SCRIPT
$config = include('/config.php');

$idSucursal = $config['idSucursal'];
$url = $config['url'];
$carpetaConciliaciones    = $config['carpetaConciliaciones'];

$enlace = mysqli_connect($config['host'], $config['user'], $config['clave'], $config['bbdd']);
$connectionInfo = array( "Database"=> $config['bbddSposaa'], "UID"=> $config['userSposaa'], "PWD"=> $config['claveSposaa']);
$conn = sqlsrv_connect($config['hostSposaa'], $connectionInfo);

if( $conn ) {
     echo "Conexión establecida.<br />";
}else{
     echo "Conexión no se pudo establecer.<br />";
     die( print_r( sqlsrv_errors(), true));
}

//RESPALDO Operacion
$query = mysqli_query($enlace, "SELECT COUNT(*) as total FROM Operacion");
$total    = mysqli_fetch_array($query)['total'];

$sql = "SELECT COUNT(*) as total FROM Operacion";
$stmt = sqlsrv_query( $conn, $sql );
while ($row = sqlsrv_fetch_object( $stmt)) {
    $total_operacion = $row->total;
}

if($total != $total_operacion){
    mysqli_query($enlace, "DELETE FROM Operacion");

    $sql = "SELECT * FROM Operacion";
    $stmt = sqlsrv_query( $conn, $sql );

    while ($row = sqlsrv_fetch_object( $stmt)) {
        $row->FechaOperacion = $row->FechaOperacion->format('Y-m-d H:i:s');
        $row->FechaHora_Apertura = $row->FechaHora_Apertura->format('Y-m-d H:i:s');
        $row->FechaHora_Cierre = $row->FechaHora_Cierre->format('Y-m-d H:i:s');
        $row->FechaHora_Envio = $row->FechaHora_Envio->format('Y-m-d H:i:s');

        $sql = "INSERT INTO `Operacion`(`FechaOperacion`, `FechaHora_Apertura`, `Id_Usuario_Apertura`, `FechaHora_Cierre`, `Id_Usuario_Cierre`, `Id_Usuario_Vendedor`, `Id_Usuario_Cajero`, `Estatus`, `GranTotalApertura`, `GranTotalCierre`, `EstatusPV`, `EstatusCierre`, `EnvioCierre`, `FechaHora_Envio`) VALUES ('$row->FechaOperacion', '$row->FechaHora_Apertura', '$row->Id_Usuario_Apertura', '$row->FechaHora_Cierre', '$row->Id_Usuario_Cierre', '$row->Id_Usuario_Vendedor', '$row->Id_Usuario_Cajero', '$row->Estatus', '$row->GranTotalApertura', '$row->GranTotalCierre', '$row->EstatusPV', '$row->EstatusCierre', '$row->EnvioCierre', '$row->FechaHora_Envio')";
        mysqli_query($enlace, $sql);
    }
}

//FIN DE RESPALDO Operacion

//RESPALDO CONCILIACIONES
$query = mysqli_query($enlace, "SELECT comprobante, fecha FROM Configuracion_Venta_Diaria WHERE fecha >= '2022-12-01' AND monto > 0 AND comprobante IS NULL");
while ($row = mysqli_fetch_array($query)){
    $fecha = date("d-m-Y", strtotime($row['fecha']));
    $archivo = $carpetaConciliaciones.$fecha.".pdf";
    if(file_exists($archivo)){
        $conciliacion = curl_file_create($archivo);
        $post = array('fecha' => $row['fecha'], 'conciliacion'=> $conciliacion);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$url/cron/GuardarConciliacion/$idSucursal");
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $result = curl_exec($ch);
        curl_close ($ch);
    }
}
//FIN RESPALDO CONCILIACIONES

//RESPALDO  CIERRES TRANSBANK
$query = mysqli_query($enlace, "SELECT Id_CierreTransbank FROM Cierres_Transbank ORDER BY Id_CierreTransbank DESC LIMIT 1");
$ultima_modificacion    = mysqli_fetch_array($query)['Id_CierreTransbank'];

if(isset($ultima_modificacion)) {
	$sql = "SELECT * FROM Cierres_Transbank WHERE Id_CierreTransbank >= '$ultima_modificacion'";
}else{
	$sql = "SELECT * FROM Cierres_Transbank";
}
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $row->Fecha = $row->Fecha->format('Y-m-d H:i:s');

    $query = mysqli_query($enlace, "SELECT COUNT(*) as total FROM Cierres_Transbank WHERE Id_CierreTransbank = '$row->Id_CierreTransbank'");
    $total    = mysqli_fetch_array($query)['total'];


    if($total >= 1){
        $sql = "DELETE FROM Cierres_Transbank WHERE Id_CierreTransbank = '$row->Id_CierreTransbank'";
        mysqli_query($enlace, $sql);
    }

    $sql = "INSERT INTO `Cierres_Transbank`(`Id_CierreTransbank`, `Fecha`, `codigoRespuesta`, `glosaRespuesta`, `cantidadAnulacionesCompras`, `cantidadCompras`, `montoTransaccionesVenta`, `montoTransaccionesAnulacion`, `Id_usuario_Cierre`, `Id_registradora`) VALUES ('$row->Id_CierreTransbank', '$row->Fecha', '$row->codigoRespuesta', '$row->glosaRespuesta', '$row->cantidadAnulacionesCompras', '$row->cantidadCompras', '$row->montoTransaccionesVenta', '$row->montoTransaccionesAnulacion', '$row->Id_usuario_Cierre', '$row->Id_registradora')";
    mysqli_query($enlace, $sql);
}
//FIN DE RESPALDO CIERRES TRANSBANK


//RESPALDO Empaque_Productos
$query = mysqli_query($enlace, "SELECT FechaActualizo FROM Empaque_Productos ORDER BY FechaActualizo DESC LIMIT 1");
$ultima_modificacion    = mysqli_fetch_array($query)['FechaActualizo'];

if(isset($ultima_modificacion)) {
	$ultima_modificacion  = date('Y-m-d', strtotime($ultima_modificacion));
	$sql = "SELECT * FROM Empaque_Productos WHERE FechaActualizo >= '$ultima_modificacion'";
}else{
	$sql = "SELECT * FROM Empaque_Productos";
}


$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $row->FechaActualizo = $row->FechaActualizo->format('Y-m-d');
    $query = mysqli_query($enlace, "SELECT COUNT(*) as total FROM Empaque_Productos WHERE Producto = '$row->Producto'");
    $total    = mysqli_fetch_array($query)['total'];

    if($total >= 1){
        $sql = "UPDATE `Empaque_Productos` SET `Empaque`='$row->Empaque',`FechaActualizo`='$row->FechaActualizo' WHERE Producto = '$row->Producto'";
        mysqli_query($enlace, $sql);
    }else{
        $sql = "INSERT INTO `Empaque_Productos`(`Producto`, `Empaque`, `FechaActualizo`) VALUES ('$row->Producto','$row->Empaque','$row->FechaActualizo')";
        mysqli_query($enlace, $sql);
    }
}

//FIN DE RESPALDO Empaque_Productos

//RESPALDO Empaques_Pedidos
$query = mysqli_query($enlace, "SELECT Id_Pedido FROM Empaques_Pedidos ORDER BY Id_Pedido DESC LIMIT 1");
$ultimo_surtido    = mysqli_fetch_array($query)['Id_Pedido'];

$query = mysqli_query($enlace, "SELECT COUNT(*) AS total FROM Empaques_Pedidos WHERE Id_Pedido = ".$ultimo_surtido);
$total_ult_surtido   = mysqli_fetch_array($query)['total'];

$sql = "SELECT COUNT(*) as total FROM Empaques_Pedidos  WHERE Id_Pedido = ".$ultimo_surtido;
$stmt = sqlsrv_query( $conn, $sql );
while ($row = sqlsrv_fetch_object( $stmt)) {
    $total_ult_surtido_simi = $row->total;
}
if($total_ult_surtido != $total_ult_surtido_simi){
    mysqli_query($enlace, "DELETE FROM Empaques_Pedidos WHERE Id_Pedido = ".$ultimo_surtido);
    $ultimo_surtido = $ultimo_surtido-1;
}
if($ultimo_surtido){
    $sql = "SELECT * FROM Empaques_Pedidos WHERE Id_Pedido > ".$ultimo_surtido." ORDER BY Id_Pedido ASC";
    $stmt = sqlsrv_query( $conn, $sql );
}else{
    $sql = "SELECT * FROM Empaques_Pedidos ORDER BY Id_Pedido ASC";
    $stmt = sqlsrv_query( $conn, $sql );
}

while ($row = sqlsrv_fetch_object( $stmt)) {
    $sql = "INSERT INTO `Empaques_Pedidos`(`Id_Pedido`, `Producto`, `Unidades_Empaque`, `Empaque_Pedido`) VALUES ('$row->Id_Pedido', '$row->Producto', '$row->Unidades_Empaque', '$row->Empaque_Pedido')";
    mysqli_query($enlace, $sql);
}
//FIN DE RESPALDO Empaques_Pedidos

//RESPALDO Pedido
$query = mysqli_query($enlace, "SELECT Id_Pedido FROM Pedido ORDER BY Id_Pedido DESC LIMIT 1");
$ultima_modificacion    = mysqli_fetch_array($query)['Id_Pedido'];

if(isset($ultima_modificacion)) {
	$sql = "SELECT * FROM Pedido WHERE Id_Pedido >= '$ultima_modificacion'";
}else{
	$sql = "SELECT * FROM Pedido";
}
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $row->FechaOperacion = $row->FechaOperacion->format('Y-m-d H:i:s');
    $row->FechaHora_Captura = $row->FechaHora_Captura->format('Y-m-d H:i:s');
    $row->FechaPedido = $row->FechaPedido->format('Y-m-d H:i:s');

    $query = mysqli_query($enlace, "SELECT COUNT(*) as total FROM Pedido WHERE Id_Pedido = '$row->Id_Pedido'");
    $total    = mysqli_fetch_array($query)['total'];


    if($total >= 1){
        $sql = "DELETE FROM Pedido WHERE Id_Pedido = '$row->Id_Pedido'";
        mysqli_query($enlace, $sql);
    }

    $sql = "INSERT INTO `Pedido`(`Id_Pedido`, `FechaOperacion`, `Id_Usuario`, `Semanal`, `FechaHora_Captura`, `Estatus`, `Observacion`, `Dias`, `Adicionales`, `FechaPedido`, `IncluirMenudeo`, `Definitivo`, `FolioConfirmacion`, `FolioPedido`, `SincRef`, `Estimado`, `PedidoEmergente`, `Id_Almacen_Surtido`, `Id_Financiamiento`) VALUES ('$row->Id_Pedido', '$row->FechaOperacion', '$row->Id_Usuario', '$row->Semanal', '$row->FechaHora_Captura', '$row->Estatus', '$row->Observacion', '$row->Dias', '$row->Adicionales', '$row->FechaPedido', '$row->IncluirMenudeo', '$row->Definitivo', '$row->FolioConfirmacion', '$row->FolioPedido', '$row->SincRef', '$row->Estimado', '$row->PedidoEmergente', '$row->Id_Almacen_Surtido', '$row->Id_Financiamiento')";
    mysqli_query($enlace, $sql);
}
//FIN DE RESPALDO Pedido

//RESPALDO Pedido_Detalle
$query = mysqli_query($enlace, "SELECT Id_Pedido FROM Pedido_Detalle ORDER BY Id_Pedido DESC LIMIT 1");
$ultimo_surtido    = mysqli_fetch_array($query)['Id_Pedido'];

$query = mysqli_query($enlace, "SELECT COUNT(*) AS total FROM Pedido_Detalle WHERE Id_Pedido = ".$ultimo_surtido);
$total_ult_surtido   = mysqli_fetch_array($query)['total'];

$sql = "SELECT COUNT(*) as total FROM Pedido_Detalle  WHERE Id_Pedido = ".$ultimo_surtido;
$stmt = sqlsrv_query( $conn, $sql );
while ($row = sqlsrv_fetch_object( $stmt)) {
    $total_ult_surtido_simi = $row->total;
}
if($total_ult_surtido != $total_ult_surtido_simi){
    mysqli_query($enlace, "DELETE FROM Pedido_Detalle WHERE Id_Pedido = ".$ultimo_surtido);
    $ultimo_surtido = $ultimo_surtido-1;
}
if($ultimo_surtido){
    $sql = "SELECT * FROM Pedido_Detalle WHERE Id_Pedido > ".$ultimo_surtido." ORDER BY Id_Pedido ASC";
    $stmt = sqlsrv_query( $conn, $sql );
}else{
    $sql = "SELECT * FROM Pedido_Detalle ORDER BY Id_Pedido ASC";
    $stmt = sqlsrv_query( $conn, $sql );
}

while ($row = sqlsrv_fetch_object( $stmt)) {
    $row->UltimaVenta = $row->UltimaVenta->format('Y-m-d H:i:s');
    $sql = "INSERT INTO `Pedido_Detalle`(`Id_Pedido`, `Id_Producto`, `UltimaVenta`, `Sugerencia`, `Pedido`, `ExistenciaTeorica`, `CostoUnitario`) VALUES ('$row->Id_Pedido', '$row->Id_Producto', '$row->UltimaVenta', '$row->Sugerencia', '$row->Pedido', '$row->ExistenciaTeorica', '$row->CostoUnitario')";
    mysqli_query($enlace, $sql);
    
}
//FIN DE RESPALDO Pedido_Detalle

//RESPALDO Inventario_Otros
$query = mysqli_query($enlace, "SELECT Id_Registro FROM Inventario_Otros ORDER BY Id_Registro DESC LIMIT 1");
$ultima_modificacion    = mysqli_fetch_array($query)['Id_Registro'];

if(isset($ultima_modificacion)) {
	$sql = "SELECT * FROM Inventario_Otros WHERE Id_Registro >= '$ultima_modificacion'";
}else{
	$sql = "SELECT * FROM Inventario_Otros";
}
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $row->FechaOperacion = $row->FechaOperacion->format('Y-m-d H:i:s');
    $row->FechaHora_Captura = $row->FechaHora_Captura->format('Y-m-d H:i:s');

    $query = mysqli_query($enlace, "SELECT COUNT(*) as total FROM Inventario_Otros WHERE Id_Registro = '$row->Id_Registro'");
    $total    = mysqli_fetch_array($query)['total'];


    if($total >= 1){
        $sql = "DELETE FROM Inventario_Otros WHERE Id_Registro = '$row->Id_Registro'";
        mysqli_query($enlace, $sql);
        $sql = "INSERT INTO `Inventario_Otros`(`Id_Registro`, `Id_Movimiento`, `Documento`, `Referencia`, `Signo`, `FechaOperacion`, `FechaHora_Captura`, `Id_Usuario`, `Observacion`, `SincRef`, `Id_tipo`) VALUES ('$row->Id_Registro', '$row->Id_Movimiento', '$row->Documento', '$row->Referencia', '$row->Signo', '$row->FechaOperacion', '$row->FechaHora_Captura', '$row->Id_Usuario', '$row->Observacion', '$row->SincRef', '$row->id_tipo')";
        mysqli_query($enlace, $sql);
    }else{
        $sql = "INSERT INTO `Inventario_Otros`(`Id_Registro`, `Id_Movimiento`, `Documento`, `Referencia`, `Signo`, `FechaOperacion`, `FechaHora_Captura`, `Id_Usuario`, `Observacion`, `SincRef`, `Id_tipo`) VALUES ('$row->Id_Registro', '$row->Id_Movimiento', '$row->Documento', '$row->Referencia', '$row->Signo', '$row->FechaOperacion', '$row->FechaHora_Captura', '$row->Id_Usuario', '$row->Observacion', '$row->SincRef', '$row->id_tipo')";
        mysqli_query($enlace, $sql);
    }
}
//FIN DE RESPALDO Inventario_Otros


//RESPALDO Inventario_Otros_Detalle
$query = mysqli_query($enlace, "SELECT Id_Registro FROM Inventario_Otros_Detalle ORDER BY Id_Registro DESC LIMIT 1");
$ultimo_surtido    = mysqli_fetch_array($query)['Id_Registro'];

$query = mysqli_query($enlace, "SELECT COUNT(*) AS total FROM Inventario_Otros_Detalle WHERE Id_Registro = ".$ultimo_surtido);
$total_ult_surtido   = mysqli_fetch_array($query)['total'];

$sql = "SELECT COUNT(*) as total FROM Inventario_Otros_Detalle  WHERE Id_Registro = ".$ultimo_surtido;
$stmt = sqlsrv_query( $conn, $sql );
while ($row = sqlsrv_fetch_object( $stmt)) {
    $total_ult_surtido_simi = $row->total;
}
if($total_ult_surtido != $total_ult_surtido_simi){
    mysqli_query($enlace, "DELETE FROM Inventario_Otros_Detalle WHERE Id_Registro = ".$ultimo_surtido);
    $ultimo_surtido = $ultimo_surtido-1;
}
if($ultimo_surtido){
    $sql = "SELECT * FROM Inventario_Otros_Detalle WHERE Id_Registro > ".$ultimo_surtido." ORDER BY Id_Registro ASC";
    $stmt = sqlsrv_query( $conn, $sql );
}else{
    $sql = "SELECT * FROM Inventario_Otros_Detalle ORDER BY Id_Registro ASC";
    $stmt = sqlsrv_query( $conn, $sql );
}

while ($row = sqlsrv_fetch_object( $stmt)) {
    $sql = "INSERT INTO `Inventario_Otros_Detalle`(`Id_Registro`, `Id_Producto`, `Cantidad`) VALUES ('$row->Id_Registro', '$row->Id_Producto', '$row->Cantidad')";
    mysqli_query($enlace, $sql);
    
}
//FIN DE RESPALDO Inventario_Otros_Detalle


//RESPALDO Inventario_Otros_Tipo
$query = mysqli_query($enlace, "SELECT Id_tipo FROM Inventario_Otros_Tipo ORDER BY Id_tipo DESC LIMIT 1");
$ultima_modificacion    = mysqli_fetch_array($query)['Id_tipo'];

if(isset($ultima_modificacion)) {
	$sql = "SELECT * FROM Inventario_Otros_Tipo WHERE Id_tipo >= '$ultima_modificacion'";
}else{
	$sql = "SELECT * FROM Inventario_Otros_Tipo";
}
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $query = mysqli_query($enlace, "SELECT COUNT(*) as total FROM Inventario_Otros_Tipo WHERE Id_tipo = '$row->Id_tipo'");
    $total    = mysqli_fetch_array($query)['total'];

    if($total >= 1){
        $sql = "DELETE FROM Inventario_Otros_Tipo WHERE Id_tipo = '$row->Id_tipo'";
        mysqli_query($enlace, $sql);
        $sql = "INSERT INTO `Inventario_Otros_Tipo`(`Id_tipo`, `Nombre`, `Signo`) VALUES ('$row->Id_tipo', '$row->Nombre', '$row->Signo')";
        mysqli_query($enlace, $sql);
    }else{
        $sql = "INSERT INTO `Inventario_Otros_Tipo`(`Id_tipo`, `Nombre`, `Signo`) VALUES ('$row->Id_tipo', '$row->Nombre', '$row->Signo')";
        mysqli_query($enlace, $sql);
    }
}
//FIN DE RESPALDO Inventario_Otros_Tipo


//RESPALDO Inventario_Traspaso
$query = mysqli_query($enlace, "SELECT COUNT(*) as total FROM Inventario_Traspaso");
$total    = mysqli_fetch_array($query)['total'];

$sql = "SELECT COUNT(*) as total FROM Inventario_Traspaso";
$stmt = sqlsrv_query( $conn, $sql );
while ($row = sqlsrv_fetch_object( $stmt)) {
    $total_traspaso = $row->total;
}

if($total != $total_traspaso){
    mysqli_query($enlace, "DELETE FROM Inventario_Traspaso");
    mysqli_query($enlace, "DELETE FROM Inventario_Traspaso_Detalle");

    $sql = "SELECT * FROM Inventario_Traspaso";
    $stmt = sqlsrv_query( $conn, $sql );

    while ($row = sqlsrv_fetch_object( $stmt)) {
        $row->FechaOperacion = $row->FechaOperacion->format('Y-m-d H:i:s');
        $row->FechaHora_Captura = $row->FechaHora_Captura->format('Y-m-d H:i:s');
        $row->FechaHora_Autorizacion = $row->FechaHora_Autorizacion->format('Y-m-d H:i:s');

        $sql = "INSERT INTO `Inventario_Traspaso`(`Id_Farmacia_Entrega`, `Id_Traspaso`, `Id_Farmacia_Pedido`, `Id_Concepto`, `Id_Movimiento`, `Documento`, `Referencia`, `FechaOperacion`, `FechaHora_Captura`, `FechaHora_Autorizacion`, `Id_Usuario_Captura`, `Id_Usuario_Autoriza`, `Estatus`, `SincRef`, `Total`) VALUES ('$row->Id_Farmacia_Entrega', '$row->Id_Traspaso', '$row->Id_Farmacia_Pedido', '$row->Id_Concepto', '$row->Id_Movimiento', '$row->Documento', '$row->Referencia', '$row->FechaOperacion', '$row->FechaHora_Captura', '$row->FechaHora_Autorizacion', '$row->Id_Usuario_Captura', '$row->Id_Usuario_Autoriza', '$row->Estatus', '$row->SincRef', '$row->Total')";
        mysqli_query($enlace, $sql);
    }
    
    $sql = "SELECT * FROM Inventario_Traspaso_Detalle ORDER BY Id_Traspaso ASC";
    $stmt = sqlsrv_query( $conn, $sql );
    
    while ($row = sqlsrv_fetch_object( $stmt)) {
        $sql = "INSERT INTO `Inventario_Traspaso_Detalle`(`Id_Farmacia_Entrega`, `Id_Traspaso`, `Id_Producto`, `Solicitud`, `Autorizado`, `Precio`, `Importe`) VALUES ('$row->Id_Farmacia_Entrega', '$row->Id_Traspaso', '$row->Id_Producto', '$row->Solicitud', '$row->Autorizado', '$row->Precio', '$row->Importe')";
        mysqli_query($enlace, $sql);
        
    }
}

//FIN DE RESPALDO Inventario_Traspaso


//RESPALDO TABLA VENTA
$query = mysqli_query($enlace, "SELECT Id_Venta FROM Venta ORDER BY Id_Venta DESC LIMIT 1");
$ultima_venta    = mysqli_fetch_array($query)['Id_Venta'];

if($ultima_venta){

    $query = mysqli_query($enlace, "SELECT COUNT(*) AS total FROM Venta WHERE Id_Venta = ".$ultima_venta);
    $total_ult_venta    = mysqli_fetch_array($query)['total'];

    $sql = "SELECT COUNT(*) as total FROM Venta  WHERE Id_Venta = ".$ultima_venta;
    $stmt = sqlsrv_query( $conn, $sql );

    while ($row = sqlsrv_fetch_object( $stmt)) {
        $total_ult_venta_simi = $row->total;
    }
    if($total_ult_venta != $total_ult_venta_simi){
        mysqli_query($enlace, "DELETE FROM Venta WHERE Id_Venta = ".$ultima_venta);
        $ultima_venta = $ultima_venta-1;
    }

    $sql = "SELECT * FROM Venta WHERE Id_Venta >= '".$ultima_venta."' ORDER BY Id_Venta ASC";
    $stmt = sqlsrv_query( $conn, $sql );

}else{
    $sql = "SELECT * FROM Venta  ORDER BY Id_Venta ASC";
    $stmt = sqlsrv_query( $conn, $sql );
}


while ($row = sqlsrv_fetch_object( $stmt)) {
    $row->FechaHoraCobro = $row->FechaHoraCobro->format('Y-m-d H:i:s');
    $row->FechaHoraVenta = $row->FechaHoraVenta->format('Y-m-d H:i:s');
    $row->FechaHoraCancelacion = $row->FechaHoraCancelacion->format('Y-m-d H:i:s');
    $row->FechaOperacion = $row->FechaOperacion->format('Y-m-d H:i:s');

    $sql = "INSERT INTO `Venta`(`Id_Venta`, `Id_Venta_Local`, `Id_Venta_Consecutivo`, `Id_Movimiento`, `Id_Venta_Registradora`, `Id_Registradora_Venta`, `Id_Registradora_Cobro`, `Id_Usuario_Venta`, `Id_Usuario_Cobro`, `Id_Usuario_Cancelacion`, `FechaHoraVenta`, `FechaHoraCobro`, `FechaHoraCancelacion`, `FechaOperacion`, `TipoVenta`, `TipoOperacion`, `Id_Venta_Referencia`, `Receta`, `AntesTotal`, `Estatus`, `Historico`, `Sincroniza`, `Id_Cliente`, `PuntosIniciales`, `PuntosFinales`, `PuntosAcumulados`, `Restriccion`) VALUES ('$row->Id_Venta','$row->Id_Venta_Local','$row->Id_Venta_Consecutivo','$row->Id_Movimiento','$row->Id_Venta_Registradora','$row->Id_Registradora_Venta','$row->Id_Registradora_Cobro','$row->Id_Usuario_Venta','$row->Id_Usuario_Cobro','$row->Id_Usuario_Cancelacion','$row->FechaHoraVenta','$row->FechaHoraCobro','$row->FechaHoraCancelacion','$row->FechaOperacion','$row->TipoVenta','$row->TipoOperacion','$row->Id_Venta_Referencia','$row->Receta','$row->AntesTotal','$row->Estatus','$row->Historico','$row->Sincroniza','$row->Id_Cliente','$row->PuntosIniciales','$row->PuntosFinales','$row->PuntosAcumulados','$row->Restriccion')";
    mysqli_query($enlace, $sql);
}
//FIN DE RESPALDO TABLA VENTA


//RESPALDO TABLA VENTA_PRODUCTO

$query = mysqli_query($enlace, "SELECT Id_Venta FROM Venta_Producto ORDER BY Id_Venta DESC LIMIT 1");
$ultima_venta    = mysqli_fetch_array($query)['Id_Venta'];

if(isset($ultima_venta)){
	$query = mysqli_query($enlace, "SELECT COUNT(*) AS total FROM Venta_Producto WHERE Id_Venta = ".$ultima_venta);
	$total_ult_venta    = mysqli_fetch_array($query)['total'];

	$sql = "SELECT COUNT(*) as total FROM Venta_Producto  WHERE Id_Venta = ".$ultima_venta;
	$stmt = sqlsrv_query( $conn, $sql );
	while ($row = sqlsrv_fetch_object( $stmt)) {
	    $total_ult_venta_simi = $row->total;
	}
	if($total_ult_venta != $total_ult_venta_simi){
	    mysqli_query($enlace, "DELETE FROM Venta_Producto WHERE Id_Venta = ".$ultima_venta);
	    $ultima_venta = $ultima_venta-1;
	}

	$sql = "SELECT * FROM Venta_Producto WHERE Id_Venta > ".$ultima_venta." ORDER BY Id_Venta ASC";
}else{
	$sql = "SELECT * FROM Venta_Producto  ORDER BY Id_Venta ASC";
}

$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $sql = "INSERT INTO `Venta_Producto`(`Id_Venta`, `Id_Venta_Local`, `Id_Venta_Consecutivo`, `Id_Producto`, `Cantidad`, `Precio`, `IVA`, `Descuento`, `DescuentoPorciento`, `Puntos`, `IVA_Porciento`, `IVA_Importe`, `Posicion`, `Premio`) VALUES ('$row->Id_Venta','$row->Id_Venta_Local','$row->Id_Venta_Consecutivo','$row->Id_Producto','$row->Cantidad','$row->Precio','$row->IVA','$row->Descuento','$row->DescuentoPorciento','$row->Puntos','$row->IVA_Porciento','$row->IVA_Importe','$row->Posicion','$row->Premio')";
    mysqli_query($enlace, $sql);
}
//FIN DE RESPALDO TABLA VENTA_PRODUCTO


//RESPALDO TABLA VENTA_PAGO
$query = mysqli_query($enlace, "SELECT Id_Venta FROM Venta_Pago ORDER BY Id_Venta DESC LIMIT 1");
$ultima_venta    = mysqli_fetch_array($query)['Id_Venta'];

if(isset($ultima_venta)){
	$query = mysqli_query($enlace, "SELECT COUNT(*) AS total FROM Venta_Pago WHERE Id_Venta = ".$ultima_venta);
	$total_ult_venta    = mysqli_fetch_array($query)['total'];

	$sql = "SELECT COUNT(*) as total FROM Venta_Pago  WHERE Id_Venta = ".$ultima_venta;
	$stmt = sqlsrv_query( $conn, $sql );
	while ($row = sqlsrv_fetch_object( $stmt)) {
	    $total_ult_venta_simi = $row->total;
	}
	if($total_ult_venta != $total_ult_venta_simi){
	    mysqli_query($enlace, "DELETE FROM Venta_Pago WHERE Id_Venta = ".$ultima_venta);
	    $ultima_venta = $ultima_venta-1;
	}

	$sql = "SELECT * FROM Venta_Pago WHERE Id_Venta > ".$ultima_venta." ORDER BY Id_Venta ASC";
}else{
	$sql = "SELECT * FROM Venta_Pago ORDER BY Id_Venta ASC";
}
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $sql = "INSERT INTO `Venta_Pago`(`Id_Venta`, `Id_Venta_Local`, `Id_Venta_Consecutivo`, `Id_FormaPago`, `Importe`, `TipoCambio`) VALUES ('$row->Id_Venta','$row->Id_Venta_Local','$row->Id_Venta_Consecutivo','$row->Id_FormaPago','$row->Importe','$row->TipoCambio')";
    mysqli_query($enlace, $sql);
}
//FIN DE RESPALDO TABLA VENTA_PAGO

//RESPALDO TABLA FORMA PAGO
/*
$sql = "SELECT * FROM FormaPago";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $sql = "INSERT INTO `FormaPago`(`Id_FormaPago`, `Nombre`, `NombreCorto`, `TipoCambio`, `PagoCompleto`, `Nacional`, `Tarjeta`, `TarjetaCredito`, `Venta`, `Cajon`, `Cambio`, `Descuento`, `Devolucion`, `Ticket`, `Voucher`, `Cobranza`, `Excedente`, `Retiro`, `Fondo`, `Prepago`, `TipoVenta`, `TipoCliente`, `Restriccion`, `LeyendaTicket`, `FondoCambio`, `Vale`, `Arqueo`, `Id_TipoDeposito`, `Id_FormaPago_Conciliacion`, `EstatusRegistro`, `AliasFormaPago`) VALUES ('$row->Id_FormaPago','$row->Nombre','$row->NombreCorto','$row->TipoCambio','$row->PagoCompleto','$row->Nacional','$row->Tarjeta','$row->TarjetaCredito','$row->Venta','$row->Cajon','$row->Cambio','$row->Descuento','$row->Devolucion','$row->Ticket','$row->Voucher','$row->Cobranza','$row->Excedente','$row->Retiro','$row->Fondo','$row->Prepago','$row->TipoVenta','$row->TipoCliente','$row->Restriccion','$row->LeyendaTicket','$row->FondoCambio','$row->Vale','$row->Arqueo','$row->Id_TipoDeposito','$row->Id_FormaPago_Conciliacion','$row->EstatusRegistro','$row->AliasFormaPago')";
    mysqli_query($enlace, $sql);
}

//FIN DE RESPALDO TABLA FORMA PAGO
*/
//RESPALDO TABLA Usuario

$sql = "SELECT * FROM Usuario";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {

	$query = mysqli_query($enlace, "SELECT FechaUltimoCambio FROM Usuario WHERE Id_Usuario = '$row->Id_Usuario'");
	$ult_cambio    = mysqli_fetch_array($query)['FechaUltimoCambio'];

	if($ult_cambio){
		if($row->FechaUltimoCambio > $ult_cambio){
	        $sql = "UPDATE `Usuario` SET `Id_Usuario`='$row->Id_Usuario',`Nombre`='$row->Nombre',`Id_Idioma`='$row->Id_Idioma',`LlaveAcceso`='$row->LlaveAcceso',`FechaUltimoCambio`='$row->FechaUltimoCambio',`CambiarLlaveAcceso`='$row->CambiarLlaveAcceso',`Temporal`='$row->Temporal',`EstatusRegistro`='$row->EstatusRegistro' WHERE Id_Usuario = '$row->Id_Usuario'";
	        mysqli_query($enlace, $sql);
		}
	}else{
    	$sql = "INSERT INTO `Usuario`(`Id_Usuario`, `Nombre`, `Id_Idioma`, `LlaveAcceso`, `FechaUltimoCambio`, `CambiarLlaveAcceso`, `Temporal`, `EstatusRegistro`) VALUES ('$row->Id_Usuario','$row->Nombre','$row->Id_Idioma','$row->LlaveAcceso','$row->FechaUltimoCambio','$row->CambiarLlaveAcceso','$row->Temporal','$row->EstatusRegistro')";
    	mysqli_query($enlace, $sql);

	}
}

//FIN DE RESPALDO TABLA Usuario


//RESPALDO INVENTARIO

$query = mysqli_query($enlace, "SELECT Fecha_Modificacion FROM Inventario ORDER BY Fecha_Modificacion DESC LIMIT 1");
$ultima_modificacion    = mysqli_fetch_array($query)['Fecha_Modificacion'];

if(isset($ultima_modificacion)) {
	$ultima_modificacion  = date('d-m-Y H:m:s', strtotime($ultima_modificacion));
	$sql = "SELECT * FROM Inventario WHERE Fecha_Modificacion >= '$ultima_modificacion'";
}else{
	$sql = "SELECT * FROM Inventario";
}


$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $row->Fecha_Modificacion = $row->Fecha_Modificacion->format('Y-m-d H:i:s');
    $query = mysqli_query($enlace, "SELECT COUNT(*) as total FROM Inventario WHERE Id_Producto = '$row->Id_Producto'");
    $total    = mysqli_fetch_array($query)['total'];

    if($total >= 1){
        $sql = "UPDATE `Inventario` SET `Existencia`='$row->Existencia',`NoDisponible`='$row->NoDisponible',`Fecha_Modificacion`='$row->Fecha_Modificacion' WHERE Id_Producto = '$row->Id_Producto'";
        mysqli_query($enlace, $sql);
    }else{
        $sql = "INSERT INTO `Inventario`(`Id_Producto`, `Existencia`, `NoDisponible`, `Fecha_Modificacion`) VALUES ('$row->Id_Producto','$row->Existencia','$row->NoDisponible','$row->Fecha_Modificacion')";
        mysqli_query($enlace, $sql);
    }
}

//FIN DE RESPALDO INVENTARIO

//RESPALDO INVENTARIO_SURTIDO

$query = mysqli_query($enlace, "SELECT FechaOperacion_Descarga FROM Inventario_Surtido ORDER BY FechaOperacion_Descarga DESC LIMIT 1");
$ultima_modificacion    = mysqli_fetch_array($query)['FechaOperacion_Descarga'];
$ultima_modificacion  = date('d-m-Y H:m:s', strtotime($ultima_modificacion));

if(isset($ultima_modificacion)) {
	$sql = "SELECT * FROM Inventario_Surtido WHERE FechaOperacion_Descarga >= '$ultima_modificacion'";
}else{
	$sql = "SELECT * FROM Inventario_Surtido";
}
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $row->FechaOperacion = $row->FechaOperacion->format('Y-m-d H:i:s');
    $row->FechaHora_Captura = $row->FechaHora_Captura->format('Y-m-d H:i:s');
    $row->FechaOperacion_Descarga = $row->FechaOperacion_Descarga->format('Y-m-d H:i:s');
    $row->Fecha_Facturacion = $row->Fecha_Facturacion->format('Y-m-d H:i:s');
    $row->FechaVencimiento = $row->FechaVencimiento->format('Y-m-d H:i:s');

    $query = mysqli_query($enlace, "SELECT COUNT(*) as total FROM Inventario_Surtido WHERE Id_Surtido = '$row->Id_Surtido'");
    $total    = mysqli_fetch_array($query)['total'];

    if($total >= 1){
        $sql = "DELETE FROM Inventario_Surtido WHERE Id_Surtido = '$row->Id_Surtido'";
        mysqli_query($enlace, $sql);
        $sql = "INSERT INTO `Inventario_Surtido`(`Id_Surtido`, `Id_Surtido_Local`, `Id_Movimiento`, `Documento`, `Referencia`, `FechaOperacion`, `FechaHora_Captura`, `FechaOperacion_Descarga`, `Fecha_Facturacion`, `Id_Usuario`, `Factura`, `Factura_Fiscal`, `SurtidoElectronico`, `Id_Proveedor`, `Id_FarmaciaSurtido`, `Estatus`, `Observacion`, `Subtotal`, `Descuento`, `Impuesto`, `Total`, `Respaldo`, `Conteo`, `SincRef`, `FechaVencimiento`, `Factura_Fiscal_Ref`, `Signo`) VALUES ('$row->Id_Surtido', '$row->Id_Surtido_Local', '$row->Id_Movimiento', '$row->Documento', '$row->Referencia', '$row->FechaOperacion', '$row->FechaHora_Captura', '$row->FechaOperacion_Descarga', '$row->Fecha_Facturacion', '$row->Id_Usuario', '$row->Factura', '$row->Factura_Fiscal', '$row->SurtidoElectronico', '$row->Id_Proveedor', '$row->Id_FarmaciaSurtido', '$row->Estatus', '$row->Observacion', '$row->Subtotal', '$row->Descuento', '$row->Impuesto', '$row->Total', '$row->Respaldo', '$row->Conteo', '$row->SincRef', '$row->FechaVencimiento', '$row->Factura_Fiscal_Ref', '$row->Signo')";
        mysqli_query($enlace, $sql);
    }else{
        $sql = "INSERT INTO `Inventario_Surtido`(`Id_Surtido`, `Id_Surtido_Local`, `Id_Movimiento`, `Documento`, `Referencia`, `FechaOperacion`, `FechaHora_Captura`, `FechaOperacion_Descarga`, `Fecha_Facturacion`, `Id_Usuario`, `Factura`, `Factura_Fiscal`, `SurtidoElectronico`, `Id_Proveedor`, `Id_FarmaciaSurtido`, `Estatus`, `Observacion`, `Subtotal`, `Descuento`, `Impuesto`, `Total`, `Respaldo`, `Conteo`, `SincRef`, `FechaVencimiento`, `Factura_Fiscal_Ref`, `Signo`) VALUES ('$row->Id_Surtido', '$row->Id_Surtido_Local', '$row->Id_Movimiento', '$row->Documento', '$row->Referencia', '$row->FechaOperacion', '$row->FechaHora_Captura', '$row->FechaOperacion_Descarga', '$row->Fecha_Facturacion', '$row->Id_Usuario', '$row->Factura', '$row->Factura_Fiscal', '$row->SurtidoElectronico', '$row->Id_Proveedor', '$row->Id_FarmaciaSurtido', '$row->Estatus', '$row->Observacion', '$row->Subtotal', '$row->Descuento', '$row->Impuesto', '$row->Total', '$row->Respaldo', '$row->Conteo', '$row->SincRef', '$row->FechaVencimiento', '$row->Factura_Fiscal_Ref', '$row->Signo')";
        mysqli_query($enlace, $sql);
    }
}
//FIN DE RESPALDO INVENTARIO_SURTIDO

//RESPALDO INVENTARIO_SURTIDO_DETALLE

$query = mysqli_query($enlace, "SELECT Id_Surtido FROM Inventario_Surtido_Detalle ORDER BY Id_Surtido DESC LIMIT 1");
$ultimo_surtido    = mysqli_fetch_array($query)['Id_Surtido'];

$query = mysqli_query($enlace, "SELECT COUNT(*) AS total FROM Inventario_Surtido_Detalle WHERE Id_Surtido = ".$ultimo_surtido);
$total_ult_surtido   = mysqli_fetch_array($query)['total'];

$sql = "SELECT COUNT(*) as total FROM Inventario_Surtido_Detalle  WHERE Id_Surtido = ".$ultimo_surtido;
$stmt = sqlsrv_query( $conn, $sql );
while ($row = sqlsrv_fetch_object( $stmt)) {
    $total_ult_surtido_simi = $row->total;
}
if($total_ult_surtido != $total_ult_surtido_simi){
    mysqli_query($enlace, "DELETE FROM Inventario_Surtido_Detalle WHERE Id_Surtido = ".$ultimo_surtido);
    $ultimo_surtido = $ultimo_surtido-1;
}
if($ultimo_surtido){
    $sql = "SELECT * FROM Inventario_Surtido_Detalle WHERE Id_Surtido > ".$ultimo_surtido." ORDER BY Id_Surtido ASC";
    $stmt = sqlsrv_query( $conn, $sql );
}else{
    $sql = "SELECT * FROM Inventario_Surtido_Detalle ORDER BY Id_Surtido ASC";
    $stmt = sqlsrv_query( $conn, $sql );
}

while ($row = sqlsrv_fetch_object( $stmt)) {
    $sql = "INSERT INTO `Inventario_Surtido_Detalle`(`Id_Surtido`, `Id_Surtido_Local`, `Id_Producto`, `Remision`, `Conteo1`, `Conteo2`, `MalEstado`, `CostoUnitario`, `Descuento`, `Impuesto`, `Total`, `SubTotal`, `IVAPorciento`, `DescuentoPorciento`) VALUES ('$row->Id_Surtido', '$row->Id_Surtido_Local', '$row->Id_Producto', '$row->Remision', '$row->Conteo1', '$row->Conteo2', '$row->MalEstado', '$row->CostoUnitario', '$row->Descuento', '$row->Impuesto', '$row->Total', '$row->SubTotal', '$row->IVAPorciento', '$row->DescuentoPorciento')";
    mysqli_query($enlace, $sql);
    
}
//FIN DE RESPALDO INVENTARIO_SURTIDO_DETALLE

//RESPALDO PRODUCTO
$sql = "SELECT * FROM Producto";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $row->FechaInclusion = $row->FechaInclusion->format('Y-m-d H:i:s');
    $query = mysqli_query($enlace, "SELECT COUNT(*) as total FROM Producto WHERE Id_Producto = '$row->Id_Producto'");
    if(mysqli_fetch_array($query)['total'] == 0){
        $sql = "INSERT INTO `Producto`(`Id_Producto`, `Id_Nivel1`, `Id_Nivel2`, `Id_Nivel3`, `Id_Articulo`, `Id_Presentacion`, `Nombre`, `MarcaEconomica`, `PrecioCompra`, `Precio`, `UltimoCosto`, `IVA`, `Inventario`, `InventarioDiario`, `Combo`, `OTC`, `Venta`, `Servicio`, `Premio`, `EstructuraNegocio`, `AplicaCaducidad`, `AplicaDescuento`, `ProductoBasico`, `AsignaPuntos`, `PrecioPuntos`, `ProductoGondola`, `EstatusRegistro`, `Controlado`, `Descripcion_Corta`, `FueradeCatalogo`, `NoPonderado`, `CantidadPresentacion`, `FechaInclusion`, `escombo`) VALUES ('$row->Id_Producto', '$row->Id_Nivel1', '$row->Id_Nivel2', '$row->Id_Nivel3', '$row->Id_Articulo', '$row->Id_Presentacion', '$row->Nombre', '$row->MarcaEconomica', '$row->PrecioCompra', '$row->Precio', '$row->UltimoCosto', '$row->IVA', '$row->Inventario', '$row->InventarioDiario', '$row->Combo', '$row->OTC', '$row->Venta', '$row->Servicio', '$row->Premio', '$row->EstructuraNegocio', '$row->AplicaCaducidad', '$row->AplicaDescuento', '$row->ProductoBasico', '$row->AsignaPuntos', '$row->PrecioPuntos', '$row->ProductoGondola', '$row->EstatusRegistro', '$row->Controlado', '$row->Descripcion_Corta', '$row->FueradeCatalogo', '$row->NoPonderado', '$row->CantidadPresentacion', '$row->FechaInclusion', '$row->escombo')";
        mysqli_query($enlace, $sql);
    }else{
        $query = mysqli_query($enlace, "SELECT Precio, FueradeCatalogo, EstatusRegistro FROM Producto WHERE Id_Producto = '$row->Id_Producto'");
        if($row->Precio != mysqli_fetch_array($query)['Precio'] || $row->FueradeCatalogo != mysqli_fetch_array($query)['FueradeCatalogo'] || $row->EstatusRegistro != mysqli_fetch_array($query)['EstatusRegistro']){
            $sql = "UPDATE `Producto` SET `Id_Producto` = '$row->Id_Producto', `Id_Nivel1` = '$row->Id_Nivel1', `Id_Nivel2` = '$row->Id_Nivel2', `Id_Nivel3` = '$row->Id_Nivel3', `Id_Articulo` = '$row->Id_Articulo', `Id_Presentacion` = '$row->Id_Presentacion', `Nombre` = '$row->Nombre' , `MarcaEconomica` = '$row->MarcaEconomica' , `PrecioCompra` = '$row->PrecioCompra' , `Precio` = '$row->Precio' , `UltimoCosto` = '$row->UltimoCosto' , `IVA` = '$row->IVA' , `Inventario` = '$row->Inventario' , `InventarioDiario` = '$row->InventarioDiario' , `Combo` = '$row->Combo' , `OTC` = '$row->OTC' , `Venta` = '$row->Venta' , `Servicio` = '$row->Servicio', `Premio` = '$row->Premio' , `EstructuraNegocio` = '$row->EstructuraNegocio' , `AplicaCaducidad` = '$row->AplicaCaducidad' , `AplicaDescuento` = '$row->AplicaDescuento' , `ProductoBasico` = '$row->ProductoBasico' , `AsignaPuntos` = '$row->AsignaPuntos' , `PrecioPuntos` = '$row->PrecioPuntos' , `ProductoGondola` = '$row->ProductoGondola' , `EstatusRegistro` = '$row->EstatusRegistro' , `Controlado` = '$row->Controlado' , `Descripcion_Corta` = '$row->Descripcion_Corta' , `FueradeCatalogo` = '$row->FueradeCatalogo' , `NoPonderado` = '$row->NoPonderado' , `CantidadPresentacion` = '$row->CantidadPresentacion' , `FechaInclusion` = '$row->FechaInclusion' , `escombo` = '$row->escombo'  WHERE `Id_Producto` = '$row->Id_Producto'";
            mysqli_query($enlace, $sql);
        }
    }
}

//FIN DE RESPALDO PRODUCTO


//RESPALDO PRODUCTO_CODIGOBARRA

$query = mysqli_query($enlace, "SELECT COUNT(*) AS total FROM Producto_CodigoBarra");
$total_codigos_black   = mysqli_fetch_array($query)['total'];

$sql = "SELECT COUNT(*) AS total FROM Producto_CodigoBarra";
$stmt = sqlsrv_query( $conn, $sql );
while ($row = sqlsrv_fetch_object( $stmt)) {
    $total_codigos_spos = $row->total;
}
if($total_codigos_spos != $total_codigos_black){
    mysqli_query($enlace, "DELETE FROM Producto_CodigoBarra");

    $sql = "SELECT * FROM Producto_CodigoBarra";
    $stmt = sqlsrv_query( $conn, $sql );
    while ($row = sqlsrv_fetch_object( $stmt)) {
        $sql = "INSERT INTO `Producto_CodigoBarra`(`Id_Producto`, `Codigo`) VALUES ('$row->Id_Producto', '$row->Codigo')";
        mysqli_query($enlace, $sql);
    }
    
}
//FIN DE RESPALDO PRODUCTO_CODIGOBARRA


//RESPALDO TABLA VENTA_TARJETA
$query = mysqli_query($enlace, "SELECT Id_Venta FROM Venta_Tarjeta ORDER BY Id_Venta DESC LIMIT 1");
$ultima_venta    = mysqli_fetch_array($query)['Id_Venta'];

if($ultima_venta){
    $query = mysqli_query($enlace, "SELECT COUNT(*) AS total FROM Venta_Tarjeta WHERE Id_Venta = ".$ultima_venta);
    $total_ult_venta    = mysqli_fetch_array($query)['total'];

    $sql = "SELECT COUNT(*) as total FROM Venta_Tarjeta  WHERE Id_Venta = ".$ultima_venta;
    $stmt = sqlsrv_query( $conn, $sql );
    while ($row = sqlsrv_fetch_object( $stmt)) {
        $total_ult_venta_simi = $row->total;
    }
    if($total_ult_venta != $total_ult_venta_simi){
        mysqli_query($enlace, "DELETE FROM Venta_Tarjeta WHERE Id_Venta = ".$ultima_venta);
        $ultima_venta = $ultima_venta-1;
    }

    $sql = "SELECT * FROM Venta_Tarjeta WHERE Id_Venta > ".$ultima_venta." ORDER BY Id_Venta ASC";
    $stmt = sqlsrv_query( $conn, $sql );
}else{
    $sql = "SELECT * FROM Venta_Tarjeta ORDER BY Id_Venta ASC";
    $stmt = sqlsrv_query( $conn, $sql );
}

while ($row = sqlsrv_fetch_object( $stmt)) {
    $sql = "INSERT INTO `Venta_Tarjeta`(`Id_Venta`, `Id_Venta_Local`, `Id_Venta_Consecutivo`, `Autorizacion`, `Id_FormaPago`, `Tarjeta`, `Importe`, `TipoCambio`, `NumeroCuenta`, `numeroTransaccion`, `referenciaFinanciera`, `idPromocionTarjetaBancaria`) VALUES ('$row->Id_Venta','$row->Id_Venta_Local','$row->Id_Venta_Consecutivo','$row->Autorizacion','$row->Id_FormaPago','$row->Tarjeta','$row->Importe','$row->TipoCambio','$row->NumeroCuenta','$row->numeroTransaccion','$row->referenciaFinanciera','$row->idPromocionTarjetaBancaria')";
    mysqli_query($enlace, $sql);
}
//FIN DE RESPALDO TABLA VENTA_TARJETA

echo "Consulta Ejecutada";


sqlsrv_close($conn);
mysqli_close($enlace);

?>

