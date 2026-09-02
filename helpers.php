<?php
// Roles: admin, supervisor, operador, consultor

function tieneRol( $rolRequerido ) {
	$oSession = new TSession( APP_SESSION );
	$rol = $oSession->GetVar('rol');
	if ( empty($rol) ) $rol = 'operador';

	if ( $rolRequerido == 'admin' ) {
		return $rol === 'admin';
	}
	if ( $rolRequerido == 'supervisor' ) {
		return $rol === 'admin' || $rol === 'supervisor';
	}
	// operador: todos excepto consultor
	return in_array($rol, ['admin', 'supervisor', 'operador']);
}

function rolUsuario() {
	$oSession = new TSession( APP_SESSION );
	$rol = $oSession->GetVar('rol');
	return $rol ?: 'operador';
}

function checkAcceso( $rolesPermitidos = [] ) {
	if ( empty($rolesPermitidos) ) return;
	$oSession = new TSession( APP_SESSION );
	if ( is_string($rolesPermitidos) ) {
		$args = func_get_args();
		$rolesPermitidos = isset($args[1]) ? $args[1] : [];
		if ( empty($rolesPermitidos) ) return;
	}
	$rol = $oSession->GetVar('rol') ?: 'operador';
	if ( !in_array($rol, $rolesPermitidos) ) {
		header("Location: menu.php");
		exit;
	}
}
?>
