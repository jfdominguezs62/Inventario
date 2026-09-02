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

$oWeb = new TWeb( APP_TITLE . ' - Consulta de Vales' );
$oWeb->lAwesome = true;
$oWeb->SetIcon( IMAGE_PATH . 'favicon.ico' );
$oWeb->SetFontFamily( FONT_FAMILY );

$nombreUsuario = $oSession->GetVar('usuario');
$rolUsuario = $oSession->GetVar('rol') ?: 'operador';
checkAcceso(['admin', 'operador', 'supervisor']);

$oWeb->Activate();
?>

<div class="container-fluid p-0">
  <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm py-3 px-4">
    <a class="navbar-brand fw-bold" href="#">
      <i class="fas fa-search me-2"></i> Consulta de Vales
    </a>
    <div class="ms-auto d-flex align-items-center">
      <a href="menu.php" class="text-white me-3 fw-bold small">
        <i class="fas fa-arrow-left me-1"></i> Menú
      </a>
      <span class="text-white me-3">
        <i class="fas fa-user-circle me-1"></i> <span class="fw-bold"><?php echo htmlspecialchars($nombreUsuario); ?></span>
        <span class="badge badge-light ms-1"><?php echo strtoupper($rolUsuario); ?></span>
      </span>
      <button class="btn btn-outline-light btn-sm fw-bold" onclick="logout()">
        Cerrar Sesión <i class="fas fa-sign-out-alt ms-1"></i>
      </button>
    </div>
  </nav>

  <div class="container-fluid my-4 px-4">
    <h4 class="fw-bold mb-4">
      <i class="fas fa-filter me-2 text-primary"></i> Filtros de Búsqueda
    </h4>

    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
      <div class="card-body">
        <div class="row align-items-end">
          <div class="col-md-2 mb-2">
            <label class="small fw-bold">Desde</label>
            <input type="date" class="form-control" id="filtro-fecha_desde">
          </div>
          <div class="col-md-2 mb-2">
            <label class="small fw-bold">Hasta</label>
            <input type="date" class="form-control" id="filtro-fecha_hasta">
          </div>
          <div class="col-md-2 mb-2">
            <label class="small fw-bold">Tipo</label>
            <select class="form-control" id="filtro-tipo">
              <option value="">TODOS</option>
              <option value="E">ENTRADA</option>
              <option value="S">SALIDA</option>
            </select>
          </div>
          <div class="col-md-3 mb-2">
            <label class="small fw-bold">Folio / Producto</label>
            <input type="text" class="form-control" id="filtro-busqueda" placeholder="Buscar...">
          </div>
          <div class="col-md-3 mb-2">
            <button class="btn btn-primary w-100 fw-bold" onclick="aplicarFiltros()">
              <i class="fas fa-search me-1"></i> Buscar
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-lg" style="border-radius: 16px;">
      <div class="card-body p-4">
        <table id="tabla-resultados" class="table table-hover table-striped w-100">
          <thead class="bg-light">
            <tr>
              <th>FOLIO</th>
              <th>FECHA</th>
              <th>TIPO</th>
              <th>PRODUCTO</th>
              <th>CANTIDAD</th>
              <th>ENTREGA</th>
              <th>RECIBE</th>
              <th>ESTATUS</th>
              <th style="width:80px">ACCIÓN</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  var oTabla;

  $(function() {
    oTabla = $('#tabla-resultados').DataTable({
      processing: true,
      serverSide: false,
      ajax: {
        url: path.model + 'vales.php',
        type: 'POST',
        data: function() {
          return {
            action: 'list',
            fecha_desde: $('#filtro-fecha_desde').val(),
            fecha_hasta: $('#filtro-fecha_hasta').val(),
            tipo: $('#filtro-tipo').val(),
            busqueda: $('#filtro-busqueda').val()
          };
        },
        dataSrc: function(json) {
          return (json.result && json.data) ? json.data : [];
        }
      },
      columns: [
        { data: 'folio', className: 'fw-bold' },
        { data: 'fecha' },
        {
          data: 'tipomov',
          render: function(d) {
            if (d == 'E') return '<span class="badge badge-success px-3 py-1">ENTRADA</span>';
            return '<span class="badge badge-warning text-dark px-3 py-1">SALIDA</span>';
          }
        },
        { data: 'productos' },
        {
          data: 'total_cantidad',
          className: 'text-end',
          render: function(d) { return formatNumber(d, 2); }
        },
        { data: 'quienentrega' },
        { data: 'quienrecibe' },
        {
          data: 'estatus',
          className: 'text-center',
          render: function(d) {
            return d == 'ACTIVO'
              ? '<span class="badge badge-success px-3 py-1">ACTIVO</span>'
              : '<span class="badge badge-secondary px-3 py-1">' + d + '</span>';
          }
        },
        {
          data: 'id',
          className: 'text-center',
          render: function(id) {
            return '<button class="btn btn-sm btn-outline-info" onclick="window.open(\'vales_print.php?id=' + id + '\', \'_blank\')">' +
                   '  <i class="fas fa-print"></i>' +
                   '</button>';
          }
        }
      ],
      language: {
        processing: "Procesando...", search: "Buscar:", lengthMenu: "Mostrar _MENU_ registros",
        info: "Mostrando _START_ a _END_ de _TOTAL_ registros", infoEmpty: "Mostrando 0 a 0 de 0 registros",
        infoFiltered: "(filtrado de _MAX_ registros totales)", loadingRecords: "Cargando...",
        zeroRecords: "No se encontraron registros", emptyTable: "No hay datos disponibles",
        paginate: { first: "Primero", previous: "Anterior", next: "Siguiente", last: "Último" }
      },
      order: [[1, 'desc']],
      pageLength: 25,
      responsive: true,
      dom: '<"d-flex justify-content-between align-items-center mb-3"lf>t<"d-flex justify-content-between align-items-center mt-3"ip>'
    });
  });

  function aplicarFiltros() {
    oTabla.ajax.reload();
  }

  function logout() {
    JMsgYesNo("¿Cerrar sesión?", function() {
      MsgServer(path.model + 'login.php', function(dat) {
        if (dat.result) location.href = '../index.php';
      }, { action: 'logout' });
    });
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
