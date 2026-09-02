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

require_once Constants::getpath_root() . 'config_db.php';

function listArticulosForPrint() {
    $oDb = create_conex();
    $sql = "SELECT id, codigo, descripcion, COALESCE(existenciainicial,0) AS existenciainicial,
                   COALESCE(entradas,0) AS entradas, COALESCE(salidas,0) AS salidas,
                   COALESCE(existencia,0) AS existencia
            FROM articulos
            WHERE estatus = 1
            ORDER BY codigo ASC";
    $rows = [];
    if ( $oDb->query($sql) ) {
        while ( $row = $oDb->getrow() ) {
            $rows[] = $row;
        }
    }
    $oDb->Close();
    return $rows;
}

$articulos = listArticulosForPrint();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hoja de Inventario Físico</title>
    <link rel="stylesheet" href="<?php echo Constants::getpath_tweb() . 'libs/bootstrap-5.3.3/css/bootstrap.min.css'; ?>">
    <link rel="stylesheet" href="<?php echo Constants::getpath_tweb() . 'libs/fontawesome.6.4.2/css/all.min.css'; ?>">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { size: landscape; margin: 1cm; }
        }
        body { font-family: 'Segoe UI', sans-serif; padding: 15px; font-size: 12px; }
        .header-box { border-bottom: 3px solid #28a745; padding-bottom: 10px; margin-bottom: 15px; }
        .header-title { font-size: 1.5rem; font-weight: 800; color: #28a745; }
        table { font-size: 11px; }
        thead th { background-color: #28a745 !important; color: #fff !important; font-size: 11px; text-align: center; vertical-align: middle !important; border: 1px solid #1e7e34 !important; }
        td { border: 1px solid #dee2e6 !important; vertical-align: middle !important; padding: 3px 5px !important; }
        .col-fisica { background-color: #fff; min-width: 100px; }
        .celda-escritura { border-bottom: 1px solid #333 !important; min-height: 20px; }
        .fila-par { background-color: #f8f9fa; }
        .footer-note { margin-top: 20px; font-size: 11px; }
        .firma-line { width: 200px; border-top: 1px solid #333; margin-top: 40px; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="no-print mb-3">
        <button class="btn btn-success" onclick="window.print()"><i class="fas fa-print me-1"></i> Imprimir</button>
        <button class="btn btn-secondary" onclick="window.close()">Cerrar</button>
    </div>

    <div class="header-box d-flex justify-content-between align-items-center flex-wrap">
        <div class="d-flex align-items-center mb-2 mb-md-0">
            <img src="<?php echo Constants::getpath_images() . 'logoEsapah.png'; ?>" alt="Logo" style="max-height: 70px; margin-right: 15px;">
            <div>
                <div class="header-title"><i class="fas fa-clipboard-list me-2"></i>HOJA DE INVENTARIO FÍSICO</div>
                <div class="text-muted">Control de Inventario</div>
            </div>
        </div>
        <div class="text-end mt-3 mt-md-0">
            <div class="fw-bold">Fecha: <?php echo date('d/m/Y'); ?></div>
            <div class="text-muted">Realizado por: _________________________</div>
        </div>
    </div>

    <table class="table table-bordered table-sm w-100">
        <thead>
            <tr>
                <th width="40">#</th>
                <th width="100">CÓDIGO</th>
                <th>DESCRIPCIÓN</th>
                <th width="80">EXIST.<br>INICIAL</th>
                <th width="70">ENTRADAS</th>
                <th width="70">SALIDAS</th>
                <th width="80">EXIST.<br>ACTUAL</th>
                <th width="100" class="col-fisica">EXIST.<br>FÍSICA</th>
                <th width="60">DIF.</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_inicial = 0;
            $total_entradas = 0;
            $total_salidas = 0;
            $total_actual = 0;
            $i = 1;
            foreach ($articulos as $a): 
                $total_inicial += floatval($a['existenciainicial']);
                $total_entradas += floatval($a['entradas']);
                $total_salidas += floatval($a['salidas']);
                $total_actual += floatval($a['existencia']);
                $class = ($i % 2 == 0) ? 'fila-par' : '';
            ?>
            <tr class="<?php echo $class; ?>">
                <td class="text-center"><?php echo $i++; ?></td>
                <td class="fw-bold"><?php echo htmlspecialchars($a['codigo']); ?></td>
                <td><?php echo htmlspecialchars($a['descripcion']); ?></td>
                <td class="text-end"><?php echo number_format(floatval($a['existenciainicial']), 2); ?></td>
                <td class="text-end"><?php echo number_format(floatval($a['entradas']), 2); ?></td>
                <td class="text-end"><?php echo number_format(floatval($a['salidas']), 2); ?></td>
                <td class="text-end fw-bold"><?php echo number_format(floatval($a['existencia']), 2); ?></td>
                <td class="col-fisica celda-escritura">&nbsp;</td>
                <td class="text-end celda-escritura">&nbsp;</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
                <tr class="fw-bold" style="background-color: #e9ecef;">
                    <td colspan="4" class="text-end">TOTALES:</td>
                    <td class="text-end"><?php echo number_format($total_inicial, 2); ?></td>
                    <td class="text-end"><?php echo number_format($total_entradas, 2); ?></td>
                    <td class="text-end"><?php echo number_format($total_salidas, 2); ?></td>
                    <td class="text-end"><?php echo number_format($total_actual, 2); ?></td>
                <td class="col-fisica celda-escritura">&nbsp;</td>
                <td class="celda-escritura">&nbsp;</td>
            </tr>
        </tfoot>
    </table>

    <div class="row footer-note">
        <div class="col-4 text-center">
            <div class="firma-line" style="margin:0 auto;">Elaboró</div>
        </div>
        <div class="col-4 text-center">
            <div class="firma-line" style="margin:0 auto;">Autorizó</div>
        </div>
        <div class="col-4 text-center">
            <div class="firma-line" style="margin:0 auto;">Recibió</div>
        </div>
    </div>

    <div class="small text-muted mt-3">
        <i class="fas fa-info-circle me-1"></i> Capture la existencia física contada. La diferencia se calcula como: Exist. Física - Exist. Actual.
    </div>
</body>
</html>
