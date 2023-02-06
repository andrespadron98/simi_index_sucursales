<?php
ini_set('memory_limit', '-1');

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

//Operacion
$sql = "SELECT * FROM Operacion";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $row->FechaOperacion = $row->FechaOperacion->format('Y-m-d H:i:s');
    $row->FechaHora_Apertura = $row->FechaHora_Apertura->format('Y-m-d H:i:s');
    $row->FechaHora_Cierre = $row->FechaHora_Cierre->format('Y-m-d H:i:s');
    $row->FechaHora_Envio = $row->FechaHora_Envio->format('Y-m-d H:i:s');

    $sql_exportar .= "INSERT INTO `Operacion`(`FechaOperacion`, `FechaHora_Apertura`, `Id_Usuario_Apertura`, `FechaHora_Cierre`, `Id_Usuario_Cierre`, `Id_Usuario_Vendedor`, `Id_Usuario_Cajero`, `Estatus`, `GranTotalApertura`, `GranTotalCierre`, `EstatusPV`, `EstatusCierre`, `EnvioCierre`, `FechaHora_Envio`) VALUES ('$row->FechaOperacion', '$row->FechaHora_Apertura', '$row->Id_Usuario_Apertura', '$row->FechaHora_Cierre', '$row->Id_Usuario_Cierre', '$row->Id_Usuario_Vendedor', '$row->Id_Usuario_Cajero', '$row->Estatus', '$row->GranTotalApertura', '$row->GranTotalCierre', '$row->EstatusPV', '$row->EstatusCierre', '$row->EnvioCierre', '$row->FechaHora_Envio');\n";
}
//Fin de Operacion

//Cierres TBK
$sql = "SELECT * FROM Cierres_Transbank";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $row->Fecha = $row->Fecha->format('Y-m-d H:i:s');

    $sql_exportar .= "INSERT INTO `Cierres_Transbank`(`Id_CierreTransbank`, `Fecha`, `codigoRespuesta`, `glosaRespuesta`, `cantidadAnulacionesCompras`, `cantidadCompras`, `montoTransaccionesVenta`, `montoTransaccionesAnulacion`, `Id_usuario_Cierre`, `Id_registradora`) VALUES ('$row->Id_CierreTransbank', '$row->Fecha', '$row->codigoRespuesta', '$row->glosaRespuesta', '$row->cantidadAnulacionesCompras', '$row->cantidadCompras', '$row->montoTransaccionesVenta', '$row->montoTransaccionesAnulacion', '$row->Id_usuario_Cierre', '$row->Id_registradora');\n";
}
//Fin de Cierres TBK

//Empaque Productos
$sql = "SELECT * FROM Empaque_Productos";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $row->FechaActualizo = $row->FechaActualizo->format('Y-m-d');

    $sql_exportar .= "INSERT INTO `Empaque_Productos`(`Producto`, `Empaque`, `FechaActualizo`) VALUES ('$row->Producto','$row->Empaque','$row->FechaActualizo');\n";
}
//Fin de Empaque Productos

//Empaques_Pedidos
$sql = "SELECT * FROM Empaques_Pedidos ORDER BY Id_Pedido ASC";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $sql_exportar .= "INSERT INTO `Empaques_Pedidos`(`Id_Pedido`, `Producto`, `Unidades_Empaque`, `Empaque_Pedido`) VALUES ('$row->Id_Pedido', '$row->Producto', '$row->Unidades_Empaque', '$row->Empaque_Pedido');\n";
}
//Fin de Empaques_Pedidos

//Pedido
$sql = "SELECT * FROM Pedido";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $row->FechaOperacion = $row->FechaOperacion->format('Y-m-d H:i:s');
    $row->FechaHora_Captura = $row->FechaHora_Captura->format('Y-m-d H:i:s');
    $row->FechaPedido = $row->FechaPedido->format('Y-m-d H:i:s');

    $sql_exportar .= "INSERT INTO `Pedido`(`Id_Pedido`, `FechaOperacion`, `Id_Usuario`, `Semanal`, `FechaHora_Captura`, `Estatus`, `Observacion`, `Dias`, `Adicionales`, `FechaPedido`, `IncluirMenudeo`, `Definitivo`, `FolioConfirmacion`, `FolioPedido`, `SincRef`, `Estimado`, `PedidoEmergente`, `Id_Almacen_Surtido`, `Id_Financiamiento`) VALUES ('$row->Id_Pedido', '$row->FechaOperacion', '$row->Id_Usuario', '$row->Semanal', '$row->FechaHora_Captura', '$row->Estatus', '$row->Observacion', '$row->Dias', '$row->Adicionales', '$row->FechaPedido', '$row->IncluirMenudeo', '$row->Definitivo', '$row->FolioConfirmacion', '$row->FolioPedido', '$row->SincRef', '$row->Estimado', '$row->PedidoEmergente', '$row->Id_Almacen_Surtido', '$row->Id_Financiamiento');\n";
}
//Fin de Pedido

//Pedido_Detalle
$sql = "SELECT * FROM Pedido_Detalle ORDER BY Id_Pedido ASC";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $row->UltimaVenta = $row->UltimaVenta->format('Y-m-d H:i:s');
    $sql_exportar .= "INSERT INTO `Pedido_Detalle`(`Id_Pedido`, `Id_Producto`, `UltimaVenta`, `Sugerencia`, `Pedido`, `ExistenciaTeorica`, `CostoUnitario`) VALUES ('$row->Id_Pedido', '$row->Id_Producto', '$row->UltimaVenta', '$row->Sugerencia', '$row->Pedido', '$row->ExistenciaTeorica', '$row->CostoUnitario');\n";
}
//Pedido_Detalle

//Inventario Otros
$sql = "SELECT * FROM Inventario_Otros";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $row->FechaOperacion = $row->FechaOperacion->format('Y-m-d H:i:s');
    $row->FechaHora_Captura = $row->FechaHora_Captura->format('Y-m-d H:i:s');

    $sql_exportar .= "INSERT INTO `Inventario_Otros`(`Id_Registro`, `Id_Movimiento`, `Documento`, `Referencia`, `Signo`, `FechaOperacion`, `FechaHora_Captura`, `Id_Usuario`, `Observacion`, `SincRef`, `Id_tipo`) VALUES ('$row->Id_Registro', '$row->Id_Movimiento', '$row->Documento', '$row->Referencia', '$row->Signo', '$row->FechaOperacion', '$row->FechaHora_Captura', '$row->Id_Usuario', '$row->Observacion', '$row->SincRef', '$row->id_tipo');\n";
}
//Fin de Inventario Otros

//Inventario Otros Detalle
$sql = "SELECT * FROM Inventario_Otros_Detalle ORDER BY Id_Registro ASC";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $sql_exportar .= "INSERT INTO `Inventario_Otros_Detalle`(`Id_Registro`, `Id_Producto`, `Cantidad`) VALUES ('$row->Id_Registro', '$row->Id_Producto', '$row->Cantidad');\n";
}
//Fin de Inventario Otros Detalle

//Inventario Otros Tipo
$sql = "SELECT * FROM Inventario_Otros_Tipo";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $sql_exportar .= "INSERT INTO `Inventario_Otros_Tipo`(`Id_tipo`, `Nombre`, `Signo`) VALUES ('$row->Id_tipo', '$row->Nombre', '$row->Signo');\n";
}
//Fin de Inventario Otros Tipo

//Inventario Traspaso
$sql = "SELECT * FROM Inventario_Traspaso";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $row->FechaOperacion = $row->FechaOperacion->format('Y-m-d H:i:s');
    $row->FechaHora_Captura = $row->FechaHora_Captura->format('Y-m-d H:i:s');
    $row->FechaHora_Autorizacion = $row->FechaHora_Autorizacion->format('Y-m-d H:i:s');

    $sql_exportar .= "INSERT INTO `Inventario_Traspaso`(`Id_Farmacia_Entrega`, `Id_Traspaso`, `Id_Farmacia_Pedido`, `Id_Concepto`, `Id_Movimiento`, `Documento`, `Referencia`, `FechaOperacion`, `FechaHora_Captura`, `FechaHora_Autorizacion`, `Id_Usuario_Captura`, `Id_Usuario_Autoriza`, `Estatus`, `SincRef`, `Total`) VALUES ('$row->Id_Farmacia_Entrega', '$row->Id_Traspaso', '$row->Id_Farmacia_Pedido', '$row->Id_Concepto', '$row->Id_Movimiento', '$row->Documento', '$row->Referencia', '$row->FechaOperacion', '$row->FechaHora_Captura', '$row->FechaHora_Autorizacion', '$row->Id_Usuario_Captura', '$row->Id_Usuario_Autoriza', '$row->Estatus', '$row->SincRef', '$row->Total');\n";
}
//Fin de Inventario Traspaso

//Inventario Traspaso Detalle
$sql = "SELECT * FROM Inventario_Traspaso_Detalle ORDER BY Id_Traspaso ASC";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $sql_exportar .= "INSERT INTO `Inventario_Traspaso_Detalle`(`Id_Farmacia_Entrega`, `Id_Traspaso`, `Id_Producto`, `Solicitud`, `Autorizado`, `Precio`, `Importe`) VALUES ('$row->Id_Farmacia_Entrega', '$row->Id_Traspaso', '$row->Id_Producto', '$row->Solicitud', '$row->Autorizado', '$row->Precio', '$row->Importe');\n";
}
//Fin de Inventario Traspaso Detalle

//Venta
$sql = "SELECT * FROM Venta  ORDER BY Id_Venta ASC";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $row->FechaHoraCobro = $row->FechaHoraCobro->format('Y-m-d H:i:s');
    $row->FechaHoraVenta = $row->FechaHoraVenta->format('Y-m-d H:i:s');
    $row->FechaHoraCancelacion = $row->FechaHoraCancelacion->format('Y-m-d H:i:s');
    $row->FechaOperacion = $row->FechaOperacion->format('Y-m-d H:i:s');

    $sql_exportar .= "INSERT INTO `Venta`(`Id_Venta`, `Id_Venta_Local`, `Id_Venta_Consecutivo`, `Id_Movimiento`, `Id_Venta_Registradora`, `Id_Registradora_Venta`, `Id_Registradora_Cobro`, `Id_Usuario_Venta`, `Id_Usuario_Cobro`, `Id_Usuario_Cancelacion`, `FechaHoraVenta`, `FechaHoraCobro`, `FechaHoraCancelacion`, `FechaOperacion`, `TipoVenta`, `TipoOperacion`, `Id_Venta_Referencia`, `Receta`, `AntesTotal`, `Estatus`, `Historico`, `Sincroniza`, `Id_Cliente`, `PuntosIniciales`, `PuntosFinales`, `PuntosAcumulados`, `Restriccion`) VALUES ('$row->Id_Venta','$row->Id_Venta_Local','$row->Id_Venta_Consecutivo','$row->Id_Movimiento','$row->Id_Venta_Registradora','$row->Id_Registradora_Venta','$row->Id_Registradora_Cobro','$row->Id_Usuario_Venta','$row->Id_Usuario_Cobro','$row->Id_Usuario_Cancelacion','$row->FechaHoraVenta','$row->FechaHoraCobro','$row->FechaHoraCancelacion','$row->FechaOperacion','$row->TipoVenta','$row->TipoOperacion','$row->Id_Venta_Referencia','$row->Receta','$row->AntesTotal','$row->Estatus','$row->Historico','$row->Sincroniza','$row->Id_Cliente','$row->PuntosIniciales','$row->PuntosFinales','$row->PuntosAcumulados','$row->Restriccion');\n";
}
//Fin de Venta

//Venta Producto
$sql = "SELECT * FROM Venta_Producto  ORDER BY Id_Venta ASC";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $sql_exportar .= "INSERT INTO `Venta_Producto`(`Id_Venta`, `Id_Venta_Local`, `Id_Venta_Consecutivo`, `Id_Producto`, `Cantidad`, `Precio`, `IVA`, `Descuento`, `DescuentoPorciento`, `Puntos`, `IVA_Porciento`, `IVA_Importe`, `Posicion`, `Premio`) VALUES ('$row->Id_Venta','$row->Id_Venta_Local','$row->Id_Venta_Consecutivo','$row->Id_Producto','$row->Cantidad','$row->Precio','$row->IVA','$row->Descuento','$row->DescuentoPorciento','$row->Puntos','$row->IVA_Porciento','$row->IVA_Importe','$row->Posicion','$row->Premio');\n";
}
//Fin de Venta Producto

//Venta Pago
$sql = "SELECT * FROM Venta_Pago ORDER BY Id_Venta ASC";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $sql_exportar .= "INSERT INTO `Venta_Pago`(`Id_Venta`, `Id_Venta_Local`, `Id_Venta_Consecutivo`, `Id_FormaPago`, `Importe`, `TipoCambio`) VALUES ('$row->Id_Venta','$row->Id_Venta_Local','$row->Id_Venta_Consecutivo','$row->Id_FormaPago','$row->Importe','$row->TipoCambio');\n";
}
//Fin de Venta Pago

//Usuario
$sql = "SELECT * FROM Usuario";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $sql_exportar .= "INSERT INTO `Usuario`(`Id_Usuario`, `Nombre`, `Id_Idioma`, `LlaveAcceso`, `FechaUltimoCambio`, `CambiarLlaveAcceso`, `Temporal`, `EstatusRegistro`) VALUES ('$row->Id_Usuario','$row->Nombre','$row->Id_Idioma','$row->LlaveAcceso','$row->FechaUltimoCambio','$row->CambiarLlaveAcceso','$row->Temporal','$row->EstatusRegistro');\n";
}
//Fin de Usuario

//Inventario
$sql = "SELECT * FROM Inventario";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $sql_exportar .= "INSERT INTO `Inventario`(`Id_Producto`, `Existencia`, `NoDisponible`, `Fecha_Modificacion`) VALUES ('$row->Id_Producto','$row->Existencia','$row->NoDisponible','$row->Fecha_Modificacion');\n";
}
//Fin de Inventario

//Inventario Surtido
$sql = "SELECT * FROM Inventario_Surtido";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $row->FechaOperacion = $row->FechaOperacion->format('Y-m-d H:i:s');
    $row->FechaHora_Captura = $row->FechaHora_Captura->format('Y-m-d H:i:s');
    $row->FechaOperacion_Descarga = $row->FechaOperacion_Descarga->format('Y-m-d H:i:s');
    $row->Fecha_Facturacion = $row->Fecha_Facturacion->format('Y-m-d H:i:s');
    $row->FechaVencimiento = $row->FechaVencimiento->format('Y-m-d H:i:s');

    $sql_exportar .= "INSERT INTO `Inventario_Surtido`(`Id_Surtido`, `Id_Surtido_Local`, `Id_Movimiento`, `Documento`, `Referencia`, `FechaOperacion`, `FechaHora_Captura`, `FechaOperacion_Descarga`, `Fecha_Facturacion`, `Id_Usuario`, `Factura`, `Factura_Fiscal`, `SurtidoElectronico`, `Id_Proveedor`, `Id_FarmaciaSurtido`, `Estatus`, `Observacion`, `Subtotal`, `Descuento`, `Impuesto`, `Total`, `Respaldo`, `Conteo`, `SincRef`, `FechaVencimiento`, `Factura_Fiscal_Ref`, `Signo`) VALUES ('$row->Id_Surtido', '$row->Id_Surtido_Local', '$row->Id_Movimiento', '$row->Documento', '$row->Referencia', '$row->FechaOperacion', '$row->FechaHora_Captura', '$row->FechaOperacion_Descarga', '$row->Fecha_Facturacion', '$row->Id_Usuario', '$row->Factura', '$row->Factura_Fiscal', '$row->SurtidoElectronico', '$row->Id_Proveedor', '$row->Id_FarmaciaSurtido', '$row->Estatus', '$row->Observacion', '$row->Subtotal', '$row->Descuento', '$row->Impuesto', '$row->Total', '$row->Respaldo', '$row->Conteo', '$row->SincRef', '$row->FechaVencimiento', '$row->Factura_Fiscal_Ref', '$row->Signo');\n";
}
//Fin de Inventario Surtido

//Inventario Surtido Detalle
$sql = "SELECT * FROM Inventario_Surtido_Detalle ORDER BY Id_Surtido ASC";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $sql_exportar .= "INSERT INTO `Inventario_Surtido_Detalle`(`Id_Surtido`, `Id_Surtido_Local`, `Id_Producto`, `Remision`, `Conteo1`, `Conteo2`, `MalEstado`, `CostoUnitario`, `Descuento`, `Impuesto`, `Total`, `SubTotal`, `IVAPorciento`, `DescuentoPorciento`) VALUES ('$row->Id_Surtido', '$row->Id_Surtido_Local', '$row->Id_Producto', '$row->Remision', '$row->Conteo1', '$row->Conteo2', '$row->MalEstado', '$row->CostoUnitario', '$row->Descuento', '$row->Impuesto', '$row->Total', '$row->SubTotal', '$row->IVAPorciento', '$row->DescuentoPorciento');\n";
}
//Fin de Inventario Surtido Detalle

//Producto
$sql = "SELECT * FROM Producto";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $row->FechaInclusion = $row->FechaInclusion->format('Y-m-d H:i:s');
    $sql_exportar .= "INSERT INTO `Producto`(`Id_Producto`, `Id_Nivel1`, `Id_Nivel2`, `Id_Nivel3`, `Id_Articulo`, `Id_Presentacion`, `Nombre`, `MarcaEconomica`, `PrecioCompra`, `Precio`, `UltimoCosto`, `IVA`, `Inventario`, `InventarioDiario`, `Combo`, `OTC`, `Venta`, `Servicio`, `Premio`, `EstructuraNegocio`, `AplicaCaducidad`, `AplicaDescuento`, `ProductoBasico`, `AsignaPuntos`, `PrecioPuntos`, `ProductoGondola`, `EstatusRegistro`, `Controlado`, `Descripcion_Corta`, `FueradeCatalogo`, `NoPonderado`, `CantidadPresentacion`, `FechaInclusion`, `escombo`) VALUES ('$row->Id_Producto', '$row->Id_Nivel1', '$row->Id_Nivel2', '$row->Id_Nivel3', '$row->Id_Articulo', '$row->Id_Presentacion', '$row->Nombre', '$row->MarcaEconomica', '$row->PrecioCompra', '$row->Precio', '$row->UltimoCosto', '$row->IVA', '$row->Inventario', '$row->InventarioDiario', '$row->Combo', '$row->OTC', '$row->Venta', '$row->Servicio', '$row->Premio', '$row->EstructuraNegocio', '$row->AplicaCaducidad', '$row->AplicaDescuento', '$row->ProductoBasico', '$row->AsignaPuntos', '$row->PrecioPuntos', '$row->ProductoGondola', '$row->EstatusRegistro', '$row->Controlado', '$row->Descripcion_Corta', '$row->FueradeCatalogo', '$row->NoPonderado', '$row->CantidadPresentacion', '$row->FechaInclusion', '$row->escombo');\n";
}
//Fin de Producto

//Producto Codigo Barra
$sql = "SELECT * FROM Producto_CodigoBarra";
$stmt = sqlsrv_query( $conn, $sql );
while ($row = sqlsrv_fetch_object( $stmt)) {
    $sql_exportar .= "INSERT INTO `Producto_CodigoBarra`(`Id_Producto`, `Codigo`) VALUES ('$row->Id_Producto', '$row->Codigo');\n";
}
//Fin de Producto Codigo Barra

//Venta Tarjeta
$sql = "SELECT * FROM Venta_Tarjeta ORDER BY Id_Venta ASC";
$stmt = sqlsrv_query( $conn, $sql );

while ($row = sqlsrv_fetch_object( $stmt)) {
    $sql_exportar .= "INSERT INTO `Venta_Tarjeta`(`Id_Venta`, `Id_Venta_Local`, `Id_Venta_Consecutivo`, `Autorizacion`, `Id_FormaPago`, `Tarjeta`, `Importe`, `TipoCambio`, `NumeroCuenta`, `numeroTransaccion`, `referenciaFinanciera`, `idPromocionTarjetaBancaria`) VALUES ('$row->Id_Venta','$row->Id_Venta_Local','$row->Id_Venta_Consecutivo','$row->Autorizacion','$row->Id_FormaPago','$row->Tarjeta','$row->Importe','$row->TipoCambio','$row->NumeroCuenta','$row->numeroTransaccion','$row->referenciaFinanciera','$row->idPromocionTarjetaBancaria');\n";
}
//Fin de Venta Tarjeta

$myfile = fopen("exportaciones/bbdd.sql", "w") or die("Unable to open file!");
fwrite($myfile, $sql_exportar);
fclose($myfile);