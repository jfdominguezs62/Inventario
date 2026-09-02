<?php 
include ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( false );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_root() . 'config_db.php' );

$cAction = filter_post( 'action' );

switch( $cAction ) {	

	case 'login':
		$result = Login();
		break;

	case 'register':
		$result = Register();
		break;

	case 'logout':
		$result = Logout();
		break;
	
	default:
		$result = [ 'result' => false, 'message' => 'Acción no permitida' ];

}

die( json_encode( $result ) );	

//-------------------------------------	
	
function Login() {
	$usuario = filter_post( 'usuario' );
	$clave   = filter_post( 'clave' );

	$lLogin = false;		
	$oDb = create_conex();

	$Sql = "SELECT * FROM " . TABLA_USUARIOS . " WHERE user = ? LIMIT 1";

	if ( $oDb->bind_params($Sql, [ $usuario ]) ) {
		if ( $row = $oDb->getrow() ) {
			// Validar contraseña con password_verify
			if ( password_verify($clave, $row['pasw1']) ) {
				$rol = !empty($row['rol']) ? $row['rol'] : 'operador';
				$lLogin = ValidSucess( $row['username'], $row['user'], $rol );
			}
		} 
	}	
		
	$oDb->Close();

	return [ 'result' => $lLogin ];
}

//-------------------------------------	

function Register() {
	$nombre  = filter_post( 'nombre' );
	$usuario = filter_post( 'usuario' );
	$clave   = filter_post( 'clave' );

	if ( empty($nombre) || empty($usuario) || empty($clave) ) {
		return [ 'result' => false, 'message' => 'Todos los campos son obligatorios' ];
	}

	$oDb = create_conex();

	// Verificar si el usuario ya existe
	$SqlCheck = "SELECT id FROM " . TABLA_USUARIOS . " WHERE user = ? LIMIT 1";
	if ( $oDb->bind_params($SqlCheck, [ $usuario ]) ) {
		if ( $row = $oDb->getrow() ) {
			$oDb->Close();
			return [ 'result' => false, 'message' => 'El usuario ya está registrado' ];
		}
	}

	// Cifrar la contraseña
	$hashed_password = password_hash($clave, PASSWORD_DEFAULT);

	// Determinar rol: el primer usuario registrado es admin
	$rol = 'operador';
	$checkCount = $oDb->query("SELECT COUNT(*) as total FROM " . TABLA_USUARIOS);
	if ( $checkCount && ($rowCount = $oDb->getrow()) && $rowCount['total'] == 0 ) {
		$rol = 'admin';
	}

	$success = $oDb->insert(
		TABLA_USUARIOS,
		[ 'username', 'user', 'pasw1', 'rol' ],
		[ $nombre, $usuario, $hashed_password, $rol ]
	);

	$oDb->Close();

	if ( $success ) {
		return [ 'result' => true ];
	} else {
		return [ 'result' => false, 'message' => 'No se pudo guardar el usuario en la base de datos' ];
	}
}

//-------------------------------------	

function ValidSucess( $name, $id, $rol = 'operador' ) {
	$oSession = new TSession( APP_SESSION );	
	$oSession->AddVar( 'usuario', $name );
	$oSession->AddVar( 'id', $id );
	$oSession->AddVar( 'rol', $rol );
	$oSession->Login();
	return true;
}

//-------------------------------------	

function Logout() {
	$oSession = new TSession( APP_SESSION );	
	$oSession->Logout();
	return [ 'result' => true ];
}
?>
