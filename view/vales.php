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

$oWeb = new TWeb( APP_TITLE . ' - Vales' );
$oWeb->lAwesome = true;
$oWeb->SetIcon( IMAGE_PATH . 'favicon.ico' );
$oWeb->SetFontFamily( FONT_FAMILY );

$nombreUsuario = $oSession->GetVar('usuario');
$rolUsuario = $oSession->GetVar('rol') ?: 'operador';
checkAcceso(['admin', 'operador', 'consultor']);

$oWeb->Activate();
?>

<style>
  #tabla-detalle td { vertical-align: middle; }
  .detalle-codigo { min-width: 100px; }
  .detalle-producto { min-width: 180px; }
  .detalle-cantidad { min-width: 80px; }
  .btn-remove-row { color: #dc3545; cursor: pointer; font-size: 1.2rem; }
  .vale-total-partidas { font-size: 1.1rem; font-weight: 600; }
  #modal-form .modal-header { background: linear-gradient(135deg, #007bff, #0056b3); }
  #modal-search-articulos .modal-header { background: linear-gradient(135deg, #17a2b8, #117a8b); }
</style>

<div class="container-fluid p-0">
  <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm py-3 px-4">
    <a class="navbar-brand fw-bold" href="#">
      <i class="fas fa-exchange-alt me-2"></i> Control de Inventario - Vales
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
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
      <h3 class="fw-bold text-dark mb-0">
        <i class="fas fa-file-invoice me-2 text-primary"></i> Vales de Entrada y Salida
      </h3>
      <div class="d-flex gap-2 align-items-center flex-shrink-0">
        <div class="input-group" style="width: 300px;">
          <input type="text" id="filtro-detalle" class="form-control form-control-sm" placeholder="Buscar en detalle (código/descripción)..." autocomplete="off">
          <button class="btn btn-outline-primary btn-sm" onclick="aplicarFiltroDetalle()"><i class="fas fa-search"></i></button>
        </div>
        <?php if ( $rolUsuario !== 'consultor' ) : ?>
        <button id="btn-new" class="btn btn-primary shadow-sm" onclick="nuevoVale()">
          <i class="fas fa-plus me-1"></i> Nuevo Vale
        </button>
        <?php endif; ?>
      </div>
    </div>

    <div class="d-flex align-items-center gap-2 mb-3">
      <label class="form-check d-flex align-items-center m-0" style="gap:.4rem; white-space:nowrap; flex-shrink:0; cursor:pointer;" for="chk-solo-hoy">
        <input class="form-check-input m-0" type="checkbox" id="chk-solo-hoy">
        <span class="small fw-bold text-dark">Mostrar vales solo de</span>
      </label>
      <input type="date" id="filtro-solo-fecha" class="form-control form-control-sm flex-shrink-0" style="width:155px;" title="Fecha a mostrar">
    </div>

    <div class="card border-0 shadow-lg" style="border-radius: 16px;">
      <div class="card-body p-4">
        <table id="tabla-vales" class="table table-hover table-striped w-100">
          <thead class="bg-light">
            <tr>
              <th># VALE</th>
              <th>FECHA</th>
              <th>TIPO</th>
              <th>PARTIDAS</th>
              <th>TOTAL PZAS</th>
              <th>QUIEN ENTREGA</th>
              <th>QUIEN RECIBE</th>
              <th>ESTATUS</th>
              <th style="width:140px">ACCIONES</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Formulario -->
<div class="modal fade" id="modal-form" tabindex="-1" role="dialog" data-backdrop="static" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header border-0 py-3">
        <h5 class="modal-title fw-bold text-white">
          <i class="fas fa-file-invoice me-2"></i> <span id="modal-title-text">Nuevo Vale</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" style="background-color: #f8f9fa;">
        <form id="form-vale" autocomplete="off">
          <input type="hidden" id="f_id" name="id" value="">

          <div class="row">
            <div class="col-md-2 form-group mb-3">
              <label class="text-secondary fw-bold small">TIPO <span class="text-danger">*</span></label>
              <select class="form-control" id="f_tipomov" name="tipomov" onchange="cambioTipo()">
                <option value="">-- Seleccione --</option>
              </select>
              <input type="hidden" id="f_id_tipomov" name="id_tipomov">
              <input type="hidden" id="f_signo" value="">
            </div>
            <div class="col-md-2 form-group mb-3">
              <label class="text-secondary fw-bold small">FECHA <span class="text-danger">*</span></label>
              <input type="date" class="form-control" id="f_fecha" name="fecha">
            </div>
            <div class="col-md-2 form-group mb-3">
              <label class="text-secondary fw-bold small">FOLIO <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="f_folio" name="folio" readonly>
            </div>
            <div class="col-md-3 form-group mb-3">
              <label class="text-secondary fw-bold small">QUIEN ENTREGA <span class="text-danger">*</span></label>
              <select class="form-control" id="f_quienentrega">
                <option value="">-- Seleccione --</option>
              </select>
            </div>
            <div class="col-md-3 form-group mb-3">
              <label class="text-secondary fw-bold small">QUIEN RECIBE <span class="text-danger">*</span></label>
              <select class="form-control" id="f_quienrecibe">
                <option value="">-- Seleccione --</option>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 form-group mb-3">
              <label class="text-secondary fw-bold small">DESCRIPCIÓN DEL TRABAJO</label>
              <textarea class="form-control" id="f_descripcion_trabajo" rows="2"></textarea>
            </div>
            <div class="col-md-6 form-group mb-3">
              <label class="text-secondary fw-bold small">OBSERVACIONES</label>
              <textarea class="form-control" id="f_observaciones" rows="2"></textarea>
            </div>
          </div>

          <hr class="my-3">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark mb-0"><i class="fas fa-list me-2"></i>Artículos del Vale</h5>
            <?php if ( $rolUsuario !== 'consultor' ) : ?>
            <button type="button" class="btn btn-success btn-sm fw-bold" onclick="agregarFila()">
              <i class="fas fa-plus me-1"></i> Agregar Artículo
            </button>
            <?php endif; ?>
          </div>

          <div class="table-responsive">
            <table id="tabla-detalle" class="table table-bordered table-sm">
              <thead class="bg-secondary text-white small">
                <tr>
                  <th style="width:120px">CÓDIGO</th>
                  <th>PRODUCTO</th>
                  <th style="width:100px">CATEGORÍA</th>
                  <th style="width:80px">UNIDAD</th>
                  <th style="width:100px">EXIST. ACTUAL</th>
                  <th style="width:100px">CANTIDAD</th>
                  <th style="width:40px"></th>
                </tr>
              </thead>
              <tbody id="detalle-body">
              </tbody>
              <tfoot id="detalle-foot" style="display:none;">
                <tr class="fw-bold bg-light">
                  <td colspan="4" class="text-end">TOTAL ARTÍCULOS:</td>
                  <td id="total-piezas">0</td>
                  <td></td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </form>
      </div>
      <div class="modal-footer border-0" style="background-color: #f8f9fa;">
        <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">
          <i class="fas fa-times me-1"></i> Cancelar
        </button>
        <?php if ( $rolUsuario !== 'consultor' ) : ?>
        <button type="button" class="btn btn-primary fw-bold shadow-sm" onclick="guardarVale()">
          <i class="fas fa-save me-1"></i> Guardar Vale
        </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Modal Búsqueda de Artículos -->
<div class="modal fade" id="modal-search-articulos" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header border-0 py-3 bg-info">
        <h5 class="modal-title fw-bold text-white">
          <i class="fas fa-search me-2"></i> Buscar Artículo
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" style="background-color: #f8f9fa;">
        <table id="tabla-search-articulos" class="table table-hover table-striped w-100">
          <thead class="bg-info text-white">
            <tr>
              <th>CÓDIGO</th>
              <th>DESCRIPCIÓN</th>
              <th>FAMILIA</th>
              <th>U/MEDIDA</th>
              <th>EXISTENCIA</th>
              <th>ENTRADAS</th>
              <th>SALIDAS</th>
              <th style="width:50px"></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <div class="modal-footer border-0" style="background-color: #f8f9fa;">
        <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">
          <i class="fas fa-times me-1"></i> Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  var oTabla;
  var rowCount = 0;
  var rowData = [];
  var editId = null;
  var searchTargetIdx = null;
  var modoLectura = false;

  $(function() {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-form'), { backdrop: 'static' });
    document.getElementById('f_fecha').value = new Date().toISOString().substr(0, 10);
    document.getElementById('filtro-solo-fecha').value = new Date().toISOString().substr(0, 10);

    oTabla = $('#tabla-vales').DataTable({
      processing: true,
      ajax: {
        url: path.model + 'vales.php',
        type: 'POST',
        data: function(d) {
          d.action = 'list';
          d.filtro_detalle = $('#filtro-detalle').val();
          d.solo_hoy = $('#chk-solo-hoy').is(':checked') ? '1' : '0';
          d.fecha_solo = $('#filtro-solo-fecha').val();
        },
        dataSrc: function(json) {
          return (json.result && json.data) ? json.data : [];
        }
      },
      columns: [
        { data: 'folio', className: 'fw-bold' },
        { data: 'fecha' },
        {
          data: null,
          render: function(row) {
            if (row.tipomov == 'E') {
              return '<span class="badge badge-success px-3 py-1">ENTRADA</span>';
            }
            return '<span class="badge badge-warning text-dark px-3 py-1">SALIDA</span>';
          }
        },
        { data: 'total_partidas', className: 'text-center' },
        {
          data: 'total_cantidad',
          className: 'text-end fw-bold',
          render: function(d) { return formatNumber(d, 2); }
        },
        { data: 'quienentrega' },
        { data: 'quienrecibe' },
        {
          data: 'estatus',
          className: 'text-center',
          render: function(d) {
            if (d == 'ACTIVO') return '<span class="badge badge-success px-3 py-1">ACTIVO</span>';
            return '<span class="badge badge-secondary px-3 py-1">' + d + '</span>';
          }
        },
        {
          data: null,
          className: 'text-center table-actions',
          orderable: false,
          render: function(row) {
            var rolUsuario = '<?php echo $rolUsuario; ?>';
            var imprimir = '<button class="btn btn-sm btn-outline-info me-1" title="Imprimir" onclick="imprimirVale(' + row.id + ')">' +
                           '  <i class="fas fa-print"></i>' +
                           '</button>';
            var ver = '';
            var editar = '';
            var eliminar = '';
            if (rolUsuario === 'consultor') {
              ver = '<button class="btn btn-sm btn-outline-secondary me-1" title="Ver" onclick="verVale(' + row.id + ')">' +
                    '  <i class="fas fa-eye"></i>' +
                    '</button>';
            } else {
              editar = '<button class="btn btn-sm btn-outline-primary me-1" title="Editar" onclick="solicitarClaveEditar(' + row.id + ')">' +
                       '  <i class="fas fa-edit"></i>' +
                       '</button>';
              eliminar = '<button class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="solicitarClaveEliminar(' + row.id + ', \'' + escapeJs(row.folio) + '\')">' +
                         '  <i class="fas fa-trash-alt"></i>' +
                         '</button>';
            }
            return imprimir + ver + editar + eliminar;
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
      order: [[0, 'desc']],
      pageLength: 25,
      responsive: true,
      dom: '<"d-flex justify-content-between align-items-center mb-3"lf>t<"d-flex justify-content-between align-items-center mt-3"ip>'
    });

    // Filtro por detalle (código/descripción en vales_detalle)
    var filtroTimer;
    $('#filtro-detalle').on('input', function() {
      clearTimeout(filtroTimer);
      filtroTimer = setTimeout(aplicarFiltroDetalle, 300);
    });
    $('#filtro-detalle').on('keypress', function(e) {
      if (e.which === 13) {
        e.preventDefault();
        clearTimeout(filtroTimer);
        aplicarFiltroDetalle();
      }
    });

    $('#chk-solo-hoy').on('change', function() {
      oTabla.ajax.reload();
    });

    $('#filtro-solo-fecha').on('change', function() {
      oTabla.ajax.reload();
    });

    function aplicarFiltroDetalle() {
      oTabla.ajax.reload();
    }

    // Cargar tipos de movimiento
    MsgServer(path.model + 'vales.php', loadTipos, { action: 'list_tipos' }, false);

    function loadTipos(dat) {
      if (dat.result && dat.data) {
        var sel = document.getElementById('f_tipomov');
        dat.data.forEach(function(t) {
          var opt = document.createElement('option');
          opt.value = t.codigo;
          opt.setAttribute('data-id', t.id);
          opt.setAttribute('data-signo', t.signo);
          opt.textContent = t.nombre;
          sel.appendChild(opt);
        });
      }
    }

    // Inicializar DataTable de búsqueda (diferido al primer uso)
    $('#modal-search-articulos').on('shown.bs.modal', function() {
      if ( !oSearchTabla ) {
        oSearchTabla = $('#tabla-search-articulos').DataTable({
          processing: true,
          serverSide: false,
          ajax: {
            url: path.model + 'vales.php',
            type: 'POST',
            data: { action: 'search_articulos', query: '' },
            dataSrc: function(json) {
              return (json.result && json.data) ? json.data : [];
            }
          },
          columns: [
            { data: 'codigo', className: 'fw-bold' },
            { data: 'descripcion' },
            { data: 'familia' },
            { data: 'unidadmedida' },
            {
              data: 'existencia',
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
              data: null,
              className: 'text-center',
              orderable: false,
              render: function(row) {
                return '<i class="fas fa-check-circle text-success" style="cursor:pointer;font-size:1.2rem" title="Seleccionar" onclick="seleccionarArticulo(' + row.id + ',\'' + escapeJs(row.codigo) + '\',\'' + escapeJs(row.descripcion) + '\',\'' + escapeJs(row.familia) + '\',\'' + escapeJs(row.unidadmedida) + '\',' + row.existencia + ')"></i>';
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
          order: [[1, 'asc']],
          pageLength: 10,
          lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
          responsive: true,
          dom: '<"d-flex justify-content-between align-items-center mb-3"lf>t<"d-flex justify-content-between align-items-center mt-3"ip>',
          drawCallback: function() {
            // Hacer click en fila para seleccionar
            $('#tabla-search-articulos tbody tr').css('cursor', 'pointer').on('click', function() {
              var data = oSearchTabla.row(this).data();
              if (data) {
                seleccionarArticulo(data.id, data.codigo, data.descripcion, data.familia, data.unidadmedida, data.existencia);
              }
            });
          }
        });
      } else {
        oSearchTabla.search('').draw();
        oSearchTabla.ajax.reload();
      }
    });

    $('#modal-form').on('hidden.bs.modal', function() {
      resetForm();
    });
  });

  function resetForm() {
    $('#form-vale')[0].reset();
    $('#form-vale :input').prop('disabled', false);
    $('.btn-search-code').prop('disabled', false);
    $('.btn-remove-row').removeClass('d-none');
    $('#f_id').val('');
    $('#f_folio').val('');
    $('#f_quienentrega').empty().append('<option value="">-- Seleccione --</option>');
    $('#f_quienrecibe').empty().append('<option value="">-- Seleccione --</option>');
    $('#detalle-body').empty();
    $('#detalle-foot').hide();
    rowData = [];
    rowCount = 0;
    editId = null;
    modoLectura = false;
    document.getElementById('f_fecha').value = new Date().toISOString().substr(0, 10);
  }

  function cargarTrabajadores(tipo, callback) {
    if (typeof tipo === 'function') { callback = tipo; tipo = ''; }
    MsgServer(path.model + 'trabajadores.php', function(dat) {
      if (!dat.result || !dat.data) { if (callback) callback(); return; }
      var selE = document.getElementById('f_quienentrega');
      var selR = document.getElementById('f_quienrecibe');
      var bodegueroVal = '';
      dat.data.forEach(function(t) {
        var opt = document.createElement('option');
        opt.value = t.nombre;
        opt.textContent = t.nombre + (t.departamento ? ' (' + t.departamento + ')' : '');
        selE.appendChild(opt);
        selR.appendChild(opt.cloneNode(true));
        if (t.bodeguero == 1 && !bodegueroVal) bodegueroVal = t.nombre;
      });
      if (bodegueroVal) {
        if (tipo == 'E') selR.value = bodegueroVal;
        else if (tipo == 'S') selE.value = bodegueroVal;
      }
      if (callback) callback();
    }, { action: 'list_activos' });
  }

  function cambioTipo() {
    var sel = document.getElementById('f_tipomov');
    var opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;
    document.getElementById('f_id_tipomov').value = opt.getAttribute('data-id');
    document.getElementById('f_signo').value = opt.getAttribute('data-signo');
    generarFolio(opt.value);
    asignarBodeguero(opt.value);
  }

  function asignarBodeguero(tipo) {
    if (!tipo) return;
    // Reload selects with tipo so bodeguero goes to the right field
    $('#f_quienentrega').empty().append('<option value="">-- Seleccione --</option>');
    $('#f_quienrecibe').empty().append('<option value="">-- Seleccione --</option>');
    cargarTrabajadores(tipo);
  }

  function generarFolio(tipomov) {
    if (!tipomov) return;
    MsgServer(path.model + 'vales.php', function(dat) {
      if (dat.result) $('#f_folio').val(dat.folio);
    }, { action: 'next_folio', tipomov: tipomov }, false);
  }

  function nuevoVale() {
    modoLectura = false;
    editId = null;
    document.getElementById('modal-title-text').textContent = 'Nuevo Vale';
    resetForm();
    cargarTrabajadores();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-form')).show();
    setTimeout(function() { document.getElementById('f_tipomov').focus(); }, 500);
  }

function solicitarClaveEditar(id) {
    modoLectura = false;
    editarVale(id);
  }

  function verVale(id) {
    modoLectura = true;
    editarVale(id);
  }

  function aplicarModoLectura() {
    if (!modoLectura) return;
    $('#form-vale :input').prop('disabled', true);
    $('.btn-search-code').prop('disabled', true);
    $('.btn-remove-row').addClass('d-none');
  }

  function editarVale(id) {
    editId = id;
    MsgServer(path.model + 'vales.php', cargarVale, { action: 'get', id: id });

    function cargarVale(dat) {
      if (!dat.result || !dat.data) {
        JError(dat.message || 'Error al cargar vale');
        return;
      }
      var v = dat.data;
      document.getElementById('modal-title-text').textContent = (modoLectura ? 'Ver Vale: ' : 'Editar Vale: ') + v.folio;
      $('#f_id').val(v.id);
      $('#f_folio').val(v.folio);
      $('#f_fecha').val(v.fecha);
      $('#f_tipomov').val(v.tipomov);
      document.getElementById('f_id_tipomov').value = v.id_tipomov;
      document.getElementById('f_signo').value = v.signo;
      cargarTrabajadores(v.tipomov, function() {
        $('#f_quienentrega').val(v.quienentrega);
        $('#f_quienrecibe').val(v.quienrecibe);
      });
      $('#f_descripcion_trabajo').val(v.descripcion_trabajo);
      $('#f_observaciones').val(v.observaciones);

      $('#detalle-body').empty();
      rowData = [];
      rowCount = 0;

      if (v.detalles && v.detalles.length > 0) {
        v.detalles.forEach(function(d) {
          agregarFila(d);
        });
      }

      aplicarModoLectura();
      bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-form')).show();
    }
  }

  function agregarFila(data) {
    rowCount++;
    var idx = rowCount;
    var d = data || { id_articulo: '', codigo: '', producto: '', categoria: '', unidad: '', existencia_actual: 0, cantidad: '' };

    rowData[idx] = {
      id_articulo: d.id_articulo || '',
      codigo: d.codigo || '',
      producto: d.producto || '',
      categoria: d.categoria || '',
      unidad: d.unidad || '',
      existencia_actual: d.existencia_actual || 0,
      cantidad: d.cantidad || ''
    };

    var html = '<tr id="fila-' + idx + '">' +
      '<td><div class="input-group input-group-sm input-group-code">' +
        '<input type="text" class="form-control form-control-sm detalle-codigo" id="cod-' + idx + '" value="' + d.codigo + '" onblur="buscarArticulo(' + idx + ')" placeholder="Código">' +
        '<button class="btn btn-outline-primary btn-search-code" type="button" title="Buscar artículo" onclick="abrirBusquedaArticulos(' + idx + ')">' +
          '<i class="fas fa-search"></i>' +
        '</button>' +
      '</div></td>' +
      '<td><input type="text" class="form-control form-control-sm detalle-producto" id="prod-' + idx + '" value="' + d.producto + '" readonly></td>' +
      '<td><input type="text" class="form-control form-control-sm" id="cat-' + idx + '" value="' + d.categoria + '" readonly></td>' +
      '<td><input type="text" class="form-control form-control-sm" id="und-' + idx + '" value="' + d.unidad + '" readonly></td>' +
      '<td><input type="text" class="form-control form-control-sm text-end" id="exis-' + idx + '" value="' + formatNumber(d.existencia_actual, 2) + '" readonly></td>' +
      '<td><input type="number" class="form-control form-control-sm detalle-cantidad" id="cant-' + idx + '" value="' + d.cantidad + '" min="0" step="0.01" onchange="actualizarTotales()" placeholder="0"></td>' +
      '<td class="text-center"><span class="btn-remove-row" onclick="eliminarFila(' + idx + ')"><i class="fas fa-times-circle"></i></span></td>' +
      '</tr>';

    $('#detalle-body').append(html);
    $('#detalle-foot').show();
    actualizarTotales();

    // Si es nueva fila sin data, enfocar código
    if (!data) {
      setTimeout(function() {
        document.getElementById('cod-' + idx).focus();
      }, 200);
    }
  }

  function eliminarFila(idx) {
    $('#fila-' + idx).remove();
    delete rowData[idx];
    actualizarTotales();
    if ($('#detalle-body tr').length === 0) {
      $('#detalle-foot').hide();
    }
  }

  function buscarArticulo(idx) {
    var codigo = document.getElementById('cod-' + idx).value.trim();
    if (!codigo) return;

    MsgServer(path.model + 'vales.php', function(dat) {
      if (dat.result && dat.data) {
        var a = dat.data;
        document.getElementById('prod-' + idx).value = a.descripcion;
        document.getElementById('cat-' + idx).value = a.familia;
        document.getElementById('und-' + idx).value = a.unidadmedida;
        document.getElementById('exis-' + idx).value = formatNumber(a.existencia, 2);
        rowData[idx] = rowData[idx] || {};
        rowData[idx].id_articulo = a.id;
        rowData[idx].codigo = a.codigo;
        rowData[idx].producto = a.descripcion;
        rowData[idx].categoria = a.familia;
        rowData[idx].unidad = a.unidadmedida;
        rowData[idx].existencia_actual = a.existencia;
        setTimeout(function() { document.getElementById('cant-' + idx).focus(); }, 200);
      } else {
        document.getElementById('prod-' + idx).value = '';
        document.getElementById('cat-' + idx).value = '';
        document.getElementById('und-' + idx).value = '';
        document.getElementById('exis-' + idx).value = '0.00';
        rowData[idx] = rowData[idx] || {};
        rowData[idx].id_articulo = '';
        JError(dat.message || 'Artículo no encontrado');
      }
    }, { action: 'get_articulo', codigo: codigo }, false);
  }

  var oSearchTabla = null;

  function abrirBusquedaArticulos(idx) {
    searchTargetIdx = idx;
    if ( oSearchTabla ) {
      oSearchTabla.search('').draw();
      oSearchTabla.ajax.reload();
    }
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-search-articulos')).show();
    setTimeout(function() {
      if ( oSearchTabla ) {
        $('div.dataTables_filter input', '#modal-search-articulos').focus();
      }
    }, 500);
  }

  function seleccionarArticulo(id, codigo, descripcion, familia, unidadmedida, existencia) {
    var idx = searchTargetIdx;
    if (!idx) return;

    document.getElementById('cod-' + idx).value = codigo;
    document.getElementById('prod-' + idx).value = descripcion;
    document.getElementById('cat-' + idx).value = familia;
    document.getElementById('und-' + idx).value = unidadmedida;
    document.getElementById('exis-' + idx).value = formatNumber(existencia, 2);
    rowData[idx] = rowData[idx] || {};
    rowData[idx].id_articulo = id;
    rowData[idx].codigo = codigo;
    rowData[idx].producto = descripcion;
    rowData[idx].categoria = familia;
    rowData[idx].unidad = unidadmedida;
    rowData[idx].existencia_actual = existencia;

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-search-articulos')).hide();
    setTimeout(function() { document.getElementById('cant-' + idx).focus(); }, 300);
  }

  function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
  }

  function escapeJs(text) {
    if (!text) return '';
    return text.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '&quot;').replace(/\n/g, '\\n');
  }

  function actualizarTotales() {
    var total = 0;
    var filas = $('#detalle-body tr');
    filas.each(function() {
      var id = this.id.split('-')[1];
      var cant = parseFloat(document.getElementById('cant-' + id).value) || 0;
      total += cant;
    });
    document.getElementById('total-piezas').textContent = formatNumber(total, 2);
  }

  function guardarVale() {
    var id_tipomov = document.getElementById('f_id_tipomov').value;
    var tipomov = document.getElementById('f_tipomov').value;
    var folio = document.getElementById('f_folio').value.trim();
    var fecha = document.getElementById('f_fecha').value;

    if (!tipomov) { JError('Seleccione el tipo de movimiento'); return; }
    if (!fecha) { JError('Seleccione la fecha'); return; }
    var quienentrega = ($('#f_quienentrega').val() || '').trim();
    var quienrecibe = ($('#f_quienrecibe').val() || '').trim();
    if (!quienentrega) { JError('Seleccione quien entrega'); return; }
    if (!quienrecibe) { JError('Seleccione quien recibe'); return; }

    var detalles = [];
    var filas = $('#detalle-body tr');
    if (filas.length === 0) { JError('Agregue al menos un artículo'); return; }

    filas.each(function() {
      var id = this.id.split('-')[1];
      var id_art = rowData[id] ? rowData[id].id_articulo : '';
      var cod = document.getElementById('cod-' + id).value.trim();
      var prod = document.getElementById('prod-' + id).value.trim();
      var cat = document.getElementById('cat-' + id).value.trim();
      var und = document.getElementById('und-' + id).value.trim();
      var exis = parseFloat(document.getElementById('exis-' + id).value.replace(',', '.')) || 0;
      var cant = parseFloat(document.getElementById('cant-' + id).value) || 0;

      if (!cod || !prod || cant <= 0) return;

      detalles.push({
        id_articulo: id_art,
        codigo: cod,
        producto: prod,
        categoria: cat,
        unidad: und,
        existencia_actual: exis,
        cantidad: cant
      });
    });

    if (detalles.length === 0) { JError('Debe completar al menos una fila con código, producto y cantidad > 0'); return; }

    var aPar = {
      action: 'save',
      id: $('#f_id').val(),
      folio: folio,
      fecha: fecha,
      id_tipomov: id_tipomov,
      tipomov: tipomov,
      quienentrega: quienentrega,
      quienrecibe: quienrecibe,
      descripcion_trabajo: ($('#f_descripcion_trabajo').val() || '').trim(),
      observaciones: ($('#f_observaciones').val() || '').trim(),
      detalles: JSON.stringify(detalles)
    };

    MsgServer(path.model + 'vales.php', function(dat, errMsg) {
      if (dat && dat.result) {
        JSuccess(dat.message || 'Vale guardado');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-form')).hide();
        oTabla.ajax.reload(null, false);
      } else {
        var msg = (dat && dat.message) ? dat.message : (errMsg || 'Error al guardar el vale');
        JError(msg);
      }
    }, aPar);
  }

function solicitarClaveEliminar(id, folio) {
    eliminarVale(id, folio);
  }

  function eliminarVale(id, folio) {
    JMsgYesNo("¿Eliminar vale <b>" + folio + "</b>?<br>Se revertirá el stock de los artículos.", function() {
      MsgServer(path.model + 'vales.php', function(dat) {
        if (dat.result) {
          JSuccess(dat.message);
          oTabla.ajax.reload(null, false);
        } else {
          JError(dat.message || 'Error al eliminar');
        }
      }, { action: 'delete', id: id });
    });
  }

  function imprimirVale(id) {
    window.open('vales_print.php?id=' + id, '_blank');
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
