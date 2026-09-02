<?php
include_once ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( false );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_root() . 'config_db.php' );

header('Content-Type: application/json');

$cAction = filter_post( 'action' );

switch( $cAction ) {

	case 'ejecutar':
		$result = EjecutarCierre();
		break;

	default:
		$result = [ 'result' => false, 'message' => 'Acción no permitida' ];
}

die( json_encode( $result, JSON_UNESCAPED_UNICODE ) );

function EjecutarCierre() {
	$clave = filter_post('clave');

	if ( empty($clave) ) {
		return [ 'result' => false, 'message' => 'Debes ingresar tu contraseña' ];
	}

	$oSession = new TSession( APP_SESSION );
	$sessionUser = $oSession->GetVar('id');
	$sessionRol   = $oSession->GetVar('rol') ?: 'operador';

	if ( !in_array($sessionRol, ['admin', 'supervisor']) ) {
		return [ 'result' => false, 'message' => 'No tienes permiso para realizar esta acción' ];
	}

	// Obtener hash de la contraseña del usuario actual
	$oDb = create_conex();
	$sql = "SELECT pasw1 FROM " . TABLA_USUARIOS . " WHERE user = ? LIMIT 1";
	if ( !$oDb->bind_params($sql, [ $sessionUser ]) ) {
		$oDb->Close();
		return [ 'result' => false, 'message' => 'Error al verificar la identidad' ];
	}

	$row = $oDb->getrow();
	if ( !$row ) {
		$oDb->Close();
		return [ 'result' => false, 'message' => 'Usuario no encontrado' ];
	}

	if ( !password_verify($clave, $row['pasw1']) ) {
		$oDb->Close();
		return [ 'result' => false, 'message' => 'Contraseña incorrecta' ];
	}

	// Contar artículos antes
	$oDb->query("SELECT COUNT(*) as total FROM articulos");
	$countRow = $oDb->getrow();
	$totalArticulos = $countRow ? $countRow['total'] : 0;

	// Ejecutar cierre mensual
	$sqlUpdate = "UPDATE articulos
	              SET existencia = COALESCE(existenciafisica, 0),
	                  existenciainicial = COALESCE(existenciafisica, 0),
	                  entradas = 0,
	                  salidas = 0,
	                  inventariado = 0";

	$success = $oDb->execute($sqlUpdate);
	$oDb->Close();

	if ( $success ) {
		return [
			'result'  => true,
			'message' => "Cierre mensual realizado correctamente. Se actualizaron $totalArticulos artículos."
		];
	} else {
		return [ 'result' => false, 'message' => 'Error al ejecutar el cierre mensual' ];
	}
}
