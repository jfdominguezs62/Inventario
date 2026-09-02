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

$oWeb = new TWeb( APP_TITLE . ' - Artículos' );
$oWeb->lAwesome = true;
$oWeb->SetIcon( IMAGE_PATH . 'favicon.ico' );
$oWeb->SetFontFamily( FONT_FAMILY );
$oWeb->Activate();

$nombreUsuario = $oSession->GetVar('usuario');
$rolUsuario = $oSession->GetVar('rol') ?: 'operador';
checkAcceso(['admin', 'operador', 'consultor']);
?>

<style>
  .table-actions .btn { padding: .25rem .5rem; font-size: .85rem; }
  #modal-form .modal-header { background: linear-gradient(135deg, #007bff, #0056b3); }
  #btn-new { border-radius: 50px; padding: .5rem 1.5rem; font-weight: 600; }
  .dt-buttons .btn { margin-right: 4px; }
  td.estatus-activo { color: #28a745; font-weight: 600; }
  td.estatus-inactivo { color: #dc3545; font-weight: 600; }
  #tabla-articulos { font-size: 0.8rem; }
  #tabla-articulos th { font-size: 0.75rem; }
  #tabla-articulos td { font-size: 0.8rem; white-space: nowrap; }
</style>

<div class="container-fluid p-0">
  <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm py-3 px-4">
    <a class="navbar-brand fw-bold" href="#">
      <i class="fas fa-boxes me-2"></i> Control de Inventario
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
      <h3 class="fw-bold text-dark mb-0">
        <i class="fas fa-cube me-2 text-primary"></i> Catálogo de Artículos
      </h3>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary shadow-sm" onclick="exportarExcel('tabla-articulos', 'Catalogo_Articulos')">
          <i class="fas fa-file-excel me-1"></i> Exportar Excel
        </button>
        <?php if ( $rolUsuario !== 'consultor' ) : ?>
        <button id="btn-new" class="btn btn-primary shadow-sm" onclick="nuevoArticulo()">
          <i class="fas fa-plus me-1"></i> Nuevo Artículo
        </button>
        <?php endif; ?>
      </div>
    </div>

    <div class="card border-0 shadow-lg" style="border-radius: 16px;">
      <div class="card-body p-4">
        <table id="tabla-articulos" class="table table-hover table-striped w-100" style="border-radius: 12px;">
          <thead class="bg-light">
            <tr>
              <th>CÓDIGO</th>
              <th>DESCRIPCIÓN</th>
              <th>FAMILIA</th>
              <th>U/MEDIDA</th>
              <th>PZAS/UN</th>
              <th>EXISTENCIA</th>
              <th>STOCK MÍNIMO</th>
              <th>ENTRADAS</th>
              <th>SALIDAS</th>
              <th>ESTATUS</th>
              <th>FECHA ALTA</th>
              <th style="width:100px">ACCIONES</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Formulario -->
<div class="modal fade" id="modal-form" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header border-0 py-3">
        <h5 class="modal-title fw-bold text-white" id="modal-form-title">
          <i class="fas fa-cube me-2"></i> <span id="modal-title-text">Nuevo Artículo</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" style="background-color: #f8f9fa;">
        <form id="form-articulo" autocomplete="off">
          <input type="hidden" id="f_id" name="id" value="">

          <div class="row">
            <div class="col-md-4 form-group mb-3">
              <label for="f_codigo" class="text-secondary fw-bold small">CÓDIGO <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="f_codigo" name="codigo"
                     placeholder="Código único" required maxlength="50"
                     onblur="checkCodigo()">
              <small id="codigo-msg" class="form-text"></small>
            </div>
            <div class="col-md-8 form-group mb-3">
              <label for="f_descripcion" class="text-secondary fw-bold small">DESCRIPCIÓN <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="f_descripcion" name="descripcion"
                     placeholder="Descripción del artículo" required maxlength="255"
                     oninput="this.value = this.value.toUpperCase()"
                     style="text-transform: uppercase;">
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 form-group mb-3">
              <label for="f_familia" class="text-secondary fw-bold small">FAMILIA</label>
              <input type="text" class="form-control" id="f_familia" name="familia"
                     placeholder="Ej: Electrónica, Ferretería" maxlength="100">
            </div>
            <div class="col-md-4 form-group mb-3">
              <label for="f_unidadmedida" class="text-secondary fw-bold small">UNIDAD DE MEDIDA</label>
              <select class="form-control" id="f_unidadmedida" name="unidadmedida">
                <option value="">-- Seleccione --</option>
                <option value="PIEZA">PIEZA</option>
                <option value="CAJA">CAJA</option>
                <option value="PAQUETE">PAQUETE</option>
                <option value="KILO">KILO</option>
                <option value="LITRO">LITRO</option>
                <option value="METRO">METRO</option>
                <option value="ROLLO">ROLLO</option>
                <option value="PAR">PAR</option>
                <option value="DOCENA">DOCENA</option>
                <option value="OTRO">OTRO</option>
              </select>
            </div>
            <div class="col-md-4 form-group mb-3">
              <label for="f_piezasxunidad" class="text-secondary fw-bold small">PIEZAS POR UNIDAD</label>
              <input type="number" class="form-control" id="f_piezasxunidad" name="piezasxunidad"
                     value="1" min="1" step="1">
              <small class="form-text text-muted">Ej: si es una caja de 12 piezas, indique 12</small>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 form-group mb-3">
              <label for="f_existencia" class="text-secondary fw-bold small">EXISTENCIA</label>
              <input type="number" class="form-control" id="f_existencia" name="existencia"
                     value="0" min="0" step="0.01" <?php echo ($rolUsuario !== 'admin' && $rolUsuario !== 'supervisor') ? 'readonly' : ''; ?>>
              <?php if ($rolUsuario !== 'admin' && $rolUsuario !== 'supervisor'): ?>
                <small class="form-text text-muted">Solo admin/supervisor pueden modificar</small>
              <?php endif; ?>
            </div>
            <div class="col-md-4 form-group mb-3">
              <label for="f_stock_minimo" class="text-secondary fw-bold small">STOCK MÍNIMO</label>
              <input type="number" class="form-control" id="f_stock_minimo" name="stock_minimo"
                     value="0" min="0" step="0.01">
              <small class="form-text text-muted">Cantidad mínima para alerta de reorden</small>
            </div>
            <div class="col-md-4 form-group mb-3">
              <label for="f_estatus" class="text-secondary fw-bold small">ESTATUS</label>
              <select class="form-control" id="f_estatus" name="estatus">
                <option value="1">ACTIVO</option>
                <option value="0">INACTIVO</option>
              </select>
            </div>
            <div class="col-md-4 form-group mb-3" id="fechas-container" style="display:none;">
              <label class="text-secondary fw-bold small">FECHAS</label>
              <div class="bg-light rounded p-2 small">
                <div><strong>Alta:</strong> <span id="f_fechaalta"></span></div>
                <div><strong>Cambio:</strong> <span id="f_fechacambio"></span></div>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer border-0" style="background-color: #f8f9fa;">
        <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">
          <i class="fas fa-times me-1"></i> Cancelar
        </button>
        <button type="button" class="btn btn-primary fw-bold shadow-sm" onclick="guardarArticulo()">
          <i class="fas fa-save me-1"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="modal-delete" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow" style="border-radius: 16px;">
      <div class="modal-header border-0 bg-danger text-white py-3" style="border-radius: 16px 16px 0 0;">
        <h5 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> Confirmar Eliminación</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 text-center">
        <i class="fas fa-trash-alt text-danger" style="font-size: 48px;"></i>
        <p class="mt-3 mb-0 fw-bold" id="delete-msg">¿Está seguro de eliminar este artículo?</p>
        <p class="text-muted small" id="delete-item-info"></p>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">
          <i class="fas fa-times me-1"></i> Cancelar
        </button>
        <button type="button" class="btn btn-danger fw-bold shadow-sm" onclick="confirmarEliminar()">
          <i class="fas fa-trash-alt me-1"></i> Eliminar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  var oTabla;
  var deleteId = null;

  $(function() {
    oTabla = $('#tabla-articulos').DataTable({
      processing: true,
      serverSide: false,
      ajax: {
        url: path.model + 'articulos.php',
        type: 'POST',
        data: { action: 'list' },
        dataSrc: function(json) {
          if (json.result && json.data) {
            return json.data;
          }
          JError('Error al cargar datos');
          return [];
        }
      },
      columns: [
        { data: 'codigo' },
        {
          data: 'descripcion',
          render: function(data) {
            return '<span class="fw-bold">' + escapeHtml(data) + '</span>';
          }
        },
        { data: 'familia' },
        { data: 'unidadmedida' },
        {
          data: 'piezasxunidad',
          className: 'text-center',
          render: function(data) {
            return '<span class="badge badge-info">' + data + '</span>';
          }
        },
        {
          data: 'existencia',
          className: 'text-end fw-bold',
          render: function(data) {
            var n = parseFloat(data);
            if (n <= 0) return '<span class="text-danger">' + formatNumber(n, 2) + '</span>';
            return formatNumber(n, 2);
          }
        },
        {
          data: 'stock_minimo',
          className: 'text-end fw-bold text-warning',
          render: function(data) {
            var n = parseFloat(data);
            return formatNumber(n, 2);
          }
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
          data: 'estatus',
          className: 'text-center',
          render: function(data) {
            if (data == '1' || data == 1) {
              return '<span class="badge badge-success px-3 py-1">ACTIVO</span>';
            }
            return '<span class="badge badge-danger px-3 py-1">INACTIVO</span>';
          }
        },
        {
          data: 'fechaalta',
          render: function(data) {
            return data ? data.substr(0, 10) : '';
          }
        },
        {
          data: null,
          className: 'text-center table-actions',
          orderable: false,
          render: function(row) {
            var rolUsuario = '<?php echo $rolUsuario; ?>';
            var btnEditar = rolUsuario === 'consultor' ? '' :
                           '<button class="btn btn-sm btn-outline-primary me-1" title="Editar" onclick="editarArticulo(' + row.id + ')">' +
                           '  <i class="fas fa-edit"></i>' +
                           '</button>';
            var btnEliminar = '';
            if (rolUsuario === 'admin' || rolUsuario === 'supervisor') {
              btnEliminar = '<button class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="eliminarArticulo(' + row.id + ', \'' + escapeOnclick(row.descripcion) + '\')">' +
                           '  <i class="fas fa-trash-alt"></i>' +
                           '</button>';
            }
            return btnEditar + btnEliminar;
          }
        }
      ],
      language: {
        processing:     "Procesando...",
        search:         "Buscar:",
        lengthMenu:     "Mostrar _MENU_ registros",
        info:           "Mostrando _START_ a _END_ de _TOTAL_ registros",
        infoEmpty:      "Mostrando 0 a 0 de 0 registros",
        infoFiltered:   "(filtrado de _MAX_ registros totales)",
        infoPostFix:    "",
        loadingRecords: "Cargando...",
        zeroRecords:    "No se encontraron registros",
        emptyTable:     "No hay datos disponibles",
        paginate: {
          first:      "Primero",
          previous:   "Anterior",
          next:       "Siguiente",
          last:       "Último"
        },
        aria: {
          sortAscending:  ": activar para ordenar ascendente",
          sortDescending: ": activar para ordenar descendente"
        }
      },
      order: [[0, 'asc']],
      pageLength: 25,
      responsive: true,
      dom: '<"d-flex justify-content-between align-items-center mb-3"lf>t<"d-flex justify-content-between align-items-center mt-3"ip>',
      stateSave: true
    });

    $('#modal-form').on('hidden.bs.modal', function() {
      $('#form-articulo')[0].reset();
      $('#f_id').val('');
      $('#codigo-msg').html('');
      $('#f_codigo').removeClass('is-invalid is-valid');
      $('#fechas-container').hide();
      document.getElementById('f_codigo').readOnly = false;
      document.getElementById('f_piezasxunidad').value = 1;
      document.getElementById('f_existencia').value = '0';
      document.getElementById('f_stock_minimo').value = '0';
      document.getElementById('f_estatus').value = '1';
    });
  });

  function nuevoArticulo() {
    document.getElementById('modal-title-text').textContent = 'Nuevo Artículo';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-form')).show();
    setTimeout(function() { document.getElementById('f_codigo').focus(); }, 500);
  }

  function editarArticulo(id) {
    var aPar = { action: 'get', id: id };
    MsgServer(path.model + 'articulos.php', cargarEditar, aPar);

    function cargarEditar(dat) {
      if (dat.result && dat.data) {
        var r = dat.data;
        document.getElementById('modal-title-text').textContent = 'Editar Artículo: ' + r.codigo;
        $('#f_id').val(r.id);
        $('#f_codigo').val(r.codigo).prop('readonly', true);
        $('#f_descripcion').val(r.descripcion);
        $('#f_familia').val(r.familia);
        $('#f_unidadmedida').val(r.unidadmedida);
        $('#f_piezasxunidad').val(r.piezasxunidad);
        $('#f_existencia').val(r.existencia);
        $('#f_stock_minimo').val(r.stock_minimo);
        $('#f_estatus').val(r.estatus);
        $('#f_fechaalta').text(r.fechaalta);
        $('#f_fechacambio').text(r.fechacambio);
        $('#fechas-container').show();
        $('#codigo-msg').html('');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-form')).show();
      } else {
        JError(dat.message || 'Error al cargar artículo');
      }
    }
  }

  function guardarArticulo() {
    var codigo = document.getElementById('f_codigo').value.trim();
    var descripcion = document.getElementById('f_descripcion').value.trim();

    if (!codigo) {
      JError('El código es obligatorio');
      document.getElementById('f_codigo').focus();
      return;
    }
    if (!descripcion) {
      JError('La descripción es obligatoria');
      document.getElementById('f_descripcion').focus();
      return;
    }

    var aPar = {
      action: 'save',
      id: $('#f_id').val(),
      codigo: codigo,
      descripcion: descripcion,
      familia: $('#f_familia').val().trim(),
      unidadmedida: $('#f_unidadmedida').val(),
      piezasxunidad: $('#f_piezasxunidad').val(),
      estatus: $('#f_estatus').val(),
      existencia: $('#f_existencia').val(),
      stock_minimo: $('#f_stock_minimo').val()
    };

    MsgServer(path.model + 'articulos.php', responseGuardar, aPar);

    function responseGuardar(dat) {
      if (dat.result) {
        JSuccess(dat.message || 'Artículo guardado');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-form')).hide();
        oTabla.ajax.reload(null, false);
      } else {
        JError(dat.message || 'Error al guardar');
      }
    }
  }

  function eliminarArticulo(id, descripcion) {
    deleteId = id;
    document.getElementById('delete-item-info').textContent = 'Código/Descripción: ' + descripcion;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-delete')).show();
  }

  function confirmarEliminar() {
    if (deleteId === null) return;

    var aPar = { action: 'delete', id: deleteId };

    MsgServer(path.model + 'articulos.php', responseEliminar, aPar);

    function responseEliminar(dat) {
      bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-delete')).hide();
      if (dat.result) {
        JSuccess(dat.message || 'Artículo eliminado');
        oTabla.ajax.reload(null, false);
      } else {
        JError(dat.message || 'Error al eliminar');
      }
      deleteId = null;
    }
  }

  function checkCodigo() {
    var codigo = document.getElementById('f_codigo').value.trim();
    var id = $('#f_id').val();

    if (!codigo) {
      $('#codigo-msg').html('');
      $('#f_codigo').removeClass('is-invalid is-valid');
      return;
    }

    var aPar = { action: 'check_codigo', codigo: codigo, id: id || '' };

    MsgServer(path.model + 'articulos.php', responseCheck, aPar, false);

    function responseCheck(dat) {
      if (dat.result && dat.exists) {
        $('#codigo-msg').html('<span class="text-danger fw-bold"><i class="fas fa-times me-1"></i> El código ya existe</span>');
        $('#f_codigo').removeClass('is-valid').addClass('is-invalid');
      } else {
        $('#codigo-msg').html('<span class="text-success fw-bold"><i class="fas fa-check me-1"></i> Código disponible</span>');
        $('#f_codigo').removeClass('is-invalid').addClass('is-valid');
      }
    }
  }

  function logout() {
    JMsgYesNo("¿Estás seguro de que deseas cerrar sesión?", doLogout);

    function doLogout() {
      var aPar = { action: 'logout' };
      MsgServer(path.model + 'login.php', response_logout, aPar);
    }

    function response_logout(dat) {
      if (dat.result) {
        location.href = '../index.php';
      }
    }
  }

  function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
  }

  function escapeOnclick(text) {
    if (!text) return '';
    return escapeHtml(text)
      .replace(/'/g, "\\'")
      .replace(/"/g, '&quot;');
  }

  function formatNumber(n, decimals) {
    n = parseFloat(n);
    if (isNaN(n)) return '0.00';
    var dec = (decimals || 2);
    var s = n.toFixed(dec);
    var partes = s.split('.');
    partes[0] = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return partes.join('.');
  }

  function exportarExcel(tableId, filename) {
    var table = document.getElementById(tableId);
    if (!table) { JError('Tabla no encontrada'); return; }

    // Cargar SheetJS dinámicamente si no está disponible
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
</script>

<?php
$oWeb->End();
?>
