<?php
include_once ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( false );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_root() . 'config_db.php' );

header('Content-Type: application/json');

$cAction = filter_post( 'action' );

switch( $cAction ) {

	case 'list':
		$result = ListTrabajadores();
		break;

	case 'get':
		$result = GetTrabajador();
		break;

	case 'save':
		$result = SaveTrabajador();
		break;

	case 'delete':
		$result = DeleteTrabajador();
		break;

	case 'list_activos':
		$result = ListTrabajadoresActivos();
		break;

	default:
		$result = [ 'result' => false, 'message' => 'Acción no permitida' ];
}

die( json_encode( $result, JSON_UNESCAPED_UNICODE ) );

function ListTrabajadores() {
	$oDb = create_conex();
	$oDb->query("SELECT id, idnomina, nombre, departamento, bodeguero, estatus, fechaalta FROM trabajadores ORDER BY nombre ASC");
	$rows = [];
	while ( $row = $oDb->getrow() ) {
		$rows[] = $row;
	}
	$oDb->Close();
	return [ 'result' => true, 'data' => $rows ];
}

function ListTrabajadoresActivos() {
	$oDb = create_conex();
	$oDb->query("SELECT id, idnomina, nombre, departamento, bodeguero FROM trabajadores WHERE estatus = 1 ORDER BY nombre ASC");
	$rows = [];
	while ( $row = $oDb->getrow() ) {
		$rows[] = $row;
	}
	$oDb->Close();
	return [ 'result' => true, 'data' => $rows ];
}

function GetTrabajador() {
	$id = intval( filter_post('id') );
	if ( $id <= 0 ) {
		return [ 'result' => false, 'message' => 'ID inválido' ];
	}

	$oDb = create_conex();
	$oDb->bind_params("SELECT id, idnomina, nombre, departamento, bodeguero, estatus FROM trabajadores WHERE id = ? LIMIT 1", [$id]);
	$row = $oDb->getrow();
	$oDb->Close();

	if ( $row ) {
		return [ 'result' => true, 'data' => $row ];
	} else {
		return [ 'result' => false, 'message' => 'Trabajador no encontrado' ];
	}
}

function SaveTrabajador() {
	$id           = intval( filter_post('id') );
	$idnomina     = trim( filter_post('idnomina') );
	$nombre       = trim( filter_post('nombre') );
	$departamento = trim( filter_post('departamento') );
	$bodeguero    = intval( filter_post('bodeguero') );
	$estatus      = intval( filter_post('estatus') );

	if ( empty($nombre) ) {
		return [ 'result' => false, 'message' => 'El nombre es obligatorio' ];
	}

	$oDb = create_conex();
	$idnomina_esc     = $oDb->escape_string($idnomina);
	$nombre_esc       = $oDb->escape_string($nombre);
	$departamento_esc = $oDb->escape_string($departamento);

	if ( $id > 0 ) {
		$success = $oDb->execute("UPDATE trabajadores SET idnomina = '$idnomina_esc', nombre = '$nombre_esc', departamento = '$departamento_esc', bodeguero = $bodeguero, estatus = $estatus WHERE id = $id");
		$msg = 'Trabajador actualizado correctamente';
	} else {
		$success = $oDb->execute("INSERT INTO trabajadores (idnomina, nombre, departamento, bodeguero, estatus) VALUES ('$idnomina_esc', '$nombre_esc', '$departamento_esc', $bodeguero, $estatus)");
		$msg = 'Trabajador creado correctamente';
	}

	$oDb->Close();
	return $success
		? [ 'result' => true, 'message' => $msg ]
		: [ 'result' => false, 'message' => 'Error al guardar trabajador' ];
}

function DeleteTrabajador() {
	$id = intval( filter_post('id') );
	if ( $id <= 0 ) {
		return [ 'result' => false, 'message' => 'ID inválido' ];
	}

	$oDb = create_conex();
	$success = $oDb->execute("DELETE FROM trabajadores WHERE id = $id");
	$oDb->Close();

	return $success
		? [ 'result' => true, 'message' => 'Trabajador eliminado correctamente' ]
		: [ 'result' => false, 'message' => 'Error al eliminar trabajador' ];
}
?>
