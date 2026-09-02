<?php
include_once ( '../constants.php' );
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
		die( json_encode( [ 'result' => false, 'message' => 'No tienes permiso para modificar vales' ] ) );
	}

	switch( $cAction ) {

		case 'next_folio':
			$result = NextFolio();
			break;

		case 'list':
			$result = ListVales();
			break;

		case 'get':
			$result = GetVale();
			break;

		case 'save':
			$result = SaveVale();
			break;

		case 'delete':
			$result = DeleteVale();
			break;

		case 'get_articulo':
			$result = GetArticulo();
			break;

		case 'search_articulos':
			$result = SearchArticulos();
			break;

		case 'list_tipos':
			$result = ListTiposMov();
			break;

		case 'verify_password':
			$result = VerifyPassword();
			break;

		default:
			$result = [ 'result' => false, 'message' => 'Acción no permitida' ];
	}
} catch ( Exception $e ) {
	$result = [ 'result' => false, 'message' => 'Error interno: ' . $e->getMessage() ];
}

die( json_encode( $result, JSON_UNESCAPED_UNICODE ) );

function NextFolio() {
	$tipomov = filter_post( 'tipomov' );
	if ( empty($tipomov) ) {
		return [ 'result' => false, 'message' => 'Tipo de movimiento requerido' ];
	}

	$oDb = create_conex();
	$prefix = $tipomov . '-' . date('Y') . '-';
	$sql = "SELECT folio FROM vales WHERE folio LIKE '$prefix%' ORDER BY id DESC LIMIT 1";

	if ( $oDb->query($sql) ) {
		$row = $oDb->getrow();
		if ( $row ) {
			$parts = explode('-', $row['folio']);
			$num = intval(end($parts)) + 1;
		} else {
			$num = 1;
		}
		$folio = $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
	} else {
		$folio = $prefix . '0001';
	}

	$oDb->Close();
	return [ 'result' => true, 'folio' => $folio ];
}

function ListTiposMov() {
	$oDb = create_conex();
	$oDb->query("SELECT id, codigo, nombre, signo FROM tipos_movimiento ORDER BY id");
	$rows = [];
	while ( $row = $oDb->getrow() ) {
		$rows[] = $row;
	}
	$oDb->Close();
	return [ 'result' => true, 'data' => $rows ];
}

function ListVales() {
	$oDb = create_conex();
	$filtro = trim( filter_post('filtro_detalle') );
	$busqueda = trim( filter_post('busqueda') );
	$fecha_desde = trim( filter_post('fecha_desde') );
	$fecha_hasta = trim( filter_post('fecha_hasta') );
	$tipo = trim( filter_post('tipo') );
	$solo_hoy = trim( filter_post('solo_hoy') );
	$fecha_solo = trim( filter_post('fecha_solo') );

	$sql = "SELECT v.id, v.folio, v.fecha, v.tipomov, v.id_tipomov,
				   tm.nombre AS tipomov_nombre, tm.signo,
				   v.quienentrega, v.quienrecibe,
				   v.descripcion_trabajo, v.observaciones,
				   v.estatus, v.fechaalta, v.fechacambio,
				   COALESCE((SELECT COUNT(*) FROM vales_detalle WHERE id_vale = v.id),0) AS total_partidas,
				   COALESCE((SELECT SUM(cantidad) FROM vales_detalle WHERE id_vale = v.id),0) AS total_cantidad,
				   COALESCE((SELECT GROUP_CONCAT(DISTINCT SUBSTRING(vd.producto,1,60) SEPARATOR ', ') FROM vales_detalle vd WHERE vd.id_vale = v.id),'') AS productos
			FROM vales v
			INNER JOIN tipos_movimiento tm ON tm.id = v.id_tipomov";

	$where = [];

	if ( $filtro !== '' ) {
		$like = '%' . $oDb->escape_string($filtro) . '%';
		$where[] = "EXISTS (
				SELECT 1 FROM vales_detalle vd
				WHERE vd.id_vale = v.id
				AND (vd.codigo LIKE '$like' OR vd.producto LIKE '$like')
			)";
	}

	if ( $busqueda !== '' ) {
		$like = '%' . $oDb->escape_string($busqueda) . '%';
		$where[] = "(v.folio LIKE '$like' OR EXISTS (
				SELECT 1 FROM vales_detalle vd
				WHERE vd.id_vale = v.id
				AND (vd.codigo LIKE '$like' OR vd.producto LIKE '$like')
			))";
	}

	if ( $fecha_desde !== '' ) {
		$where[] = "v.fecha >= '" . $oDb->escape_string($fecha_desde) . "'";
	}

	if ( $fecha_hasta !== '' ) {
		$where[] = "v.fecha <= '" . $oDb->escape_string($fecha_hasta) . "'";
	}

	if ( $tipo === 'E' || $tipo === 'S' ) {
		$where[] = "v.tipomov = '$tipo'";
	}

	if ( $solo_hoy === '1' ) {
		$fechaFiltro = ( $fecha_solo !== '' ) ? $fecha_solo : date('Y-m-d');
		$where[] = "v.fecha = '" . $oDb->escape_string($fechaFiltro) . "'";
	}

	if ( count($where) > 0 ) {
		$sql .= " WHERE " . implode( " AND ", $where );
	}

	$sql .= " ORDER BY v.id DESC";

	if ( $oDb->query($sql) ) {
		$rows = [];
		while ( $row = $oDb->getrow() ) {
			$rows[] = $row;
		}
		$result = [ 'result' => true, 'data' => $rows ];
	} else {
		$result = [ 'result' => false, 'message' => 'Error al listar vales' ];
	}

	$oDb->Close();
	return $result;
}

function GetVale() {
	$id = filter_post( 'id' );
	if ( empty($id) ) {
		return [ 'result' => false, 'message' => 'ID no especificado' ];
	}

	$oDb = create_conex();

	$sql = "SELECT v.*, tm.nombre AS tipomov_nombre, tm.signo
			FROM vales v
			INNER JOIN tipos_movimiento tm ON tm.id = v.id_tipomov
			WHERE v.id = ? LIMIT 1";

	if ( $oDb->bind_params($sql, [$id]) ) {
		$vale = $oDb->getrow();
		if ( !$vale ) {
			$oDb->Close();
			return [ 'result' => false, 'message' => 'Vale no encontrado' ];
		}

		$sqlDet = "SELECT vd.*, a.existencia AS existencia_actual_sistema
				   FROM vales_detalle vd
				   LEFT JOIN articulos a ON a.id = vd.id_articulo
				   WHERE vd.id_vale = ? ORDER BY vd.id";

		if ( $oDb->bind_params($sqlDet, [$id]) ) {
			$detalles = [];
			while ( $row = $oDb->getrow() ) {
				$detalles[] = $row;
			}
			$vale['detalles'] = $detalles;
		} else {
			$vale['detalles'] = [];
		}

		$result = [ 'result' => true, 'data' => $vale ];
	} else {
		$result = [ 'result' => false, 'message' => 'Error al obtener vale' ];
	}

	$oDb->Close();
	return $result;
}

function SaveVale() {
	try {
		$id               = intval( filter_post('id') );
		$folio            = trim( filter_post('folio') );
		$fecha            = trim( filter_post('fecha') );
		$id_tipomov       = intval( filter_post('id_tipomov') );
		$tipomov          = trim( filter_post('tipomov') );
		$quienentrega     = trim( filter_post('quienentrega') );
		$quienrecibe      = trim( filter_post('quienrecibe') );
		$descripcion_trabajo = trim( filter_post('descripcion_trabajo') );
		$observaciones    = trim( filter_post('observaciones') );
		$detalles_json    = filter_post('detalles');

		if ( empty($folio) || empty($fecha) || empty($tipomov) ) {
			return [ 'result' => false, 'message' => 'Folio, fecha y tipo son obligatorios' ];
		}

		if ( empty($detalles_json) ) {
			return [ 'result' => false, 'message' => 'Debe agregar al menos un artículo' ];
		}

		$detalles = json_decode( $detalles_json, true );
		if ( !is_array($detalles) || count($detalles) == 0 ) {
			return [ 'result' => false, 'message' => 'Detalles inválidos' ];
		}

		$oDb = create_conex();

		// Obtener signo del tipo de movimiento
		$oDb->bind_params("SELECT signo FROM tipos_movimiento WHERE id = ?", [$id_tipomov]);
		$rowSigno = $oDb->getrow();
		$signo = $rowSigno ? intval($rowSigno['signo']) : 1;

		if ( $id > 0 ) {
			// Revertir stock y contadores anteriores
			$sqlOld = "SELECT vd.id_articulo, vd.cantidad, v.tipomov
					   FROM vales_detalle vd
					   INNER JOIN vales v ON v.id = vd.id_vale
					   WHERE vd.id_vale = ?";
			if ( $oDb->bind_params($sqlOld, [$id]) ) {
				while ( $row = $oDb->getrow() ) {
					$signoOld = ($row['tipomov'] == 'E') ? -1 : 1;
					$ajuste = $row['cantidad'] * $signoOld;
					$oDb->execute("UPDATE articulos SET existencia = existencia + ($ajuste) WHERE id = {$row['id_articulo']}");
					// Revertir entradas/salidas
					if ( $row['tipomov'] == 'E' ) {
						$oDb->execute("UPDATE articulos SET entradas = COALESCE(entradas,0) - {$row['cantidad']} WHERE id = {$row['id_articulo']}");
					} else {
						$oDb->execute("UPDATE articulos SET salidas = COALESCE(salidas,0) - {$row['cantidad']} WHERE id = {$row['id_articulo']}");
					}
				}
			}
			// Eliminar detalles anteriores
			$oDb->execute("DELETE FROM vales_detalle WHERE id_vale = $id");
		}

		// Guardar maestro
		if ( $id > 0 ) {
			$sqlVale = "UPDATE vales SET
						fecha = '$fecha', id_tipomov = $id_tipomov, tipomov = '$tipomov',
						quienentrega = '" . $oDb->escape_string($quienentrega) . "',
						quienrecibe = '" . $oDb->escape_string($quienrecibe) . "',
						descripcion_trabajo = '" . $oDb->escape_string($descripcion_trabajo) . "',
						observaciones = '" . $oDb->escape_string($observaciones) . "'
						WHERE id = $id";
			$success = $oDb->execute($sqlVale);
		} else {
			$sqlVale = "INSERT INTO vales (folio, fecha, id_tipomov, tipomov, quienentrega, quienrecibe,
						descripcion_trabajo, observaciones, estatus, usuario_crea)
						VALUES ('" . $oDb->escape_string($folio) . "', '$fecha', $id_tipomov, '$tipomov',
						'" . $oDb->escape_string($quienentrega) . "',
						'" . $oDb->escape_string($quienrecibe) . "',
						'" . $oDb->escape_string($descripcion_trabajo) . "',
						'" . $oDb->escape_string($observaciones) . "',
						'ACTIVO', '')";
			$success = $oDb->execute($sqlVale);
			if ( $success ) {
				$oDb->query("SELECT LAST_INSERT_ID() AS last_id");
				$rowId = $oDb->getrow();
				$id = $rowId ? intval($rowId['last_id']) : 0;
			}
		}

		if ( !$success || $id <= 0 ) {
			$oDb->Close();
			return [ 'result' => false, 'message' => 'Error al guardar el vale' ];
		}

		// Guardar detalles y actualizar stock
		foreach ( $detalles as $det ) {
			$id_articulo   = isset($det['id_articulo']) ? intval( $det['id_articulo'] ) : 0;
			$codigo        = $oDb->escape_string( trim( $det['codigo'] ?? '' ) );
			$producto      = $oDb->escape_string( trim( $det['producto'] ?? '' ) );
			$categoria     = $oDb->escape_string( trim( $det['categoria'] ?? '' ) );
			$unidad        = $oDb->escape_string( trim( $det['unidad'] ?? '' ) );
			$existencia_actual = floatval( $det['existencia_actual'] ?? 0 );
			$cantidad      = floatval( $det['cantidad'] ?? 0 );

			if ( $cantidad <= 0 || $id_articulo <= 0 ) continue;

			// Stock actual del artículo
			$oDb->bind_params("SELECT existencia FROM articulos WHERE id = ?", [$id_articulo]);
			$rowArt = $oDb->getrow();
			$existencia_real = $rowArt ? floatval($rowArt['existencia']) : 0;

			$existencia_despues = $signo == 1
				? $existencia_real + $cantidad
				: $existencia_real - $cantidad;

			$sqlDet = "INSERT INTO vales_detalle (id_vale, id_articulo, codigo, producto, categoria, unidad,
					   existencia_actual, existencia_despues, cantidad)
					   VALUES ($id, $id_articulo, '$codigo', '$producto', '$categoria', '$unidad',
					   $existencia_actual, $existencia_despues, $cantidad)";
			$oDb->execute($sqlDet);

			// Actualizar stock en articulos
			$nueva_existencia = $existencia_real + ($cantidad * $signo);
			if ( $nueva_existencia < 0 ) $nueva_existencia = 0;
			if ( $signo == 1 ) {
				$oDb->execute("UPDATE articulos SET existencia = $nueva_existencia, entradas = COALESCE(entradas,0) + $cantidad WHERE id = $id_articulo");
			} else {
				$oDb->execute("UPDATE articulos SET existencia = $nueva_existencia, salidas = COALESCE(salidas,0) + $cantidad WHERE id = $id_articulo");
			}
		}

		$oDb->Close();

		return [ 'result' => true, 'message' => 'Vale guardado correctamente', 'id' => $id ];
	} catch ( Exception $e ) {
		if ( isset($oDb) ) $oDb->Close();
		return [ 'result' => false, 'message' => 'Error al guardar el vale: ' . $e->getMessage() ];
	}
}

function DeleteVale() {
	$id = intval( filter_post('id') );
	if ( $id <= 0 ) {
		return [ 'result' => false, 'message' => 'ID no especificado' ];
	}

	$oDb = create_conex();

	// Revertir stock
	$sqlDet = "SELECT vd.id_articulo, vd.cantidad, v.tipomov
			   FROM vales_detalle vd
			   INNER JOIN vales v ON v.id = vd.id_vale
			   WHERE vd.id_vale = ?";
	if ( $oDb->bind_params($sqlDet, [$id]) ) {
		while ( $row = $oDb->getrow() ) {
			$signoRev = ($row['tipomov'] == 'E') ? -1 : 1;
			$ajuste = $row['cantidad'] * $signoRev;
			$oDb->execute("UPDATE articulos SET existencia = existencia + ($ajuste) WHERE id = {$row['id_articulo']}");
			if ( $row['tipomov'] == 'E' ) {
				$oDb->execute("UPDATE articulos SET entradas = COALESCE(entradas,0) - {$row['cantidad']} WHERE id = {$row['id_articulo']}");
			} else {
				$oDb->execute("UPDATE articulos SET salidas = COALESCE(salidas,0) - {$row['cantidad']} WHERE id = {$row['id_articulo']}");
			}
		}
	}

	// Eliminar detalles y maestro
	$oDb->execute("DELETE FROM vales_detalle WHERE id_vale = $id");
	$success = $oDb->execute("DELETE FROM vales WHERE id = $id");

	$oDb->Close();

	return $success
		? [ 'result' => true, 'message' => 'Vale eliminado correctamente' ]
		: [ 'result' => false, 'message' => 'Error al eliminar el vale' ];
}

function GetArticulo() {
	$codigo = trim( filter_post('codigo') );
	if ( empty($codigo) ) {
		return [ 'result' => false, 'message' => 'Código no especificado' ];
	}

	$oDb = create_conex();
	$sql = "SELECT id, codigo, descripcion, familia, unidadmedida, existencia
			FROM articulos WHERE codigo = ? AND estatus = 1 LIMIT 1";

	if ( $oDb->bind_params($sql, [$codigo]) ) {
		$row = $oDb->getrow();
		if ( $row ) {
			$result = [ 'result' => true, 'data' => $row ];
		} else {
			$result = [ 'result' => false, 'message' => 'Artículo no encontrado o inactivo' ];
		}
	} else {
		$result = [ 'result' => false, 'message' => 'Error al buscar artículo' ];
	}

	$oDb->Close();
	return $result;
}

function SearchArticulos() {
	$query = trim( filter_post('query') );
	if ( empty($query) ) {
		$query = '';
	}

	$oDb = create_conex();
	$like = '%' . $oDb->escape_string($query) . '%';
	$sql = "SELECT id, codigo, descripcion, familia, unidadmedida, existencia,
				   COALESCE(entradas,0) AS entradas, COALESCE(salidas,0) AS salidas
			FROM articulos
			WHERE estatus = 1 AND (codigo LIKE '$like' OR descripcion LIKE '$like')
			ORDER BY descripcion";

	if ( $oDb->query($sql) ) {
		$rows = [];
		while ( $row = $oDb->getrow() ) {
			$rows[] = $row;
		}
		$result = [ 'result' => true, 'data' => $rows ];
	} else {
		$result = [ 'result' => false, 'message' => 'Error al buscar artículos' ];
	}

	$oDb->Close();
	return $result;
}

function VerifyPassword() {
	$user = trim( filter_post('user') );
	$clave = filter_post('clave');

	if ( empty($user) || empty($clave) ) {
		return [ 'result' => false, 'message' => 'Debes ingresar usuario y contraseña' ];
	}

	$oDb = create_conex();
	$sql = "SELECT user, username, pasw1, rol FROM users WHERE user = ? LIMIT 1";
	if ( !$oDb->bind_params($sql, [ $user ]) ) {
		$oDb->Close();
		return [ 'result' => false, 'message' => 'Error al verificar la identidad' ];
	}

	$row = $oDb->getrow();
	$oDb->Close();

	if ( !$row ) {
		return [ 'result' => false, 'message' => 'Usuario no encontrado' ];
	}

	// Solo admin/supervisor pueden autorizar
	if ( !in_array($row['rol'], ['admin', 'supervisor']) ) {
		return [ 'result' => false, 'message' => 'El usuario no tiene permisos para autorizar esta acción' ];
	}

	if ( !password_verify($clave, $row['pasw1']) ) {
		return [ 'result' => false, 'message' => 'Contraseña incorrecta' ];
	}

	return [ 'result' => true ];
}
?>
