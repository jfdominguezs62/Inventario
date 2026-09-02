<?php
include ('../constants.php');
Constants::setpath_root("../");
Constants::create_filejs(true);

include (Constants::getpath_root().'config.php');
include (Constants::getpath_tweb().'core.php');

$oSession = new TSession(APP_SESSION);
$oSession->lExeError = false;
if (!$oSession->Valid()) {
    header("Location: ../index.php");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) { echo "ID no válido"; exit; }

require Constants::getpath_root() . 'config_db.php';

function getValeForPrint($id) {
    $oDb = create_conex();
    $sql = "SELECT v.*, tm.nombre AS tipomov_nombre, tm.signo
            FROM vales v
            INNER JOIN tipos_movimiento tm ON tm.id = v.id_tipomov
            WHERE v.id = ? LIMIT 1";
    if ( !$oDb->bind_params($sql, [$id]) ) {
        $oDb->Close();
        return null;
    }
    $vale = $oDb->getrow();
    if ( !$vale ) {
        $oDb->Close();
        return null;
    }
    $sqlDet = "SELECT vd.* FROM vales_detalle vd WHERE vd.id_vale = ? ORDER BY vd.id";
    if ( $oDb->bind_params($sqlDet, [$id]) ) {
        $detalles = [];
        while ( $row = $oDb->getrow() ) {
            $detalles[] = $row;
        }
        $vale['detalles'] = $detalles;
    } else {
        $vale['detalles'] = [];
    }
    $oDb->Close();
    return $vale;
}

$data = getValeForPrint($id);
if (!$data) {
    echo "Vale no encontrado";
    exit;
}
$v = $data;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Vale: <?php echo htmlspecialchars($v['folio']); ?></title>
    <link rel="stylesheet" href="<?php echo Constants::getpath_tweb() . 'libs/bootstrap-5.3.3/css/bootstrap.min.css'; ?>">
    <link rel="stylesheet" href="<?php echo Constants::getpath_tweb() . 'libs/fontawesome.6.4.2/css/all.min.css'; ?>">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        body { font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .header-box { border-bottom: 3px solid #007bff; padding-bottom: 15px; margin-bottom: 20px; }
        .vale-title { font-size: 1.8rem; font-weight: 800; color: #007bff; }
        .folio-badge { font-size: 1.3rem; font-weight: 700; }
        .section-label { font-weight: 700; color: #495057; }
        .table-detail th { background-color: #007bff; color: #fff; font-size: 0.85rem; }
        .table-detail td { font-size: 0.9rem; vertical-align: middle; }
        .total-row { font-size: 1rem; font-weight: 700; background-color: #f8f9fa; }
        .firmas { margin-top: 40px; }
        .firmas > div { border-top: 1px solid #333; padding-top: 8px; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print mb-3">
            <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print me-1"></i> Imprimir</button>
            <button class="btn btn-secondary" onclick="window.close()">Cerrar</button>
        </div>

        <div class="header-box d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center mb-2 mb-md-0">
                <img src="<?php echo Constants::getpath_images() . 'logoEsapah.png'; ?>" alt="Logo" style="max-height: 70px; margin-right: 15px;">
                <div>
                    <div class="vale-title">VALE DE <?php echo htmlspecialchars($v['tipomov'] == 'E' ? 'ENTRADA' : 'SALIDA'); ?></div>
                    <div class="text-muted">Control de Inventario</div>
                </div>
            </div>
            <div class="text-end mt-3 mt-md-0">
                <div class="folio-badge text-primary"><?php echo htmlspecialchars($v['folio']); ?></div>
                <div class="text-muted"><?php echo htmlspecialchars($v['fecha']); ?></div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <div class="section-label">Quién Entrega:</div>
                <div><?php echo htmlspecialchars($v['quienentrega'] ?: '---'); ?></div>
            </div>
            <div class="col-md-6">
                <div class="section-label">Quién Recibe:</div>
                <div><?php echo htmlspecialchars($v['quienrecibe'] ?: '---'); ?></div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="section-label">Descripción del Trabajo:</div>
                <div><?php echo nl2br(htmlspecialchars($v['descripcion_trabajo'] ?: '---')); ?></div>
            </div>
        </div>

        <table class="table table-bordered table-detail">
            <thead>
                <tr>
                    <th>#</th>
                    <th>CÓDIGO</th>
                    <th>PRODUCTO</th>
                    <th>CATEGORÍA</th>
                    <th>UNIDAD</th>
                    <th class="text-end">CANTIDAD</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total = 0;
                if (!empty($v['detalles'])) {
                    $i = 1;
                    foreach ($v['detalles'] as $d) {
                        $total += floatval($d['cantidad']);
                        echo '<tr>';
                        echo '<td>' . $i++ . '</td>';
                        echo '<td>' . htmlspecialchars($d['codigo']) . '</td>';
                        echo '<td>' . htmlspecialchars($d['producto']) . '</td>';
                        echo '<td>' . htmlspecialchars($d['categoria']) . '</td>';
                        echo '<td>' . htmlspecialchars($d['unidad']) . '</td>';
                        echo '<td class="text-end fw-bold">' . number_format(floatval($d['cantidad']), 2) . '</td>';
                        echo '</tr>';
                    }
                }
                ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="5" class="text-end">TOTAL PIEZAS:</td>
                    <td class="text-end"><?php echo number_format($total, 2); ?></td>
                </tr>
            </tfoot>
        </table>

        <?php if (!empty($v['observaciones'])): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="section-label">Observaciones:</div>
                <div><?php echo nl2br(htmlspecialchars($v['observaciones'])); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row firmas text-center">
            <div class="col-4 offset-1">Entrega</div>
            <div class="col-4 offset-2">Recibe</div>
        </div>
    </div>
</body>
</html>
