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
if ( $rolUsuario !== 'admin' ) {
  header("Location: menu.php");
  exit;
}

$oWeb = new TWeb( APP_TITLE . ' - Usuarios' );
$oWeb->lAwesome = true;
$oWeb->SetIcon( IMAGE_PATH . 'favicon.ico' );
$oWeb->SetFontFamily( FONT_FAMILY );
$oWeb->Activate();

$nombreUsuario = $oSession->GetVar('usuario');
?>

<div class="container-fluid p-0">
  <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm py-3 px-4">
    <a class="navbar-brand fw-bold" href="#">
      <i class="fas fa-users-cog me-2"></i> Control de Inventario - Administraci&oacute;n
    </a>
    <div class="ms-auto d-flex align-items-center">
      <a href="menu.php" class="text-white me-3 fw-bold small">
        <i class="fas fa-arrow-left me-1"></i> Volver al Men&uacute;
      </a>
      <span class="text-white me-3">
        <i class="fas fa-user-circle me-1"></i> <span class="fw-bold"><?php echo htmlspecialchars($nombreUsuario); ?></span>
        <span class="badge badge-light ms-1">ADMIN</span>
      </span>
      <button class="btn btn-outline-light btn-sm fw-bold" onclick="logout()">
        Cerrar Sesi&oacute;n <i class="fas fa-sign-out-alt ms-1"></i>
      </button>
    </div>
  </nav>

  <div class="container-fluid my-4 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold text-dark mb-0">
        <i class="fas fa-user-shield me-2 text-danger"></i> Gesti&oacute;n de Usuarios
      </h4>
      <button class="btn btn-danger fw-bold px-4 shadow-sm" onclick="nuevoUsuario()">
        <i class="fas fa-plus me-2"></i> Nuevo Usuario
      </button>
    </div>

    <div class="card border-0 shadow-lg" style="border-radius: 16px;">
      <div class="card-body p-4">
        <table id="tabla-usuarios" class="table table-hover table-striped w-100">
          <thead class="bg-danger text-white">
            <tr>
              <th>ID</th>
              <th>USUARIO</th>
              <th>NOMBRE</th>
              <th>ROL</th>
              <th>FECHA ALTA</th>
              <th style="width:180px">ACCIONES</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Usuario -->
<div class="modal fade" id="modal-usuario" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow" style="border-radius: 16px;">
      <div class="modal-header bg-danger text-white" style="border-radius: 16px 16px 0 0;">
        <h5 class="modal-title fw-bold" id="modal-usuario-titulo">
          <i class="fas fa-user me-2"></i> Nuevo Usuario
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <input type="hidden" id="edit-id" value="0">
        <div class="form-group">
          <label class="fw-bold small">Usuario de acceso</label>
          <input type="text" id="edit-user" class="form-control" placeholder="ej. jperez" maxlength="50">
        </div>
        <div class="form-group">
          <label class="fw-bold small">Nombre completo</label>
          <input type="text" id="edit-username" class="form-control" placeholder="ej. Juan P&eacute;rez" maxlength="150">
        </div>
        <div class="form-group">
          <label class="fw-bold small">
            Contrase&ntilde;a
            <small class="text-muted fw-normal" id="clave-ayuda"> (dejar vac&iacute;o para no cambiar)</small>
          </label>
          <input type="password" id="edit-clave" class="form-control" placeholder="••••••••" maxlength="100">
        </div>
        <div class="form-group">
          <label class="fw-bold small">Rol</label>
          <select id="edit-rol" class="form-control">
            <option value="operador">Operador</option>
            <option value="consultor">Consultor</option>
            <option value="supervisor">Supervisor</option>
            <option value="admin">Administrador</option>
          </select>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0 px-4 pb-4">
        <button class="btn btn-secondary fw-bold px-4" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-danger fw-bold px-4" onclick="guardarUsuario()">
          <i class="fas fa-save me-2"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  var oTabla;

  $(function() {
    oTabla = $('#tabla-usuarios').DataTable({
      processing: true,
      ajax: {
        url: path.model + 'usuarios.php',
        type: 'POST',
        data: { action: 'list' },
        dataSrc: function(j) { return (j.result && j.data) ? j.data : []; }
      },
      columns: [
        { data: 'id', className: 'text-center' },
        { data: 'user', className: 'fw-bold' },
        { data: 'username' },
        {
          data: 'rol',
          className: 'text-center',
          render: function(d) {
            if (d == 'admin') return '<span class="badge badge-danger px-3 py-1">ADMIN</span>';
            if (d == 'supervisor') return '<span class="badge badge-warning text-dark px-3 py-1">SUPERVISOR</span>';
            if (d == 'consultor') return '<span class="badge badge-secondary px-3 py-1">CONSULTOR</span>';
            return '<span class="badge badge-info px-3 py-1">OPERADOR</span>';
          }
        },
        {
          data: 'created_at',
          render: function(d) { return d ? d.substr(0,10) : ''; }
        },
        {
          data: null,
          className: 'text-center',
          orderable: false,
          render: function(row) {
            var html = '<button class="btn btn-sm btn-outline-primary me-1" title="Editar" onclick="editarUsuario(' + row.id + ')"><i class="fas fa-edit"></i></button>';
            if (row.id != 1) {
              html += '<button class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="eliminarUsuario(' + row.id + ', \'' + escapeOnclick(row.username) + '\')"><i class="fas fa-trash"></i></button>';
            }
            return html;
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

  function nuevoUsuario() {
    document.getElementById('edit-id').value = 0;
    document.getElementById('edit-user').value = '';
    document.getElementById('edit-username').value = '';
    document.getElementById('edit-clave').value = '';
    document.getElementById('edit-clave').required = true;
    document.getElementById('edit-rol').value = 'operador';
    document.getElementById('clave-ayuda').style.display = 'none';
    document.getElementById('modal-usuario-titulo').innerHTML = '<i class="fas fa-user-plus me-2"></i> Nuevo Usuario';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-usuario')).show();
    setTimeout(function() { document.getElementById('edit-user').focus(); }, 300);
  }

  function editarUsuario(id) {
    MsgServer(path.model + 'usuarios.php', function(dat) {
      if (dat.result && dat.data) {
        var u = dat.data;
        document.getElementById('edit-id').value = u.id;
        document.getElementById('edit-user').value = u.user;
        document.getElementById('edit-username').value = u.username;
        document.getElementById('edit-clave').value = '';
        document.getElementById('edit-clave').required = false;
        document.getElementById('edit-rol').value = u.rol;
        document.getElementById('clave-ayuda').style.display = '';
        document.getElementById('modal-usuario-titulo').innerHTML = '<i class="fas fa-user-edit me-2"></i> Editar Usuario';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-usuario')).show();
      } else {
        JError(dat.message || 'Error al obtener usuario');
      }
    }, { action: 'get', id: id });
  }

  function guardarUsuario() {
    var id = document.getElementById('edit-id').value;
    var user = document.getElementById('edit-user').value.trim();
    var username = document.getElementById('edit-username').value.trim();
    var clave = document.getElementById('edit-clave').value;
    var rol = document.getElementById('edit-rol').value;

    if (!user) { JError('El usuario de acceso es obligatorio'); return; }
    if (!username) { JError('El nombre es obligatorio'); return; }
    if (id == 0 && !clave) { JError('La contraseña es obligatoria para nuevos usuarios'); return; }

    MsgServer(path.model + 'usuarios.php', function(dat) {
      if (dat.result) {
        JSuccess(dat.message);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-usuario')).hide();
        oTabla.ajax.reload(null, false);
      } else {
        JError(dat.message || 'Error al guardar');
      }
    }, { action: 'save', id: id, user: user, username: username, clave: clave, rol: rol });
  }

  function eliminarUsuario(id, nombre) {
    JMsgYesNo("¿Eliminar al usuario <b>" + escapeHtml(nombre) + "</b>?", function() {
      MsgServer(path.model + 'usuarios.php', function(dat) {
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
