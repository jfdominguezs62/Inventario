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

$rolUsuario = $oSession->GetVar('rol') ?: 'operador';
checkAcceso( $rolUsuario, ['admin', 'supervisor', 'operador'] );

$oWeb = new TWeb( APP_TITLE . ' - Trabajadores' );
$oWeb->lAwesome = true;
$oWeb->SetIcon( IMAGE_PATH . 'favicon.ico' );
$oWeb->SetFontFamily( FONT_FAMILY );
$oWeb->Activate();

$nombreUsuario = $oSession->GetVar('usuario');
?>

<div class="container-fluid p-0">
  <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm py-3 px-4">
    <a class="navbar-brand fw-bold" href="#">
      <i class="fas fa-hard-hat me-2"></i> Control de Inventario - Cat&aacute;logo de Trabajadores
    </a>
    <div class="ms-auto d-flex align-items-center">
      <a href="menu.php" class="text-white me-3 fw-bold small">
        <i class="fas fa-arrow-left me-1"></i> Volver al Men&uacute;
      </a>
      <span class="text-white me-3">
        <i class="fas fa-user-circle me-1"></i> <span class="fw-bold"><?php echo htmlspecialchars($nombreUsuario); ?></span>
        <span class="badge badge-light ms-1"><?php echo strtoupper($rolUsuario); ?></span>
      </span>
      <button class="btn btn-outline-light btn-sm fw-bold" onclick="logout()">
        Cerrar Sesi&oacute;n <i class="fas fa-sign-out-alt ms-1"></i>
      </button>
    </div>
  </nav>

  <div class="container-fluid my-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold text-dark mb-0">
        <i class="fas fa-people-carry me-2 text-danger"></i> Trabajadores
      </h4>
      <button class="btn btn-danger fw-bold px-4 shadow-sm" onclick="nuevoTrabajador()">
        <i class="fas fa-plus me-2"></i> Nuevo Trabajador
      </button>
    </div>

    <div class="card border-0 shadow-lg" style="border-radius: 16px;">
      <div class="card-body p-4">
        <table id="tabla-trabajadores" class="table table-hover table-striped w-100">
          <thead class="bg-danger text-white">
            <tr>
              <th>ID N&Oacute;MINA</th>
              <th>NOMBRE</th>
              <th>DEPARTAMENTO</th>
              <th>BODEGUERO</th>
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

<!-- Modal Trabajador -->
<div class="modal fade" id="modal-trabajador" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow" style="border-radius: 16px;">
      <div class="modal-header bg-danger text-white" style="border-radius: 16px 16px 0 0;">
        <h5 class="modal-title fw-bold" id="modal-trabajador-titulo">
          <i class="fas fa-user me-2"></i> Nuevo Trabajador
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" id="edit-id" value="0">
        <div class="row">
          <div class="form-group col-md-4">
            <label class="fw-bold small">ID N&Oacute;MINA</label>
            <input type="text" id="edit-idnomina" class="form-control" placeholder="00000" maxlength="30">
          </div>
          <div class="form-group col-md-8">
            <label class="fw-bold small">Nombre completo <span class="text-danger">*</span></label>
            <input type="text" id="edit-nombre" class="form-control" placeholder="Nombre del trabajador" maxlength="150">
          </div>
        </div>
        <div class="form-group">
          <label class="fw-bold small">Departamento</label>
          <input type="text" id="edit-departamento" class="form-control" placeholder="Departamento o &aacute;rea" maxlength="100">
        </div>
        <div class="form-group">
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="edit-bodeguero">
            <label class="form-check-label fw-bold" for="edit-bodeguero">Bodeguero</label>
            <small class="text-muted d-block">Seleccionar si es el encargado del almac&eacute;n (aparecer&aacute; por defecto en "Quien Entrega")</small>
          </div>
        </div>
        <div class="form-group mb-0">
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="edit-estatus" checked>
            <label class="form-check-label fw-bold" for="edit-estatus">Activo</label>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0 px-4 pb-4">
        <button class="btn btn-secondary fw-bold px-4" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-danger fw-bold px-4" onclick="guardarTrabajador()">
          <i class="fas fa-save me-2"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  var oTabla;

  $(function() {
    oTabla = $('#tabla-trabajadores').DataTable({
      processing: true,
      ajax: {
        url: path.model + 'trabajadores.php',
        type: 'POST',
        data: { action: 'list' },
        dataSrc: function(j) { return (j.result && j.data) ? j.data : []; }
      },
      columns: [
        { data: 'idnomina', className: 'fw-bold text-center' },
        { data: 'nombre' },
        { data: 'departamento' },
        {
          data: 'bodeguero',
          className: 'text-center',
          render: function(d) {
            return d == 1
              ? '<span class="badge badge-success px-3 py-1"><i class="fas fa-check me-1"></i> Bodeguero</span>'
              : '<span class="text-muted">&mdash;</span>';
          }
        },
        {
          data: 'estatus',
          className: 'text-center',
          render: function(d) {
            return d == 1
              ? '<span class="badge badge-success px-3 py-1">Activo</span>'
              : '<span class="badge badge-secondary px-3 py-1">Inactivo</span>';
          }
        },
        {
          data: null,
          className: 'text-center',
          orderable: false,
          render: function(row) {
            return '<button class="btn btn-sm btn-outline-primary me-1" title="Editar" onclick="editarTrabajador(' + row.id + ')"><i class="fas fa-edit"></i></button>' +
                   '<button class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="eliminarTrabajador(' + row.id + ', \'' + escapeOnclick(row.nombre) + '\')"><i class="fas fa-trash"></i></button>';
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
      responsive: true,
      dom: '<"d-flex justify-content-between align-items-center mb-3"lf>t<"d-flex justify-content-between align-items-center mt-3"ip>'
    });
  });

  function nuevoTrabajador() {
    document.getElementById('edit-id').value = 0;
    document.getElementById('edit-idnomina').value = '';
    document.getElementById('edit-nombre').value = '';
    document.getElementById('edit-departamento').value = '';
    document.getElementById('edit-bodeguero').checked = false;
    document.getElementById('edit-estatus').checked = true;
    document.getElementById('modal-trabajador-titulo').innerHTML = '<i class="fas fa-user-plus me-2"></i> Nuevo Trabajador';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-trabajador')).show();
    setTimeout(function() { document.getElementById('edit-idnomina').focus(); }, 300);
  }

  function editarTrabajador(id) {
    MsgServer(path.model + 'trabajadores.php', function(dat) {
      if (dat.result && dat.data) {
        var t = dat.data;
        document.getElementById('edit-id').value = t.id;
        document.getElementById('edit-idnomina').value = t.idnomina || '';
        document.getElementById('edit-nombre').value = t.nombre;
        document.getElementById('edit-departamento').value = t.departamento || '';
        document.getElementById('edit-bodeguero').checked = t.bodeguero == 1;
        document.getElementById('edit-estatus').checked = t.estatus == 1;
        document.getElementById('modal-trabajador-titulo').innerHTML = '<i class="fas fa-user-edit me-2"></i> Editar Trabajador';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-trabajador')).show();
      } else {
        JError(dat.message || 'Error al obtener trabajador');
      }
    }, { action: 'get', id: id });
  }

  function guardarTrabajador() {
    var id = document.getElementById('edit-id').value;
    var nombre = document.getElementById('edit-nombre').value.trim();
    if (!nombre) { JError('El nombre es obligatorio'); return; }

    MsgServer(path.model + 'trabajadores.php', function(dat) {
      if (dat.result) {
        JSuccess(dat.message);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-trabajador')).hide();
        oTabla.ajax.reload(null, false);
      } else {
        JError(dat.message || 'Error al guardar');
      }
    }, {
      action: 'save',
      id: id,
      idnomina: document.getElementById('edit-idnomina').value.trim(),
      nombre: nombre,
      departamento: document.getElementById('edit-departamento').value.trim(),
      bodeguero: document.getElementById('edit-bodeguero').checked ? 1 : 0,
      estatus: document.getElementById('edit-estatus').checked ? 1 : 0
    });
  }

  function eliminarTrabajador(id, nombre) {
    JMsgYesNo("¿Eliminar a <b>" + escapeHtml(nombre) + "</b>?", function() {
      MsgServer(path.model + 'trabajadores.php', function(dat) {
        if (dat.result) {
          JSuccess(dat.message);
          oTabla.ajax.reload(null, false);
        } else {
          JError(dat.message || 'Error al eliminar');
        }
      }, { action: 'delete', id: id });
    });
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

  function logout() {
    JMsgYesNo("¿Cerrar sesión?", function() {
      MsgServer(path.model + 'login.php', function(dat) { if (dat.result) location.href = '../index.php'; }, { action: 'logout' });
    });
  }
</script>

<?php $oWeb->End(); ?>
