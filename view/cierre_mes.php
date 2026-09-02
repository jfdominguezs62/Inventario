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
if ( !in_array($rolUsuario, ['admin', 'supervisor']) ) {
  header("Location: menu.php");
  exit;
}

$oWeb = new TWeb( APP_TITLE . ' - Cierre de Mes' );
$oWeb->lAwesome = true;
$oWeb->SetIcon( IMAGE_PATH . 'favicon.ico' );
$oWeb->SetFontFamily( FONT_FAMILY );
$oWeb->Activate();

$nombreUsuario = $oSession->GetVar('usuario');
?>

<div class="container-fluid p-0">
  <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm py-3 px-4">
    <a class="navbar-brand fw-bold" href="#">
      <i class="fas fa-calendar-alt me-2"></i> Control de Inventario - Cierre de Mes
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

  <div class="container my-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card border-0 shadow-lg" style="border-radius: 16px;">
          <div class="card-body p-5 text-center">
            <div class="mb-4">
              <div class="rounded-circle bg-warning d-inline-flex justify-content-center align-items-center" style="width: 90px; height: 90px;">
                <i class="fas fa-sync-alt text-white" style="font-size: 40px;"></i>
              </div>
            </div>
            <h3 class="fw-bold text-dark mb-2">Cierre de Mes</h3>
            <p class="text-muted mb-4">
              Esta opci&oacute;n copiar&aacute; la <strong>existencia f&iacute;sica</strong> a la
              <strong>existencia inicial</strong> y pondr&aacute; las entradas y salidas en cero
              para todos los art&iacute;culos, preparando el inventario para un nuevo mes.
            </p>
            <div class="alert alert-warning text-start small py-3 px-4" style="border-radius: 12px;">
              <i class="fas fa-exclamation-triangle me-2"></i>
              <strong>&iexcl;Atenci&oacute;n!</strong> Esta acci&oacute;n no se puede deshacer.
              Todos los movimientos del mes anterior quedar&aacute;n registrados en los vales,
              pero los acumuladores de entradas/salidas se reiniciar&aacute;n.
            </div>
            <button class="btn btn-warning btn-lg fw-bold px-5 mt-3 shadow" onclick="confirmarCierre()">
              <i class="fas fa-play me-2"></i> Iniciar Nuevo Mes
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Confirmaci&oacute;n con Contrase&ntilde;a -->
<div class="modal fade" id="modal-confirmar" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow" style="border-radius: 16px;">
      <div class="modal-header bg-warning text-dark" style="border-radius: 16px 16px 0 0;">
        <h5 class="modal-title fw-bold">
          <i class="fas fa-lock me-2"></i> Confirmar Cierre
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4 text-center">
        <p class="text-muted mb-3">Ingresa tu contrase&ntilde;a para confirmar esta acci&oacute;n.</p>
        <input type="password" id="clave-confirmacion" class="form-control text-center fw-bold" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" style="font-size: 24px; letter-spacing: 6px;" maxlength="100" autocomplete="off">
      </div>
      <div class="modal-footer border-0 pt-0 px-4 pb-4 justify-content-center">
        <button class="btn btn-secondary fw-bold px-4" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-warning fw-bold px-4" id="btn-ejecutar" onclick="ejecutarCierre()">
          <i class="fas fa-check me-2"></i> Confirmar y Ejecutar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  function confirmarCierre() {
    document.getElementById('clave-confirmacion').value = '';
    document.getElementById('btn-ejecutar').disabled = false;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-confirmar')).show();
    setTimeout(function() { document.getElementById('clave-confirmacion').focus(); }, 300);
  }

  $('#modal-confirmar').on('hidden.bs.modal', function() {
    document.getElementById('btn-ejecutar').disabled = false;
  });

  function ejecutarCierre() {
    var clave = document.getElementById('clave-confirmacion').value;
    if (!clave) { JError('Debes ingresar tu contrase&ntilde;a'); return; }

    document.getElementById('btn-ejecutar').disabled = true;
    JMsgYesNo(
      '<i class="fas fa-exclamation-triangle text-warning me-2"></i> ' +
      '&iquest;Est&aacute;s completamente seguro?<br><small class="text-muted">' +
      'Se reiniciar&aacute;n entradas, salidas e inventariado de todos los art&iacute;culos.</small>',
      function() {
        MsgServer(path.model + 'cierre_mes.php', function(dat) {
          document.getElementById('btn-ejecutar').disabled = false;
          if (dat.result) {
            JSuccess(dat.message || 'Cierre realizado correctamente');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-confirmar')).hide();
            setTimeout(function() { location.href = 'menu.php'; }, 1500);
          } else {
            JError(dat.message || 'Error al ejecutar el cierre');
          }
        }, { action: 'ejecutar', clave: clave });
      }
    );
  }

  function logout() {
    JMsgYesNo("&iquest;Cerrar sesi&oacute;n?", function() {
      MsgServer(path.model + 'login.php', function(dat) { if (dat.result) location.href = '../index.php'; }, { action: 'logout' });
    });
  }
</script>

<?php $oWeb->End(); ?>
