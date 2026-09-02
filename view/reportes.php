<?php
include ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( true );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_tweb() . 'core.php' );
include ( Constants::getpath_root() . 'helpers.php' );

$oSession = new TSession( APP_SESSION );
$oSession->lExeError = false;
if ( !$oSession->Valid() ) {
  header("Location: ../index.php");
  exit;
}

$oWeb = new TWeb( APP_TITLE . ' - Reportes' );
$oWeb->lAwesome = true;
$oWeb->SetIcon( IMAGE_PATH . 'favicon.ico' );
$oWeb->SetFontFamily( FONT_FAMILY );
$oWeb->Activate();

$nombreUsuario = $oSession->GetVar('usuario');
$rolUsuario = $oSession->GetVar('rol') ?: 'operador';
checkAcceso(['admin', 'operador', 'supervisor', 'consultor']);
?>

<style>
  .nav-tabs .nav-link { font-weight: 700; color: #495057; border: none; border-bottom: 3px solid transparent; }
  .nav-tabs .nav-link.active { color: #007bff; border-bottom-color: #007bff; background: none; }
  .nav-tabs .nav-link:hover { border-bottom-color: #adb5bd; }
  .tab-content { min-height: 400px; }
  .card-report { border-radius: 16px; }
  .resumen-card { border-radius: 12px; }
  .resumen-card .card-body { padding: 1rem 1.25rem; }
  .resumen-numero { font-size: 1.6rem; font-weight: 800; }
  .total-box { background: #f8f9fa; border-radius: 12px; padding: 15px; }
  #tabla-mov-articulos tbody tr { cursor: pointer; }
</style>

<div class="container-fluid p-0">
  <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm py-3 px-4">
    <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
      <img src="<?php echo Constants::getpath_images() . 'logoEsapah.png'; ?>" alt="Logo" style="max-height: 40px; margin-right: 10px;">
      <i class="fas fa-chart-bar me-2"></i> Control de Inventario - Reportes
    </a>
    <div class="ms-auto d-flex align-items-center">
      <a href="menu.php" class="text-white me-3 fw-bold small">
        <i class="fas fa-arrow-left me-1"></i> Volver al Menú
      </a>
      <span class="text-white me-3">
        <i class="fas fa-user-circle me-1"></i> <span class="fw-bold"><?php echo htmlspecialchars($nombreUsuario); ?></span>
        <span class="badge badge-light ms-1"><?php echo strtoupper($rolUsuario); ?></span>
      </span>
      <button class="btn btn-outline-light btn-sm fw-bold" onclick="logout()">Cerrar Sesión <i class="fas fa-sign-out-alt ms-1"></i></button>
    </div>
  </nav>

  <div class="container-fluid my-4 px-4">
    <h4 class="fw-bold text-dark mb-4">
      <i class="fas fa-chart-pie me-2 text-secondary"></i> Reportes de Inventario
    </h4>

    <ul class="nav nav-tabs mb-4" id="tabReportes" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" id="tab-existencia-link" data-bs-toggle="tab" href="#tab-existencia" role="tab">
          <i class="fas fa-boxes me-1"></i> Existencia Actual
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="tab-movimientos-link" data-bs-toggle="tab" href="#tab-movimientos" role="tab">
          <i class="fas fa-exchange-alt me-1"></i> Movimientos por Artículo
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="tab-vales-link" data-bs-toggle="tab" href="#tab-vales" role="tab">
          <i class="fas fa-file-invoice me-1"></i> Vales por Período
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="tab-resumen-link" data-bs-toggle="tab" href="#tab-resumen" role="tab">
          <i class="fas fa-calculator me-1"></i> Resumen E/S
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="tab-mes-link" data-bs-toggle="tab" href="#tab-mes" role="tab">
          <i class="fas fa-calendar-alt me-1"></i> Movimientos del Mes
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="tab-historico-link" data-bs-toggle="tab" href="#tab-historico" role="tab">
          <i class="fas fa-history me-1"></i> Histórico Físico
        </a>
      </li>
    </ul>

    <div class="tab-content" id="tabReportesContent">

      <!-- Reporte 1: Existencia Actual -->
      <div class="tab-pane fade show active" id="tab-existencia" role="tabpanel">
        <div class="card border-0 shadow-lg card-report">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="fw-bold mb-0"><i class="fas fa-boxes me-2 text-primary"></i>Existencia Actual de Artículos</h5>
              <button class="btn btn-sm btn-outline-secondary" onclick="imprimirTabla('tabla-existencia')"><i class="fas fa-print me-1"></i> Imprimir</button>
            </div>
            <table id="tabla-existencia" class="table table-hover table-striped w-100">
              <thead class="bg-primary text-white">
                <tr>
                  <th>CÓDIGO</th>
                  <th>DESCRIPCIÓN</th>
                  <th>FAMILIA</th>
                  <th>U/MEDIDA</th>
                  <th>EXIST. INICIAL</th>
                  <th>ENTRADAS</th>
                  <th>SALIDAS</th>
                  <th>EXIST. ACTUAL</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Reporte 2: Movimientos por Artículo -->
      <div class="tab-pane fade" id="tab-movimientos" role="tabpanel">
        <div class="card border-0 shadow-lg card-report">
          <div class="card-body p-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-exchange-alt me-2 text-info"></i>Movimientos por Artículo</h5>
            <div class="row align-items-end mb-3">
              <div class="col-md-3 mb-2">
                <label class="small fw-bold">Artículo</label>
                <div class="input-group">
                  <input type="text" class="form-control" id="mov-articulo-txt" placeholder="Escriba código o descripción..." autocomplete="off">
                  <button class="btn btn-outline-info" type="button" onclick="abrirSelectorArticulo()" title="Buscar artículo"><i class="fas fa-search"></i></button>
                </div>
                <input type="hidden" id="mov-articulo" value="">
              </div>
              <div class="col-md-2 mb-2">
                <label class="small fw-bold">Desde</label>
                <input type="date" class="form-control" id="mov-desde">
              </div>
              <div class="col-md-2 mb-2">
                <label class="small fw-bold">Hasta</label>
                <input type="date" class="form-control" id="mov-hasta">
              </div>
              <div class="col-md-2 mb-2">
                <button class="btn btn-info w-100 fw-bold" onclick="cargarMovimientos()"><i class="fas fa-search me-1"></i> Consultar</button>
              </div>
            </div>
            <div id="mov-info" class="alert alert-info py-2 small" style="display:none;"></div>
            <table id="tabla-movimientos" class="table table-hover table-striped w-100">
              <thead class="bg-info text-white">
                <tr>
                  <th>FOLIO</th>
                  <th>FECHA</th>
                  <th>TIPO</th>
                  <th>CANTIDAD</th>
                  <th>EXIST. ANTES</th>
                  <th>EXIST. DESPUÉS</th>
                  <th>ENTREGA</th>
                  <th>RECIBE</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Reporte 3: Vales por Período -->
      <div class="tab-pane fade" id="tab-vales" role="tabpanel">
        <div class="card border-0 shadow-lg card-report">
          <div class="card-body p-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-file-invoice me-2 text-warning"></i>Vales por Período</h5>
            <div class="row align-items-end mb-3">
              <div class="col-md-2 mb-2">
                <label class="small fw-bold">Desde</label>
                <input type="date" class="form-control" id="vales-desde">
              </div>
              <div class="col-md-2 mb-2">
                <label class="small fw-bold">Hasta</label>
                <input type="date" class="form-control" id="vales-hasta">
              </div>
              <div class="col-md-2 mb-2">
                <label class="small fw-bold">Tipo</label>
                <select class="form-control" id="vales-tipo">
                  <option value="">TODOS</option>
                  <option value="E">ENTRADA</option>
                  <option value="S">SALIDA</option>
                </select>
              </div>
              <div class="col-md-2 mb-2">
                <button class="btn btn-warning w-100 fw-bold" onclick="cargarValesPeriodo()"><i class="fas fa-search me-1"></i> Consultar</button>
              </div>
            </div>
            <table id="tabla-vales-periodo" class="table table-hover table-striped w-100">
              <thead class="bg-warning text-dark">
                <tr>
                  <th>FOLIO</th>
                  <th>FECHA</th>
                  <th>TIPO</th>
                  <th>PARTIDAS</th>
                  <th>TOTAL PZAS</th>
                  <th>ENTREGA</th>
                  <th>RECIBE</th>
                  <th>DESCRIPCIÓN</th>
                  <th>ESTATUS</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Reporte 4: Resumen Entradas/Salidas -->
      <div class="tab-pane fade" id="tab-resumen" role="tabpanel">
        <div class="card border-0 shadow-lg card-report">
          <div class="card-body p-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-calculator me-2 text-success"></i>Resumen de Entradas y Salidas</h5>
            <div class="row align-items-end mb-3">
              <div class="col-md-2 mb-2">
                <label class="small fw-bold">Desde</label>
                <input type="date" class="form-control" id="res-desde">
              </div>
              <div class="col-md-2 mb-2">
                <label class="small fw-bold">Hasta</label>
                <input type="date" class="form-control" id="res-hasta">
              </div>
              <div class="col-md-2 mb-2">
                <button class="btn btn-success w-100 fw-bold" onclick="cargarResumen()"><i class="fas fa-search me-1"></i> Consultar</button>
              </div>
            </div>

            <div id="resumen-totales" class="row mb-3" style="display:none;">
              <div class="col-md-3 mb-2">
                <div class="card border-0 shadow-sm resumen-card bg-success text-white">
                  <div class="card-body text-center">
                    <div class="small fw-bold"><i class="fas fa-sign-in-alt me-1"></i>Total Entradas</div>
                    <div class="resumen-numero" id="res-total-entradas">0.00</div>
                  </div>
                </div>
              </div>
              <div class="col-md-3 mb-2">
                <div class="card border-0 shadow-sm resumen-card bg-danger text-white">
                  <div class="card-body text-center">
                    <div class="small fw-bold"><i class="fas fa-sign-out-alt me-1"></i>Total Salidas</div>
                    <div class="resumen-numero" id="res-total-salidas">0.00</div>
                  </div>
                </div>
              </div>
              <div class="col-md-3 mb-2">
                <div class="card border-0 shadow-sm resumen-card bg-info text-white">
                  <div class="card-body text-center">
                    <div class="small fw-bold"><i class="fas fa-file-invoice me-1"></i>Vales Entrada</div>
                    <div class="resumen-numero" id="res-vales-entrada">0</div>
                  </div>
                </div>
              </div>
              <div class="col-md-3 mb-2">
                <div class="card border-0 shadow-sm resumen-card bg-warning text-dark">
                  <div class="card-body text-center">
                    <div class="small fw-bold"><i class="fas fa-file-invoice me-1"></i>Vales Salida</div>
                    <div class="resumen-numero" id="res-vales-salida">0</div>
                  </div>
                </div>
              </div>
            </div>

            <table id="tabla-resumen" class="table table-hover table-striped w-100">
              <thead class="bg-success text-white">
                <tr>
                  <th>CÓDIGO</th>
                  <th>DESCRIPCIÓN</th>
                  <th>FAMILIA</th>
                  <th>TOTAL ENTRADAS</th>
                  <th>TOTAL SALIDAS</th>
                  <th>VALES E</th>
                  <th>VALES S</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Reporte 5: Movimientos del Mes -->
      <div class="tab-pane fade" id="tab-mes" role="tabpanel">
        <div class="card border-0 shadow-lg card-report">
          <div class="card-body p-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-calendar-alt me-2 text-primary"></i>Movimientos del Mes</h5>
            <div class="row align-items-end mb-3">
              <div class="col-md-3 mb-2">
                <label class="small fw-bold">Mes</label>
                <input type="month" class="form-control" id="mes-movimientos">
              </div>
              <div class="col-md-2 mb-2">
                <button class="btn btn-primary w-100 fw-bold" onclick="cargarMovimientosMes()"><i class="fas fa-search me-1"></i> Consultar</button>
              </div>
              <div class="col-md-2 mb-2">
                <button class="btn btn-outline-secondary" onclick="imprimirMovimientosMes()"><i class="fas fa-print me-1"></i> Imprimir</button>
              </div>
            </div>

            <div id="mes-totales" class="row mb-3" style="display:none;">
              <div class="col-md-3 mb-2">
                <div class="card border-0 shadow-sm resumen-card bg-success text-white">
                  <div class="card-body text-center">
                    <div class="small fw-bold"><i class="fas fa-sign-in-alt me-1"></i>Total Entradas</div>
                    <div class="resumen-numero" id="mes-total-entradas">0.00</div>
                  </div>
                </div>
              </div>
              <div class="col-md-3 mb-2">
                <div class="card border-0 shadow-sm resumen-card bg-danger text-white">
                  <div class="card-body text-center">
                    <div class="small fw-bold"><i class="fas fa-sign-out-alt me-1"></i>Total Salidas</div>
                    <div class="resumen-numero" id="mes-total-salidas">0.00</div>
                  </div>
                </div>
              </div>
              <div class="col-md-3 mb-2">
                <div class="card border-0 shadow-sm resumen-card bg-info text-white">
                  <div class="card-body text-center">
                    <div class="small fw-bold"><i class="fas fa-file-invoice me-1"></i>Total Vales</div>
                    <div class="resumen-numero" id="mes-total-vales">0</div>
                  </div>
                </div>
              </div>
              <div class="col-md-3 mb-2">
                <div class="card border-0 shadow-sm resumen-card bg-warning text-dark">
                  <div class="card-body text-center">
                    <div class="small fw-bold"><i class="fas fa-boxes me-1"></i>Total Piezas</div>
                    <div class="resumen-numero" id="mes-total-piezas">0.00</div>
                  </div>
                </div>
              </div>
            </div>

            <table id="tabla-mes-movimientos" class="table table-hover table-striped w-100">
              <thead class="bg-primary text-white">
                <tr>
                  <th>FOLIO</th>
                  <th>FECHA</th>
                  <th>TIPO</th>
                  <th>CÓDIGO</th>
                  <th>PRODUCTO</th>
                  <th>CANTIDAD</th>
                  <th>U/MEDIDA</th>
                  <th>ENTREGA</th>
                  <th>RECIBE</th>
                  <th>ESTATUS</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Reporte 6: Histórico Existencia Física -->
      <div class="tab-pane fade" id="tab-historico" role="tabpanel">
        <div class="card border-0 shadow-lg card-report">
          <div class="card-body p-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-history me-2 text-secondary"></i>Histórico de Existencia Física</h5>
            <div class="row align-items-end mb-3">
              <div class="col-md-3 mb-2">
                <label class="small fw-bold">Mes</label>
                <select class="form-control" id="hist-mes">
                  <option value="">Todos los meses</option>
                </select>
              </div>
              <div class="col-md-2 mb-2">
                <button class="btn btn-secondary w-100 fw-bold" onclick="cargarHistorico()"><i class="fas fa-search me-1"></i> Consultar</button>
              </div>
              <div class="col-md-2 mb-2">
                <button class="btn btn-outline-secondary" onclick="imprimirHistorico()"><i class="fas fa-print me-1"></i> Imprimir</button>
              </div>
            </div>

            <div id="hist-totales" class="row mb-3" style="display:none;">
              <div class="col-md-4 mb-2">
                <div class="card border-0 shadow-sm resumen-card bg-success text-white">
                  <div class="card-body text-center">
                    <div class="small fw-bold"><i class="fas fa-boxes me-1"></i>Total Artículos</div>
                    <div class="resumen-numero" id="hist-total-articulos">0</div>
                  </div>
                </div>
              </div>
              <div class="col-md-4 mb-2">
                <div class="card border-0 shadow-sm resumen-card bg-primary text-white">
                  <div class="card-body text-center">
                    <div class="small fw-bold"><i class="fas fa-balance-scale me-1"></i>Existencia Física</div>
                    <div class="resumen-numero" id="hist-total-fisica">0.00</div>
                  </div>
                </div>
              </div>
              <div class="col-md-4 mb-2">
                <div class="card border-0 shadow-sm resumen-card bg-info text-white">
                  <div class="card-body text-center">
                    <div class="small fw-bold"><i class="fas fa-exchange-alt me-1"></i>Entradas / Salidas</div>
                    <div class="resumen-numero" id="hist-total-mov">0 / 0</div>
                  </div>
                </div>
              </div>
            </div>

            <table id="tabla-historico" class="table table-hover table-striped w-100">
              <thead class="bg-secondary text-white">
                <tr>
                  <th>PERÍODO</th>
                  <th>CÓDIGO</th>
                  <th>DESCRIPCIÓN</th>
                  <th>FAMILIA</th>
                  <th>U/MEDIDA</th>
                  <th>EXIST. FÍSICA</th>
                  <th>ENTRADAS</th>
                  <th>SALIDAS</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>

    </div>

    <!-- Modal selector de artículo -->
    <div class="modal fade" id="modal-mov-articulo" tabindex="-1" aria-labelledby="modalMovArticuloLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header bg-info text-white">
            <h5 class="modal-title" id="modalMovArticuloLabel"><i class="fas fa-boxes me-2"></i>Seleccionar Artículo</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <table id="tabla-mov-articulos" class="table table-hover table-striped w-100">
              <thead class="bg-info text-white">
                <tr>
                  <th>CÓDIGO</th>
                  <th>DESCRIPCIÓN</th>
                  <th>FAMILIA</th>
                  <th>EXIST.</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  var tabExistencia, tabMovimientos, tabValesPeriodo, tabResumen, tabMesMovimientos, tabHistorico;
  var articulosMov = [];
  var tablaMovArticulos;

  $(function() {
    var hoy = new Date().toISOString().substr(0, 10);
    var mesPasado = new Date(); mesPasado.setMonth(mesPasado.getMonth()-1);
    document.getElementById('mov-desde').value = mesPasado.toISOString().substr(0, 10);
    document.getElementById('mov-hasta').value = hoy;
    document.getElementById('vales-desde').value = mesPasado.toISOString().substr(0, 10);
    document.getElementById('vales-hasta').value = hoy;
    document.getElementById('res-desde').value = mesPasado.toISOString().substr(0, 10);
    document.getElementById('res-hasta').value = hoy;
    document.getElementById('mes-movimientos').value = new Date().toISOString().substr(0, 7);

    // Cargar artículos para el selector (DataTable en modal)
    MsgServer(path.model + 'reportes.php', function(dat) {
      if (dat.result && dat.data) {
        articulosMov = dat.data;
        inicializarTablaArticulos();
      }
    }, { action: 'existencia' }, false);

    // Enter en el campo de artículo abre el selector con lo escrito
    $('#mov-articulo-txt').on('keydown', function(e) {
      if (e.key === 'Enter') { e.preventDefault(); abrirSelectorArticulo(); }
    });

    // --- Tabla Existencia ---
    tabExistencia = $('#tabla-existencia').DataTable({
      processing: true, serverSide: false,
      ajax: { url: path.model + 'reportes.php', type: 'POST', data: { action: 'existencia' }, dataSrc: function(j) { return (j.result && j.data) ? j.data : []; } },
      columns: [
        { data: 'codigo', className: 'fw-bold' },
        { data: 'descripcion' },
        { data: 'familia' },
        { data: 'unidadmedida' },
        { data: 'existenciainicial', className: 'text-end', render: function(d) { return formatNumber(d, 2); } },
        { data: 'entradas', className: 'text-end fw-bold text-success', render: function(d) { return formatNumber(d, 2); } },
        { data: 'salidas', className: 'text-end fw-bold text-danger', render: function(d) { return formatNumber(d, 2); } },
        { data: 'existencia', className: 'text-end fw-bold', render: function(d) { var n=parseFloat(d); return n<=0?'<span class="text-danger">'+formatNumber(n,2)+'</span>':formatNumber(n,2); } }
      ],
      language: langDT(), order: [[0,'asc']], pageLength: 25, responsive: true,
      dom: '<"d-flex justify-content-between align-items-center mb-3"lf>t<"d-flex justify-content-between align-items-center mt-3"ip>'
    });

    // --- Tabla Movimientos ---
    tabMovimientos = $('#tabla-movimientos').DataTable({
      processing: true, serverSide: false,
      ajax: { url: path.model + 'reportes.php', type: 'POST', data: function() {
        return { action: 'movimientos', id_articulo: $('#mov-articulo').val(), fecha_desde: $('#mov-desde').val(), fecha_hasta: $('#mov-hasta').val() };
      }, dataSrc: function(j) { return (j.result && j.data) ? j.data : []; } },
      columns: [
        { data: 'folio', className: 'fw-bold' },
        { data: 'fecha' },
        { data: 'tipomov_nombre' },
        { data: 'cantidad', className: 'text-end fw-bold', render: function(d) { return formatNumber(d, 2); } },
        { data: 'existencia_actual', className: 'text-end', render: function(d) { return formatNumber(d, 2); } },
        { data: 'existencia_despues', className: 'text-end', render: function(d) { return formatNumber(d, 2); } },
        { data: 'quienentrega' },
        { data: 'quienrecibe' }
      ],
      language: langDT(), order: [[1,'asc']], pageLength: 25, responsive: true,
      dom: '<"d-flex justify-content-between align-items-center mb-3"lf>t<"d-flex justify-content-between align-items-center mt-3"ip>'
    });

    // --- Tabla Vales por Período ---
    tabValesPeriodo = $('#tabla-vales-periodo').DataTable({
      processing: true, serverSide: false,
      ajax: { url: path.model + 'reportes.php', type: 'POST', data: function() {
        return { action: 'vales_periodo', fecha_desde: $('#vales-desde').val(), fecha_hasta: $('#vales-hasta').val(), tipo: $('#vales-tipo').val() };
      }, dataSrc: function(j) { return (j.result && j.data) ? j.data : []; } },
      columns: [
        { data: 'folio', className: 'fw-bold' },
        { data: 'fecha' },
        { data: 'tipomov_nombre' },
        { data: 'partidas', className: 'text-center' },
        { data: 'total_cantidad', className: 'text-end', render: function(d) { return formatNumber(d, 2); } },
        { data: 'quienentrega' },
        { data: 'quienrecibe' },
        { data: 'descripcion_trabajo' },
        { data: 'estatus', className: 'text-center', render: function(d) { return d=='ACTIVO'?'<span class="badge badge-success px-2">ACTIVO</span>':'<span class="badge badge-secondary px-2">'+d+'</span>'; } }
      ],
      language: langDT(), order: [[1,'desc']], pageLength: 25, responsive: true,
      dom: '<"d-flex justify-content-between align-items-center mb-3"lf>t<"d-flex justify-content-between align-items-center mt-3"ip>'
    });

    // --- Tabla Resumen ---
    tabResumen = $('#tabla-resumen').DataTable({
      processing: true, serverSide: false,
      ajax: { url: path.model + 'reportes.php', type: 'POST', data: function() {
        return { action: 'resumen', fecha_desde: $('#res-desde').val(), fecha_hasta: $('#res-hasta').val() };
      }, dataSrc: function(j) {
        if (j.result && j.totales) {
          $('#res-total-entradas').text(formatNumber(j.totales.total_entradas,2));
          $('#res-total-salidas').text(formatNumber(j.totales.total_salidas,2));
          $('#res-vales-entrada').text(j.totales.vales_entrada);
          $('#res-vales-salida').text(j.totales.vales_salida);
          $('#resumen-totales').show();
        }
        return (j.result && j.data) ? j.data : [];
      } },
      columns: [
        { data: 'codigo', className: 'fw-bold' },
        { data: 'descripcion' },
        { data: 'familia' },
        { data: 'total_entradas', className: 'text-end fw-bold text-success', render: function(d) { return formatNumber(d, 2); } },
        { data: 'total_salidas', className: 'text-end fw-bold text-danger', render: function(d) { return formatNumber(d, 2); } },
        { data: 'vales_entrada', className: 'text-center' },
        { data: 'vales_salida', className: 'text-center' }
      ],
      language: langDT(), order: [[0,'asc']], pageLength: 25, responsive: true,
      dom: '<"d-flex justify-content-between align-items-center mb-3"lf>t<"d-flex justify-content-between align-items-center mt-3"ip>'
    });

    // --- Tabla Movimientos del Mes ---
    tabMesMovimientos = $('#tabla-mes-movimientos').DataTable({
      processing: true, serverSide: false,
      ajax: { url: path.model + 'reportes.php', type: 'POST', data: function() {
        return { action: 'movimientos_mes', mes: $('#mes-movimientos').val() };
      }, dataSrc: function(j) {
        if (j.result && j.totales) {
          $('#mes-total-entradas').text(formatNumber(j.totales.total_entradas,2));
          $('#mes-total-salidas').text(formatNumber(j.totales.total_salidas,2));
          $('#mes-total-vales').text(j.totales.total_vales);
          $('#mes-total-piezas').text(formatNumber(j.totales.total_piezas,2));
          $('#mes-totales').show();
        }
        return (j.result && j.data) ? j.data : [];
      } },
      columns: [
        { data: 'folio', className: 'fw-bold' },
        { data: 'fecha' },
        { data: 'tipomov_nombre', render: function(d, t, row) {
          return row.tipomov=='E' ? '<span class="badge badge-success px-2">'+d+'</span>' : '<span class="badge badge-warning text-dark px-2">'+d+'</span>';
        }},
        { data: 'codigo', className: 'fw-bold' },
        { data: 'producto' },
        { data: 'cantidad', className: 'text-end fw-bold', render: function(d) { return formatNumber(d, 2); } },
        { data: 'unidad' },
        { data: 'quienentrega' },
        { data: 'quienrecibe' },
        { data: 'estatus', className: 'text-center', render: function(d) { return d=='ACTIVO'?'<span class="badge badge-success px-2">ACTIVO</span>':'<span class="badge badge-secondary px-2">'+d+'</span>'; } }
      ],
      language: langDT(), order: [[1,'asc']], pageLength: 25, responsive: true,
      dom: '<"d-flex justify-content-between align-items-center mb-3"lf>t<"d-flex justify-content-between align-items-center mt-3"ip>'
    });

    // --- Tabla Histórico ---
    tabHistorico = $('#tabla-historico').DataTable({
      processing: true, serverSide: false,
      ajax: { url: path.model + 'inventario_fisico.php', type: 'POST', data: function() {
        var val = $('#hist-mes').val();
        var anio = val ? val.split('-')[0] : '';
        var mes = val ? val.split('-')[1] : '';
        return { action: 'consultar_historico', anio: anio, mes: mes };
      }, dataSrc: function(j) {
        if (j.result && j.meses_disponibles) {
          var sel = document.getElementById('hist-mes');
          var actual = sel.value;
          sel.innerHTML = '<option value="">Todos los meses</option>';
          var meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
          j.meses_disponibles.forEach(function(m) {
            var opt = document.createElement('option');
            opt.value = m.anio + '-' + m.mes;
            opt.textContent = meses[parseInt(m.mes)] + ' ' + m.anio;
            sel.appendChild(opt);
          });
          sel.value = actual;
        }
        if (j.result && j.data && j.data.length > 0) {
          var totalF = 0, totalE = 0, totalS = 0;
          j.data.forEach(function(r) { totalF += parseFloat(r.existenciafisica); totalE += parseFloat(r.entradas); totalS += parseFloat(r.salidas); });
          $('#hist-total-articulos').text(j.data.length);
          $('#hist-total-fisica').text(formatNumber(totalF, 2));
          $('#hist-total-mov').text(formatNumber(totalE, 2) + ' / ' + formatNumber(totalS, 2));
          $('#hist-totales').show();
        } else {
          $('#hist-totales').hide();
        }
        return (j.result && j.data) ? j.data : [];
      } },
      columns: [
        { data: null, render: function(d) { return d.anio + '-' + String(d.mes).padStart(2,'0'); }, className: 'fw-bold' },
        { data: 'codigo', className: 'fw-bold' },
        { data: 'descripcion' },
        { data: 'familia' },
        { data: 'unidadmedida' },
        { data: 'existenciafisica', className: 'text-end fw-bold', render: function(d) { return formatNumber(d, 2); } },
        { data: 'entradas', className: 'text-end fw-bold text-success', render: function(d) { return formatNumber(d, 2); } },
        { data: 'salidas', className: 'text-end fw-bold text-danger', render: function(d) { return formatNumber(d, 2); } }
      ],
      language: langDT(), order: [[0,'asc'],[1,'asc']], pageLength: 25, responsive: true,
      dom: '<"d-flex justify-content-between align-items-center mb-3"lf>t<"d-flex justify-content-between align-items-center mt-3"ip>'
    });
  });

  function cargarMovimientos() {
    var id = $('#mov-articulo').val();
    if (!id) { JError('Seleccione un artículo'); return; }
    $('#mov-info').hide();
    tabMovimientos.ajax.reload(function(json) {
      if (json.articulo) {
        $('#mov-info').html('<i class="fas fa-info-circle me-1"></i><strong>Artículo:</strong> ' + json.articulo.codigo + ' - ' + json.articulo.descripcion + ' <span class="ms-3 text-muted">' + json.articulo.familia + '</span>').show();
      }
    });
  }

  function cargarValesPeriodo() { tabValesPeriodo.ajax.reload(); }

  function inicializarTablaArticulos() {
    if (tablaMovArticulos) {
      tablaMovArticulos.clear();
      tablaMovArticulos.rows.add(articulosMov);
      tablaMovArticulos.draw();
      return;
    }
    tablaMovArticulos = $('#tabla-mov-articulos').DataTable({
      data: articulosMov,
      columns: [
        { data: 'codigo', className: 'fw-bold' },
        { data: 'descripcion' },
        { data: 'familia' },
        { data: 'existencia', className: 'text-end', render: function(d) { return formatNumber(d, 2); } }
      ],
      language: langDT(), order: [[0, 'asc']], pageLength: 10, responsive: true,
      dom: '<"d-flex justify-content-between align-items-center mb-2"lf>rt<"d-flex justify-content-between align-items-center mt-2"ip>'
    });
    $('#tabla-mov-articulos tbody').on('click', 'tr', function() {
      var row = tablaMovArticulos.row(this).data();
      if (row) seleccionarArticulo(row);
    });
  }

  function abrirSelectorArticulo() {
    var txt = $('#mov-articulo-txt').val().trim();
    if (tablaMovArticulos) {
      tablaMovArticulos.search(txt).draw();
    }
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-mov-articulo')).show();
  }

  function seleccionarArticulo(row) {
    $('#mov-articulo').val(row.id);
    $('#mov-articulo-txt').val(row.codigo + ' - ' + row.descripcion);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-mov-articulo')).hide();
  }
  function cargarResumen() { tabResumen.ajax.reload(); }

  function cargarMovimientosMes() { tabMesMovimientos.ajax.reload(); }

  function cargarHistorico() { tabHistorico.ajax.reload(); }

  function imprimirHistorico() {
    var data = tabHistorico.rows().data().toArray();
    if (!data.length) { JError('No hay datos para imprimir'); return; }

    var win = window.open('', '_blank');
    var logo = '<?php echo Constants::getpath_images() . "logoEsapah.png"; ?>';
    var bootstrapCss = '<?php echo Constants::getpath_tweb() . 'libs/bootstrap-5.3.3/css/bootstrap.min.css'; ?>';
    var html = '<html><head><title>Histórico Existencia Física</title>';
    html += '<link rel="stylesheet" href="' + bootstrapCss + '">';
    html += '<style>@media print{body{-webkit-print-color-adjust:exact;print-color-adjust:exact}}table{font-size:11px}th{background-color:#6c757d!important;color:#fff!important;padding:6px 8px!important}td{padding:5px 8px!important}</style>';
    html += '</head><body>';
    html += '<div class="text-center mb-3"><img src="' + logo + '" alt="Logo" style="max-height:50px;"><h4 class="mt-2 mb-1">Histórico de Existencia Física</h4>';
    html += '<small class="text-muted">Impreso: ' + new Date().toLocaleDateString() + ' | Total: ' + data.length + ' registros</small></div>';

    html += '<table class="table table-bordered table-sm w-100">';
    html += '<thead><tr><th>PERÍODO</th><th>CÓDIGO</th><th>DESCRIPCIÓN</th><th>FAMILIA</th><th>U/M</th><th>EXIST. FÍSICA</th><th>ENTRADAS</th><th>SALIDAS</th></tr></thead><tbody>';

    for (var i = 0; i < data.length; i++) {
      var r = data[i];
      html += '<tr><td>' + r.anio + '-' + String(r.mes).padStart(2,'0') + '</td><td>' + r.codigo + '</td><td>' + r.descripcion + '</td><td>' + r.familia + '</td><td>' + r.unidadmedida + '</td><td style="text-align:right;font-weight:700">' + formatNumber(r.existenciafisica,2) + '</td><td style="text-align:right;color:#198754;font-weight:700">' + formatNumber(r.entradas,2) + '</td><td style="text-align:right;color:#dc3545;font-weight:700">' + formatNumber(r.salidas,2) + '</td></tr>';
    }

    html += '</tbody></table></body></html>';
    win.document.write(html);
    win.document.close();
    setTimeout(function() { win.print(); }, 500);
  }

  function imprimirTabla(id) {
    var win = window.open('', '_blank');
    var logo = '<?php echo Constants::getpath_images() . "logoEsapah.png"; ?>';
    var bootstrapCss = '<?php echo Constants::getpath_tweb() . 'libs/bootstrap-5.3.3/css/bootstrap.min.css'; ?>';
    var html = '<html><head><title>Reporte</title>';
    html += '<link rel="stylesheet" href="' + bootstrapCss + '">';
    html += '<style>@media print{body{-webkit-print-color-adjust:exact;print-color-adjust:exact}}table{font-size:12px}th{background-color:#007bff!important;color:#fff!important}</style>';
    html += '</head><body>';
    html += '<div class="text-center mb-4"><img src="' + logo + '" alt="Logo" style="max-height: 60px;"><h4 class="mt-2">Reporte de Inventario</h4><small class="text-muted">Fecha: ' + new Date().toLocaleDateString() + '</small></div>';
    html += document.getElementById(id).outerHTML + '</body></html>';
    win.document.write(html);
    win.document.close();
    setTimeout(function() { win.print(); }, 500);
  }

  function imprimirMovimientosMes() {
    var data = tabMesMovimientos.rows().data().toArray();
    if (!data.length) { JError('No hay datos para imprimir'); return; }
    var mes = $('#mes-movimientos').val();
    var partes = mes.split('-');
    var nombreMes = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    var tituloMes = nombreMes[parseInt(partes[1])-1] + ' ' + partes[0];

    var totalE = $('#mes-total-entradas').text();
    var totalS = $('#mes-total-salidas').text();
    var totalV = $('#mes-total-vales').text();
    var totalP = $('#mes-total-piezas').text();

    var win = window.open('', '_blank');
    var logo = '<?php echo Constants::getpath_images() . "logoEsapah.png"; ?>';
    var bootstrapCss = '<?php echo Constants::getpath_tweb() . 'libs/bootstrap-5.3.3/css/bootstrap.min.css'; ?>';
    var html = '<html><head><title>Movimientos del Mes</title>';
    html += '<link rel="stylesheet" href="' + bootstrapCss + '">';
    html += '<style>@media print{body{-webkit-print-color-adjust:exact;print-color-adjust:exact}}table{font-size:11px}th{background-color:#0d6efd!important;color:#fff!important;padding:6px 8px!important}td{padding:5px 8px!important}.totales-box{background:#f8f9fa;border-radius:8px;padding:12px;margin-bottom:16px}</style>';
    html += '</head><body>';
    html += '<div class="text-center mb-3"><img src="' + logo + '" alt="Logo" style="max-height:50px;"><h4 class="mt-2 mb-1">Movimientos del Mes - ' + tituloMes + '</h4>';
    html += '<small class="text-muted">Impreso: ' + new Date().toLocaleDateString() + ' | Total: ' + data.length + ' movimientos</small></div>';

    html += '<div class="row text-center mb-3">';
    html += '<div class="col"><div class="totales-box"><strong class="text-success">Entradas:</strong> ' + totalE + '</div></div>';
    html += '<div class="col"><div class="totales-box"><strong class="text-danger">Salidas:</strong> ' + totalS + '</div></div>';
    html += '<div class="col"><div class="totales-box"><strong>Vales:</strong> ' + totalV + '</div></div>';
    html += '<div class="col"><div class="totales-box"><strong>Piezas:</strong> ' + totalP + '</div></div>';
    html += '</div>';

    html += '<table class="table table-bordered table-sm w-100">';
    html += '<thead><tr><th>FOLIO</th><th>FECHA</th><th>TIPO</th><th>CÓDIGO</th><th>PRODUCTO</th><th>CANTIDAD</th><th>U/M</th><th>ENTREGA</th><th>RECIBE</th><th>ESTATUS</th></tr></thead><tbody>';

    for (var i = 0; i < data.length; i++) {
      var r = data[i];
      var tipo = r.tipomov == 'E' ? '<span style="color:#198754;font-weight:700">ENTRADA</span>' : '<span style="color:#ffc107;font-weight:700">SALIDA</span>';
      html += '<tr><td>' + r.folio + '</td><td>' + r.fecha + '</td><td>' + tipo + '</td><td>' + r.codigo + '</td><td>' + r.producto + '</td><td style="text-align:right;font-weight:700">' + formatNumber(r.cantidad,2) + '</td><td>' + r.unidad + '</td><td>' + r.quienentrega + '</td><td>' + r.quienrecibe + '</td><td>' + r.estatus + '</td></tr>';
    }

    html += '</tbody></table></body></html>';
    win.document.write(html);
    win.document.close();
    setTimeout(function() { win.print(); }, 500);
  }

  function logout() {
    JMsgYesNo("¿Cerrar sesión?", function() {
      MsgServer(path.model + 'login.php', function(dat) { if (dat.result) location.href = '../index.php'; }, { action: 'logout' });
    });
  }

  function langDT() {
    return { processing: "Procesando...", search: "Buscar:", lengthMenu: "Mostrar _MENU_ registros", info: "Mostrando _START_ a _END_ de _TOTAL_ registros", infoEmpty: "Mostrando 0 a 0 de 0 registros", infoFiltered: "(filtrado de _MAX_ registros totales)", loadingRecords: "Cargando...", zeroRecords: "No se encontraron registros", emptyTable: "No hay datos disponibles", paginate: { first: "Primero", previous: "Anterior", next: "Siguiente", last: "Último" } };
  }

  function formatNumber(n, d) {
    n = parseFloat(n);
    if (isNaN(n)) return '0.00';
    var dec = (d || 2);
    var s = n.toFixed(dec);
    var partes = s.split('.');
    partes[0] = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return partes.join('.');
  }
</script>

<?php $oWeb->End(); ?>
