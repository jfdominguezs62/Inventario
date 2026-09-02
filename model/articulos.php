<?php
include ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( false );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_root() . 'config_db.php' );

header('Content-Type: application/json');

try {
	$cAction = filter_post( 'action' );

	$oSession = new TSession( APP_SESSION );
	$rol = $oSession->GetVar('rol') ?: 'operador';
	if ( $rol === 'consultor' && in_array($cAction, ['save', 'delete']) ) {
		die( json_encode( [ 'result' => false, 'message' => 'No tienes permiso para modificar artículos' ] ) );
	}

	switch( $cAction ) {

	case 'list':
		$result = ListArticulos();
		break;

	case 'get':
		$result = GetArticulo();
		break;

	case 'save':
		$result = SaveArticulo();
		break;

	case 'delete':
		$result = DeleteArticulo();
		break;

	case 'check_codigo':
		$result = CheckCodigo();
		break;

default:
		$result = [ 'result' => false, 'message' => 'Acción no permitida' ];
	}
} catch ( Exception $e ) {
	$result = [ 'result' => false, 'message' => 'Error interno: ' . $e->getMessage() ];
}

die( json_encode( $result, JSON_UNESCAPED_UNICODE ) );

function ListArticulos() {
	$oDb = create_conex();

	$sql = "SELECT id, codigo, descripcion, familia, unidadmedida, piezasxunidad,
				   estatus, existencia, stock_minimo, entradas, salidas, fechaalta, fechacambio
			FROM articulos
			ORDER BY codigo ASC";

	if ( $oDb->query($sql) ) {
		$rows = [];
		while ( $row = $oDb->getrow() ) {
			$rows[] = $row;
		}
		$result = [ 'result' => true, 'data' => $rows ];
	} else {
		$result = [ 'result' => false, 'message' => 'Error al listar artículos' ];
	}

	$oDb->Close();
	return $result;
}

function GetArticulo() {
	$id = filter_post( 'id' );
	if ( empty($id) ) {
		return [ 'result' => false, 'message' => 'ID no especificado' ];
	}

	$oDb = create_conex();

	$sql = "SELECT id, codigo, descripcion, familia, unidadmedida, piezasxunidad,
				   estatus, existencia, stock_minimo, fechaalta, fechacambio
			FROM articulos WHERE id = ? LIMIT 1";

	if ( $oDb->bind_params( $sql, [ $id ] ) ) {
		$row = $oDb->getrow();
		if ( $row ) {
			$result = [ 'result' => true, 'data' => $row ];
		} else {
			$result = [ 'result' => false, 'message' => 'Artículo no encontrado' ];
		}
	} else {
		$result = [ 'result' => false, 'message' => 'Error al obtener artículo' ];
	}

	$oDb->Close();
	return $result;
}

function SaveArticulo() {
	$id          = filter_post( 'id' );
	$codigo      = trim( filter_post( 'codigo' ) );
	$descripcion = trim( filter_post( 'descripcion' ) );
	$familia     = trim( filter_post( 'familia' ) );
	$unidadmedida = trim( filter_post( 'unidadmedida' ) );
	$piezasxunidad = intval( filter_post( 'piezasxunidad' ) );
	$estatus     = intval( filter_post( 'estatus' ) );
	$existencia  = floatval( filter_post( 'existencia' ) );
	$stock_minimo = floatval( filter_post( 'stock_minimo' ) );

	// Validaciones
	if ( empty($codigo) ) {
		return [ 'result' => false, 'message' => 'El código es obligatorio' ];
	}
	if ( empty($descripcion) ) {
		return [ 'result' => false, 'message' => 'La descripción es obligatoria' ];
	}
	if ( $piezasxunidad < 1 ) {
		$piezasxunidad = 1;
	}

	$descripcion = strtoupper($descripcion);

	// Verificar rol de la sesión para existencia
	$oSession = new TSession( APP_SESSION );
	$rol = $oSession->GetVar('rol') ?: 'operador';
	$puedeModificarExistencia = in_array($rol, ['admin', 'supervisor']);

	$descripcion = strtoupper($descripcion);

	$oDb = create_conex();

	// Verificar duplicado de código (excluyendo el registro actual)
	if ( $id ) {
		$sqlCheck = "SELECT id FROM articulos WHERE codigo = ? AND id != ? LIMIT 1";
		$found = $oDb->bind_params( $sqlCheck, [ $codigo, $id ] );
	} else {
		$sqlCheck = "SELECT id FROM articulos WHERE codigo = ? LIMIT 1";
		$found = $oDb->bind_params( $sqlCheck, [ $codigo ] );
	}

	if ( $found ) {
		$row = $oDb->getrow();
		if ( $row ) {
			$oDb->Close();
			return [ 'result' => false, 'message' => 'El código de artículo ingresado ya existe' ];
		}
	}

	if ( $id ) {
		if ($puedeModificarExistencia) {
			$sql = "UPDATE articulos SET
						codigo = ?, descripcion = ?, familia = ?, unidadmedida = ?,
						piezasxunidad = ?, estatus = ?, existencia = ?, stock_minimo = ?,
						fechacambio = NOW()
					WHERE id = ?";

			$success = $oDb->bind_params( $sql, [
				$codigo, $descripcion, $familia, $unidadmedida,
				$piezasxunidad, $estatus, $existencia, $stock_minimo,
				$id
			], true );
		} else {
			$sql = "UPDATE articulos SET
						codigo = ?, descripcion = ?, familia = ?, unidadmedida = ?,
						piezasxunidad = ?, estatus = ?, stock_minimo = ?,
						fechacambio = NOW()
					WHERE id = ?";

			$success = $oDb->bind_params( $sql, [
				$codigo, $descripcion, $familia, $unidadmedida,
				$piezasxunidad, $estatus, $stock_minimo,
				$id
			], true );
		}
	} else {
		if ($puedeModificarExistencia) {
			$sql = "INSERT INTO articulos (codigo, descripcion, familia, unidadmedida, piezasxunidad, estatus, existencia, stock_minimo, fechaalta, fechacambio)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

			$success = $oDb->bind_params( $sql, [
				$codigo, $descripcion, $familia, $unidadmedida,
				$piezasxunidad, $estatus, $existencia, $stock_minimo
			], true );
		} else {
			$sql = "INSERT INTO articulos (codigo, descripcion, familia, unidadmedida, piezasxunidad, estatus, stock_minimo, fechaalta, fechacambio)
					VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

			$success = $oDb->bind_params( $sql, [
				$codigo, $descripcion, $familia, $unidadmedida,
				$piezasxunidad, $estatus, $stock_minimo
			], true );
		}
	}

	$oDb->Close();

	if ( $success ) {
		return [ 'result' => true, 'message' => 'Artículo guardado correctamente' ];
	} else {
		return [ 'result' => false, 'message' => 'Error al guardar el artículo' ];
	}
}

function DeleteArticulo() {
	$oSession = new TSession( APP_SESSION );
	$rol = $oSession->GetVar('rol') ?: 'operador';
	if ( !in_array($rol, ['admin', 'supervisor']) ) {
		return [ 'result' => false, 'message' => 'No tienes permiso para eliminar artículos' ];
	}

	$id = filter_post( 'id' );
	if ( empty($id) ) {
		return [ 'result' => false, 'message' => 'ID no especificado' ];
	}

	$oDb = create_conex();

	// Verificar si tiene movimientos en vales_detalle
	$oDb->bind_params("SELECT COUNT(*) AS total FROM vales_detalle WHERE id_articulo = ?", [$id]);
	$row = $oDb->getrow();
	if ( $row && $row['total'] > 0 ) {
		$oDb->Close();
		return [ 'result' => false, 'message' => 'No se puede eliminar: el artículo tiene movimientos registrados en vales' ];
	}

	// Verificar si tiene existencia inicial, entradas, salidas o existencia actual
	$oDb->bind_params("SELECT COALESCE(existenciainicial,0) AS ei, COALESCE(entradas,0) AS en, COALESCE(salidas,0) AS sa, COALESCE(existencia,0) AS ex FROM articulos WHERE id = ?", [$id]);
	$row = $oDb->getrow();
	if ( $row && (floatval($row['ei']) > 0 || floatval($row['en']) > 0 || floatval($row['sa']) > 0 || floatval($row['ex']) > 0) ) {
		$oDb->Close();
		return [ 'result' => false, 'message' => 'No se puede eliminar: el artículo tiene saldo inicial, entradas, salidas o existencia actual' ];
	}

	$sql = "DELETE FROM articulos WHERE id = ?";
	$success = $oDb->bind_params( $sql, [ $id ], true );

	$oDb->Close();

	if ( $success ) {
		return [ 'result' => true, 'message' => 'Artículo eliminado correctamente' ];
	} else {
		return [ 'result' => false, 'message' => 'Error al eliminar el artículo' ];
	}
}

function CheckCodigo() {
	$codigo = trim( filter_post( 'codigo' ) );
	$id     = filter_post( 'id' );

	if ( empty($codigo) ) {
		return [ 'result' => false, 'message' => 'Código no especificado' ];
	}

	$oDb = create_conex();

	if ( $id ) {
		$sql = "SELECT id FROM articulos WHERE codigo = ? AND id != ? LIMIT 1";
		$found = $oDb->bind_params( $sql, [ $codigo, $id ] );
	} else {
		$sql = "SELECT id FROM articulos WHERE codigo = ? LIMIT 1";
		$found = $oDb->bind_params( $sql, [ $codigo ] );
	}

	$existe = false;
	if ( $found ) {
		$row = $oDb->getrow();
		$existe = ( $row !== null );
	}

	$oDb->Close();

	return [ 'result' => true, 'exists' => $existe ];
}
?>
