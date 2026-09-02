<?php
include_once ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( false );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_root() . 'config_db.php' );

header('Content-Type: application/json');

$cAction = filter_post( 'action' );

switch( $cAction ) {

	case 'save_fisico':
		$result = SaveFisico();
		break;

	case 'save_historico':
		$result = SaveHistorico();
		break;

	case 'consultar_historico':
		$result = ConsultarHistorico();
		break;

	default:
		$result = [ 'result' => false, 'message' => 'Acción no permitida' ];
}

die( json_encode( $result, JSON_UNESCAPED_UNICODE ) );

function SaveFisico() {
	$datos_json = filter_post( 'datos' );
	if ( empty($datos_json) ) {
		return [ 'result' => false, 'message' => 'No se recibieron datos' ];
	}

	$datos = json_decode( $datos_json, true );
	if ( !is_array($datos) || count($datos) == 0 ) {
		return [ 'result' => false, 'message' => 'Datos inválidos' ];
	}

	$oDb = create_conex();
	$count = 0;

	foreach ( $datos as $det ) {
		$id_articulo      = intval( $det['id_articulo'] );
		$existencia_fisica = floatval( $det['existencia_fisica'] );

		if ( $id_articulo <= 0 ) continue;

		$oDb->execute("UPDATE articulos SET existenciafisica = $existencia_fisica, inventariado = 1 WHERE id = $id_articulo");
		$count++;
	}

	$oDb->Close();

	return [ 'result' => true, 'message' => "Existencia física guardada para $count artículo(s)" ];
}

function SaveHistorico() {
	$anio = intval( filter_post('anio') );
	$mes = intval( filter_post('mes') );

	if ( $anio <= 0 || $mes < 1 || $mes > 12 ) {
		return [ 'result' => false, 'message' => 'Año y mes son obligatorios' ];
	}

	$oDb = create_conex();

	// Verificar si ya existe histórico para ese mes
	$r = $oDb->query("SELECT COUNT(*) c FROM historico_exfisica WHERE anio = $anio AND mes = $mes");
	$row = $r->fetch_assoc();
	if ( $row['c'] > 0 ) {
		$oDb->Close();
		return [ 'result' => false, 'message' => "Ya existe histórico registrado para $anio-$mes" ];
	}

	// Insertar snapshot actual
	$sql = "INSERT IGNORE INTO historico_exfisica (anio, mes, id_articulo, codigo, descripcion, familia, unidadmedida, existenciafisica, entradas, salidas)
			SELECT $anio, $mes, id, codigo, descripcion, familia, unidadmedida,
				   COALESCE(existenciafisica, 0), COALESCE(entradas, 0), COALESCE(salidas, 0)
			FROM articulos
			WHERE estatus = 1";

	$oDb->query($sql);
	$count = $oDb->affected_rows;

	$oDb->Close();
	return [ 'result' => true, 'message' => "Histórico guardado: $count artículo(s) para $anio-$mes" ];
}

function ConsultarHistorico() {
	$anio = intval( filter_post('anio') );
	$mes = intval( filter_post('mes') );

	$oDb = create_conex();

	// Si no se especifica mes, mostrar todos los meses disponibles
	if ( $anio > 0 && $mes > 0 ) {
		$sql = "SELECT anio, mes, codigo, descripcion, familia, unidadmedida,
					   existenciafisica, entradas, salidas
				FROM historico_exfisica
				WHERE anio = $anio AND mes = $mes
				ORDER BY codigo ASC";
	} else {
		$sql = "SELECT anio, mes, codigo, descripcion, familia, unidadmedida,
					   existenciafisica, entradas, salidas
				FROM historico_exfisica
				ORDER BY anio DESC, mes DESC, codigo ASC";
	}

	$rows = [];
	if ( $oDb->query($sql) ) {
		while ( $row = $oDb->getrow() ) {
			$rows[] = $row;
		}
	}

	// Meses disponibles
	$meses = [];
	$oDb->query("SELECT DISTINCT anio, mes FROM historico_exfisica ORDER BY anio DESC, mes DESC");
	while ( $row = $oDb->getrow() ) {
		$meses[] = $row;
	}

	$oDb->Close();
	return [ 'result' => true, 'data' => $rows, 'meses_disponibles' => $meses ];
}
?>
