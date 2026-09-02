<?php
include_once ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( false );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_root() . 'config_db.php' );

header('Content-Type: application/json');

$cAction = filter_post( 'action' );

switch( $cAction ) {

	case 'existencia':
		$result = ReporteExistencia();
		break;

	case 'movimientos':
		$result = ReporteMovimientos();
		break;

	case 'vales_periodo':
		$result = ReporteValesPeriodo();
		break;

	case 'resumen':
		$result = ReporteResumen();
		break;

	case 'movimientos_mes':
		$result = ReporteMovimientosMes();
		break;

	default:
		$result = [ 'result' => false, 'message' => 'Acción no permitida' ];
}

die( json_encode( $result, JSON_UNESCAPED_UNICODE ) );

function ReporteExistencia() {
	$oDb = create_conex();
	$sql = "SELECT id, codigo, descripcion, familia, unidadmedida,
				   COALESCE(existenciainicial,0) AS existenciainicial,
				   COALESCE(entradas,0) AS entradas,
				   COALESCE(salidas,0) AS salidas,
				   COALESCE(existencia,0) AS existencia
			FROM articulos
			WHERE estatus = 1
			ORDER BY codigo ASC";

	if ( $oDb->query($sql) ) {
		$rows = [];
		while ( $row = $oDb->getrow() ) {
			$rows[] = $row;
		}
		$result = [ 'result' => true, 'data' => $rows ];
	} else {
		$result = [ 'result' => false, 'message' => 'Error al generar reporte' ];
	}

	$oDb->Close();
	return $result;
}

function ReporteMovimientos() {
	$id_articulo = intval( filter_post('id_articulo') );
	$fecha_desde = trim( filter_post('fecha_desde') );
	$fecha_hasta = trim( filter_post('fecha_hasta') );

	if ( $id_articulo <= 0 ) {
		return [ 'result' => false, 'message' => 'Seleccione un artículo' ];
	}

	$oDb = create_conex();

	$where = "vd.id_articulo = $id_articulo";
	if ( !empty($fecha_desde) ) $where .= " AND v.fecha >= '$fecha_desde'";
	if ( !empty($fecha_hasta) ) $where .= " AND v.fecha <= '$fecha_hasta'";

	$sql = "SELECT v.folio, v.fecha, v.tipomov, tm.nombre AS tipomov_nombre,
				   vd.cantidad, vd.existencia_actual, vd.existencia_despues,
				   v.quienentrega, v.quienrecibe, v.observaciones
			FROM vales_detalle vd
			INNER JOIN vales v ON v.id = vd.id_vale
			INNER JOIN tipos_movimiento tm ON tm.id = v.id_tipomov
			WHERE $where
			ORDER BY v.fecha ASC, v.id ASC";

	if ( $oDb->query($sql) ) {
		$rows = [];
		while ( $row = $oDb->getrow() ) {
			$rows[] = $row;
		}

		// Obtener datos del artículo
		$oDb->bind_params("SELECT codigo, descripcion, familia FROM articulos WHERE id = ?", [$id_articulo]);
		$articulo = $oDb->getrow();

		$result = [
			'result' => true,
			'data' => $rows,
			'articulo' => $articulo ?: null
		];
	} else {
		$result = [ 'result' => false, 'message' => 'Error al generar reporte' ];
	}

	$oDb->Close();
	return $result;
}

function ReporteValesPeriodo() {
	$fecha_desde = trim( filter_post('fecha_desde') );
	$fecha_hasta = trim( filter_post('fecha_hasta') );
	$tipo = trim( filter_post('tipo') );

	$oDb = create_conex();

	$where = "1=1";
	if ( !empty($fecha_desde) ) $where .= " AND v.fecha >= '$fecha_desde'";
	if ( !empty($fecha_hasta) ) $where .= " AND v.fecha <= '$fecha_hasta'";
	if ( !empty($tipo) ) $where .= " AND v.tipomov = '$tipo'";

	$sql = "SELECT v.id, v.folio, v.fecha, v.tipomov, tm.nombre AS tipomov_nombre,
				   v.quienentrega, v.quienrecibe, v.descripcion_trabajo, v.estatus,
				   COALESCE((SELECT COUNT(*) FROM vales_detalle WHERE id_vale = v.id),0) AS partidas,
				   COALESCE((SELECT SUM(cantidad) FROM vales_detalle WHERE id_vale = v.id),0) AS total_cantidad
			FROM vales v
			INNER JOIN tipos_movimiento tm ON tm.id = v.id_tipomov
			WHERE $where
			ORDER BY v.fecha DESC, v.id DESC";

	if ( $oDb->query($sql) ) {
		$rows = [];
		while ( $row = $oDb->getrow() ) {
			$rows[] = $row;
		}
		$result = [ 'result' => true, 'data' => $rows ];
	} else {
		$result = [ 'result' => false, 'message' => 'Error al generar reporte' ];
	}

	$oDb->Close();
	return $result;
}

function ReporteResumen() {
	$fecha_desde = trim( filter_post('fecha_desde') );
	$fecha_hasta = trim( filter_post('fecha_hasta') );

	$oDb = create_conex();

	$where = "1=1";
	if ( !empty($fecha_desde) ) $where .= " AND v.fecha >= '$fecha_desde'";
	if ( !empty($fecha_hasta) ) $where .= " AND v.fecha <= '$fecha_hasta'";

	// Totales de entradas y salidas por artículo
	$sql = "SELECT vd.id_articulo, a.codigo, a.descripcion, a.familia,
				   SUM(CASE WHEN v.tipomov = 'E' THEN vd.cantidad ELSE 0 END) AS total_entradas,
				   SUM(CASE WHEN v.tipomov = 'S' THEN vd.cantidad ELSE 0 END) AS total_salidas,
				   COUNT(DISTINCT CASE WHEN v.tipomov = 'E' THEN v.id END) AS vales_entrada,
				   COUNT(DISTINCT CASE WHEN v.tipomov = 'S' THEN v.id END) AS vales_salida
			FROM vales_detalle vd
			INNER JOIN vales v ON v.id = vd.id_vale
			INNER JOIN articulos a ON a.id = vd.id_articulo
			WHERE $where
			GROUP BY vd.id_articulo, a.codigo, a.descripcion, a.familia
			ORDER BY a.codigo ASC";

	if ( $oDb->query($sql) ) {
		$rows = [];
		while ( $row = $oDb->getrow() ) {
			$rows[] = $row;
		}

		// Totales generales
		$totalEntradas = 0;
		$totalSalidas = 0;
		$totalValesE = 0;
		$totalValesS = 0;
		foreach ( $rows as $r ) {
			$totalEntradas += floatval($r['total_entradas']);
			$totalSalidas += floatval($r['total_salidas']);
			$totalValesE += intval($r['vales_entrada']);
			$totalValesS += intval($r['vales_salida']);
		}

		$result = [
			'result' => true,
			'data' => $rows,
			'totales' => [
				'total_entradas' => $totalEntradas,
				'total_salidas' => $totalSalidas,
				'vales_entrada' => $totalValesE,
				'vales_salida' => $totalValesS
			]
		];
	} else {
		$result = [ 'result' => false, 'message' => 'Error al generar reporte' ];
	}

	$oDb->Close();
	return $result;
}

function ReporteMovimientosMes() {
	$oDb = create_conex();
	$mes = trim( filter_post('mes') );

	if ( empty($mes) ) {
		$mes = date('Y-m');
	}

	$parts = explode('-', $mes);
	$anio = intval($parts[0]);
	$mesNum = intval($parts[1]);

	$sql = "SELECT v.folio, v.fecha, v.tipomov, tm.nombre AS tipomov_nombre,
				   v.descripcion_trabajo, v.quienentrega, v.quienrecibe, v.estatus,
				   vd.codigo, vd.producto, vd.unidad, vd.cantidad
			FROM vales v
			INNER JOIN tipos_movimiento tm ON tm.id = v.id_tipomov
			INNER JOIN vales_detalle vd ON vd.id_vale = v.id
			WHERE YEAR(v.fecha) = $anio AND MONTH(v.fecha) = $mesNum
			ORDER BY v.fecha ASC, v.id ASC, vd.codigo ASC";

	$rows = [];
	$totalEntradas = 0;
	$totalSalidas = 0;
	$totalVales = 0;
	$totalPiezas = 0;

	if ( $oDb->query($sql) ) {
		while ( $row = $oDb->getrow() ) {
			$rows[] = $row;
			$cant = floatval($row['cantidad']);
			$totalPiezas += $cant;
			if ( $row['tipomov'] === 'E' ) {
				$totalEntradas += $cant;
			} else {
				$totalSalidas += $cant;
			}
		}
		$totalVales = count(array_unique(array_map(function($r) { return $r['folio']; }, $rows)));
	}

	$oDb->Close();
	return [
		'result' => true,
		'data' => $rows,
		'totales' => [
			'total_entradas' => $totalEntradas,
			'total_salidas' => $totalSalidas,
			'total_vales' => $totalVales,
			'total_piezas' => $totalPiezas
		]
	];
}
?>
