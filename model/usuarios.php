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
		$result = ListUsers();
		break;

	case 'get':
		$result = GetUser();
		break;

	case 'save':
		$result = SaveUser();
		break;

	case 'delete':
		$result = DeleteUser();
		break;

	default:
		$result = [ 'result' => false, 'message' => 'Acción no permitida' ];
}

die( json_encode( $result, JSON_UNESCAPED_UNICODE ) );

function ListUsers() {
	$oDb = create_conex();
	$sql = "SELECT id, user, username, rol, created_at FROM " . TABLA_USUARIOS . " ORDER BY username ASC";
	if ( $oDb->query($sql) ) {
		$rows = [];
		while ( $row = $oDb->getrow() ) {
			$rows[] = $row;
		}
		$result = [ 'result' => true, 'data' => $rows ];
	} else {
		$result = [ 'result' => false, 'message' => 'Error al listar usuarios' ];
	}
	$oDb->Close();
	return $result;
}

function GetUser() {
	$id = intval( filter_post('id') );
	if ( $id <= 0 ) {
		return [ 'result' => false, 'message' => 'ID inválido' ];
	}

	$oDb = create_conex();
	$sql = "SELECT id, user, username, rol, created_at FROM " . TABLA_USUARIOS . " WHERE id = ? LIMIT 1";
	if ( $oDb->bind_params($sql, [$id]) ) {
		$row = $oDb->getrow();
		if ( $row ) {
			$result = [ 'result' => true, 'data' => $row ];
		} else {
			$result = [ 'result' => false, 'message' => 'Usuario no encontrado' ];
		}
	} else {
		$result = [ 'result' => false, 'message' => 'Error al obtener usuario' ];
	}
	$oDb->Close();
	return $result;
}

function SaveUser() {
	$id       = intval( filter_post('id') );
	$user     = trim( filter_post('user') );
	$username = trim( filter_post('username') );
	$clave    = filter_post('clave');
	$rol      = trim( filter_post('rol') );

	if ( empty($user) || empty($username) ) {
		return [ 'result' => false, 'message' => 'Usuario y nombre son obligatorios' ];
	}
	if ( !in_array($rol, ['admin', 'supervisor', 'operador', 'consultor']) ) {
		return [ 'result' => false, 'message' => 'Rol inválido' ];
	}

	$oDb = create_conex();

	if ( $id > 0 ) {
		$sqlSet = "user = '" . $oDb->escape_string($user) . "',
				   username = '" . $oDb->escape_string($username) . "',
				   rol = '$rol'";
		if ( !empty($clave) ) {
			$hash = password_hash($clave, PASSWORD_DEFAULT);
			$sqlSet .= ", pasw1 = '$hash'";
		}
		$success = $oDb->execute("UPDATE " . TABLA_USUARIOS . " SET $sqlSet WHERE id = $id");
		$msg = 'Usuario actualizado correctamente';
	} else {
		if ( empty($clave) ) {
			$oDb->Close();
			return [ 'result' => false, 'message' => 'La contraseña es obligatoria para nuevos usuarios' ];
		}
		$hash = password_hash($clave, PASSWORD_DEFAULT);
		$success = $oDb->insert(
			TABLA_USUARIOS,
			[ 'user', 'username', 'pasw1', 'rol' ],
			[ $user, $username, $hash, $rol ]
		);
		$msg = 'Usuario creado correctamente';
	}

	$oDb->Close();
	if ( $success ) {
		return [ 'result' => true, 'message' => $msg ];
	} else {
		return [ 'result' => false, 'message' => 'Error al guardar el usuario (puede que el nombre de usuario ya exista)' ];
	}
}

function DeleteUser() {
	$id = intval( filter_post('id') );
	if ( $id <= 0 ) {
		return [ 'result' => false, 'message' => 'ID inválido' ];
	}
	if ( $id == 1 ) {
		return [ 'result' => false, 'message' => 'No se puede eliminar el administrador principal' ];
	}

	$oDb = create_conex();
	$success = $oDb->execute("DELETE FROM " . TABLA_USUARIOS . " WHERE id = $id");
	$oDb->Close();

	return $success
		? [ 'result' => true, 'message' => 'Usuario eliminado correctamente' ]
		: [ 'result' => false, 'message' => 'Error al eliminar usuario' ];
}
?>
