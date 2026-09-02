<?php
include ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( true );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_root() . 'config_db.php' );
include ( Constants::getpath_tweb() . 'core.php' );
include ( Constants::getpath_root() . 'helpers.php' );

$oSession = new TSession( APP_SESSION );
$oSession->lExeError = false;
if ( !$oSession->Valid() ) {
  header("Location: ../index.php");
  exit;
}

$oWeb = new TWeb( APP_TITLE . ' - Inventario Físico' );
$oWeb->lAwesome = true;
$oWeb->SetIcon( IMAGE_PATH . 'favicon.ico' );
$oWeb->SetFontFamily( FONT_FAMILY );
$oWeb->Activate();

$nombreUsuario = $oSession->GetVar('usuario');
$rolUsuario = $oSession->GetVar('rol') ?: 'operador';
checkAcceso(['admin', 'supervisor']);

// Cargar artículos directamente en PHP (evita problemas de AJAX/sesión)
$oDb = create_conex();
$oDb->query("SELECT id, codigo, descripcion, familia, unidadmedida, COALESCE(existenciainicial,0) AS existenciainicial,
                    COALESCE(entradas,0) AS entradas, COALESCE(salidas,0) AS salidas,
                    COALESCE(existencia,0) AS existencia,
                    COALESCE(existenciafisica,0) AS existenciafisica,
                    COALESCE(inventariado,0) AS inventariado
             FROM articulos WHERE estatus = 1 ORDER BY codigo ASC");
$articulos = [];
while ( $row = $oDb->getrow() ) {
	$articulos[] = $row;
}
$oDb->Close();
$articulosJson = json_encode($articulos, JSON_UNESCAPED_UNICODE);
?>

<style>
  .input-fisico { width: 90px; text-align: right; font-weight: 600; font-size: 0.85rem; }
  .input-fisico:focus { border-color: #28a745; box-shadow: 0 0 0 0.2rem rgba(40,167,69,.25); }
  .badge-conteo { font-size: 0.85rem; }
  #tabla-inventario { font-size: 0.8rem; }
  #tabla-inventario th { font-size: 0.75rem; white-space: nowrap; }
</style>

<div class="container-fluid p-0">
  <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm py-3 px-4">
    <a class="navbar-brand fw-bold" href="#">
      <i class="fas fa-clipboard-list me-2"></i> Control de Inventario - Inventario Físico
    </a>
    <div class="ms-auto d-flex align-items-center">
      <a href="menu.php" class="text-white me-3 fw-bold small">
        <i class="fas fa-arrow-left me-1"></i> Volver al Menú
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
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold text-dark mb-0">
        <i class="fas fa-tasks me-2 text-success"></i> Toma de Inventario Físico
      </h4>
      <div>
        <button class="btn btn-outline-secondary shadow-sm me-2" onclick="exportarExcel('tabla-inventario', 'Inventario_Fisico')">
          <i class="fas fa-file-excel me-1"></i> Exportar Excel
        </button>
        <button class="btn btn-outline-success shadow-sm me-2" onclick="window.open('inventario_fisico_print.php', '_blank')">
          <i class="fas fa-print me-1"></i> Imprimir Hoja
        </button>
        <?php if ( $rolUsuario === 'admin' ) : ?>
        <button class="btn btn-outline-primary shadow-sm" onclick="guardarHistorico()">
          <i class="fas fa-database me-1"></i> Guardar Histórico
        </button>
        <?php endif; ?>
      </div>
    </div>

    <div class="card border-0 shadow-lg" style="border-radius: 16px;">
      <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="alert alert-info py-2 small mb-0 flex-grow-1 me-3">
            <i class="fas fa-info-circle me-1"></i> Capture la <strong>Existencia Física</strong> y presione <strong>Guardar</strong>. Las filas <span class="badge badge-success" style="background:#d4edda;color:#155724;border:1px solid #c3e6cb">&nbsp;&nbsp;&nbsp;&nbsp;</span> ya están registradas (persistente), las <span class="badge" style="background:#fff3cd;color:#856404;border:1px solid #ffeeba">&nbsp;&nbsp;&nbsp;&nbsp;</span> están pendientes.
          </div>
          <span id="contador-fisico" class="badge badge-dark px-3 py-2 fw-bold" style="font-size:14px;white-space:nowrap">0 de 0</span>
        </div>
        <table id="tabla-inventario" class="table table-hover table-striped w-100">
          <thead class="bg-success text-white">
            <tr>
              <th>CÓDIGO</th>
              <th>DESCRIPCIÓN</th>
              <th>U/M</th>
              <th>EXIST. INICIAL</th>
              <th>ENTRADAS</th>
              <th>SALIDAS</th>
              <th>EXIST. ACTUAL</th>
              <th>EXIST. FÍSICA</th>
              <th style="width:60px"></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  var articulosData = <?php echo $articulosJson; ?>;
  var datosArticulos = {};
  for (var idx = 0; idx < articulosData.length; idx++) {
    datosArticulos[articulosData[idx].id] = articulosData[idx];
  }
  var oTabla;

  function formatNumber(n, d) {
    n = parseFloat(n);
    if (isNaN(n)) return '0.00';
    var dec = (d || 2);
    var s = n.toFixed(dec);
    var partes = s.split('.');
    partes[0] = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return partes.join('.');
  }

  $(function() {
    oTabla = $('#tabla-inventario').DataTable({
      data: articulosData,
      columns: [
        { data: 'codigo', className: 'fw-bold' },
        { data: 'descripcion' },
        { data: 'unidadmedida', className: 'text-center small' },
        {
          data: 'existenciainicial',
          className: 'text-end fw-bold',
          render: function(d) { return formatNumber(d, 2); }
        },
        {
          data: 'entradas',
          className: 'text-end fw-bold text-success',
          render: function(d) { return formatNumber(d, 2); }
        },
        {
          data: 'salidas',
          className: 'text-end fw-bold text-danger',
          render: function(d) { return formatNumber(d, 2); }
        },
        {
          data: 'existencia',
          className: 'text-end fw-bold',
          render: function(d) {
            var n = parseFloat(d);
            if (n <= 0) return '<span class="text-danger">' + formatNumber(n, 2) + '</span>';
            return formatNumber(n, 2);
          }
        },
        {
          data: null,
          className: 'text-center',
          orderable: false,
          render: function(row) {
            var val = row.existenciafisica !== undefined && row.existenciafisica !== null ? row.existenciafisica : '';
            return '<input type="number" class="form-control form-control-sm input-fisico" id="fis-' + row.id + '" data-id="' + row.id + '" value="' + val + '" min="0" step="0.01" placeholder="0">';
          }
        },
        {
          data: null,
          className: 'text-center',
          orderable: false,
          render: function(row) {
            var saved = row.inventariado == 1;
            return '<button class="btn btn-sm ' + (saved ? 'btn-outline-success' : 'btn-outline-primary') + ' btn-guardar-fila" data-id="' + row.id + '" title="Guardar este artículo">' +
                   (saved ? '<i class="fas fa-check"></i>' : '<i class="fas fa-save"></i>') + '</button>';
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
      order: [[0, 'asc']],
      pageLength: 25,
      dom: '<"d-flex justify-content-between align-items-center mb-3"lf>t<"d-flex justify-content-between align-items-center mt-3"ip>',
      drawCallback: function() {
        $('#tabla-inventario tbody tr').each(function() {
          marcarFila(this);
        });
        actualizarContador();
      }
    });

    $(document).on('input', '.input-fisico', function() {
      marcarFila($(this).closest('tr'));
      actualizarContador();
    });

    $(document).on('click', '.btn-guardar-fila', function() {
      var id = $(this).data('id');
      guardarFila(id);
    });
  });

  function marcarFila(tr) {
    var input = $(tr).find('.input-fisico');
    var id = input.data('id');
    var reg = datosArticulos[id] ? datosArticulos[id].inventariado : 0;
    $(tr).toggleClass('table-success', reg == 1);
    $(tr).toggleClass('table-warning', reg != 1);
  }

  function actualizarContador() {
    var total = articulosData.length;
    var grabadas = 0;
    for (var i = 0; i < total; i++) {
      var row = articulosData[i];
      var input = document.getElementById('fis-' + row.id);
      var val = input ? input.value : '';
      if (row.inventariado == 1 || (val && parseFloat(val) > 0)) grabadas++;
    }
    var texto = grabadas + ' de ' + total + ' artículos registrados';
    if (grabadas === total) texto += ' ✓ Completo';
    $('#contador-fisico').text(texto);
  }

  function guardarFila(id) {
    var row = datosArticulos[id];
    if (!row) return;
    var input = document.getElementById('fis-' + id);
    var val = input ? input.value : '';
    var existencia_fisica = parseFloat(val) || 0;
    var datos = [{
      id_articulo: row.id,
      codigo: row.codigo,
      descripcion: row.descripcion,
      existencia_fisica: existencia_fisica
    }];

    $.ajax({
      url: path.model + 'inventario_fisico.php',
      type: 'POST',
      dataType: 'json',
      data: { action: 'save_fisico', datos: JSON.stringify(datos) },
      timeout: 30000
    }).done(function(dat) {
      if (dat.result) {
        datosArticulos[id].inventariado = 1;
        JSuccess('Guardado: ' + row.codigo);
        oTabla.draw(false);
      } else {
        JError(dat.message || 'Error al guardar');
      }
    }).fail(function(jqXHR) {
      console.error('Save error:', jqXHR.responseText);
      JError('Error al guardar (revisa consola)');
    });
  }

function logout() {
    JMsgYesNo("¿Cerrar sesión?", function() {
      MsgServer(path.model + 'login.php', function(dat) {
        if (dat.result) location.href = '../index.php';
      }, { action: 'logout' });
    });
  }

  function exportarExcel(tableId, filename) {
    var table = document.getElementById(tableId);
    if (!table) { JError('Tabla no encontrada'); return; }

    if (typeof XLSX === 'undefined') {
      var script = document.createElement('script');
      script.src = 'https://cdn.sheetjs.com/xlsx-0.18.5/package/dist/xlsx.full.min.js';
      script.onload = function() { generarExcel(table, filename); };
      document.head.appendChild(script);
    } else {
      generarExcel(table, filename);
    }

    function generarExcel(table, filename) {
      var wb = XLSX.utils.table_to_book(table, { sheet: "Datos" });
      XLSX.writeFile(wb, filename + '_' + new Date().toISOString().split('T')[0] + '.xlsx');
      JSuccess('Archivo Excel generado correctamente');
    }
  }

  function guardarHistorico() {
    var hoy = new Date();
    var defaultMes = hoy.getFullYear() + '-' + String(hoy.getMonth() + 1).padStart(2, '0');

    var html = '<div class="modal fade" id="modal-historico" tabindex="-1">';
    html += '<div class="modal-dialog modal-dialog-centered">';
    html += '<div class="modal-content">';
    html += '<div class="modal-header bg-primary text-white">';
    html += '<h5 class="modal-title"><i class="fas fa-database me-2"></i>Guardar Histórico</h5>';
    html += '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>';
    html += '</div>';
    html += '<div class="modal-body">';
    html += '<label class="form-label fw-bold">Seleccione el mes a guardar:</label>';
    html += '<input type="month" class="form-control" id="hist-mes-guardar" value="' + defaultMes + '">';
    html += '</div>';
    html += '<div class="modal-footer">';
    html += '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>';
    html += '<button type="button" class="btn btn-primary" onclick="confirmarGuardarHistorico()">Guardar</button>';
    html += '</div></div></div></div>';

    document.body.insertAdjacentHTML('beforeend', html);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-historico')).show();
    document.getElementById('modal-historico').addEventListener('hidden.bs.modal', function() { this.remove(); });
  }

  function confirmarGuardarHistorico() {
    var valor = document.getElementById('hist-mes-guardar').value;
    if (!valor) { JError('Seleccione un mes'); return; }
    var partes = valor.split('-');
    var anio = parseInt(partes[0]);
    var mes = parseInt(partes[1]);
    var meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    var nombreMes = meses[mes - 1] + ' ' + anio;

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-historico')).hide();

    JMsgYesNo('¿Guardar histórico de existencia física de ' + nombreMes + '?', function() {
      MsgServer(path.model + 'inventario_fisico.php', function(dat) {
        if (dat.result) {
          JSuccess(dat.message);
        } else {
          JError(dat.message);
        }
      }, { action: 'save_historico', anio: anio, mes: mes });
    });
  }
</script>

<?php $oWeb->End(); ?>
