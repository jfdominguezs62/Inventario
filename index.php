<?php
include( 'constants.php' );
Constants::unset();

include( Constants::getpath_root() . 'config.php' );
include( Constants::getpath_tweb() . 'core.php' );
include( Constants::getpath_tweb() . 'core.login.php' );

$oWeb = new TWeb( APP_TITLE );
$oWeb->lAwesome = true; 
$oWeb->SetIcon( IMAGE_PATH . 'logoEsapah.jpeg' );
$oWeb->SetFontFamily( FONT_FAMILY );
$oWeb->Activate();

$oLogin = new TLogin( 'myLogin' );
$oLogin->cImage           = IMAGE_PATH . 'logoEsapah.png';
$oLogin->cTitle           = 'Inventario V2.0';
$oLogin->cTextUser        = 'Usuario / Correo';
$oLogin->cTextPassword    = 'Contraseña';
$oLogin->lShowPassword    = false;
$oLogin->cTextLogin       = 'Iniciar Sesión';
$oLogin->cTextRegister    = 'Registrarse';
$oLogin->cTextForgot      = '';
$oLogin->bActionLogin     = 'login()';
$oLogin->bActionRegister  = 'register()';
$oLogin->Developer( '© Inventario', ' v1.0', 'creditos()' );
$oLogin->Activate();

$oWeb->End();
?>

<!-- Modal de Registro -->
<div class="modal fade" id="registerModal" tabindex="-1" role="dialog" aria-labelledby="registerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header" style="background: linear-gradient(135deg, #43a047, #2e7d32); color: white;">
        <h5 class="modal-title fw-bold" id="registerModalLabel">Crear Nueva Cuenta</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" style="background-color: #f8f9fa;">
        <form id="form-register" action="javascript:doRegister();" method="post">
          <div class="form-group mb-3">
            <label for="reg-username" class="text-secondary fw-bold">Nombre Completo</label>
            <input type="text" class="form-control" id="reg-username" placeholder="Ej. Juan Pérez" required style="border-radius: 8px;">
          </div>
          <div class="form-group mb-3">
            <label for="reg-email" class="text-secondary fw-bold">Usuario / Correo</label>
            <input type="text" class="form-control" id="reg-email" placeholder="Ej. juan@correo.com" required style="border-radius: 8px;">
          </div>
          <div class="form-group mb-4">
            <label for="reg-password" class="text-secondary fw-bold">Contraseña</label>
            <input type="password" class="form-control" id="reg-password" placeholder="Mínimo 6 caracteres" required style="border-radius: 8px;">
          </div>
          <button type="submit" class="btn btn-primary w-100 btn-lg fw-bold shadow" style="border-radius: 8px; background: linear-gradient(135deg, #43a047, #2e7d32); border: none;">
            Registrarse <i class="fas fa-user-plus ms-2"></i>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
  /* Tema tvalweb2 - Identidad verde ESAPAH */
  body {
    min-height: 100vh;
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 55%, #a5d6a7 100%);
  }

  #myLogin .modal-content {
    border-radius: 14px;
  }

  #myLogin .avatar {
    background: #2e7d32;
  }

  #myLogin .btn.login-btn {
    background: #2e7d32;
    font-size: 20px;
  }

  #myLogin .btn.login-btn:hover,
  #myLogin .btn.login-btn:focus {
    background: #1b5e20;
    outline: none;
  }

  #myLogin .form-control:focus {
    border-color: #43a047;
    box-shadow: 0 0 0 0.25rem rgba(46, 125, 50, 0.25);
  }

  #myLogin .modal-footer {
    background: #e8f5e9;
    border-color: #c8e6c9;
    border-radius: 0 0 14px 14px;
  }
</style>

<script>
  var oLogin;

  $(function() {
    SetMessageServer("Cargando sistema...");
    oLogin = new TLogin("myLogin");
    oLogin.show();
    setTimeout(function() { $('#username').focus(); }, 500);

    // BS5: el botón X del modal TLogin usa data-dismiss (BS4). El modal es backdrop
    // estático con keyboard=false, así que habilitamos el cierre manualmente.
    $(document).on('click', '#myLogin [data-dismiss="modal"]', function(e) {
      e.preventDefault();
      oLogin.hide();
    });
  });

  function login() { 	
    var aPar = {};
  	aPar.action   = 'login';
  	aPar.usuario  = oLogin.getuser();
    aPar.clave    = oLogin.getpassword();

    MsgServer( path.model + 'login.php', response_login, aPar );					

    function response_login( dat ) {
  	  if ( dat.result ) {
        oLogin.hide();
        JSuccess("¡Acceso concedido!");
        location.href = path.view + 'menu.php';
      } else {
        JError("Acceso denegado. Verifique usuario o contraseña.");
  	  }
    }  
  }	
  
  function register() {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('registerModal')).show();
  }

  function doRegister() {
    var aPar = {};
    aPar.action   = 'register';
    aPar.nombre   = $('#reg-username').val();
    aPar.usuario  = $('#reg-email').val();
    aPar.clave    = $('#reg-password').val();

    MsgServer( path.model + 'login.php', response_register, aPar );

    function response_register( dat ) {
      if ( dat.result ) {
        JSuccess("¡Registro exitoso! Ya puedes iniciar sesión.");
        bootstrap.Modal.getOrCreateInstance(document.getElementById('registerModal')).hide();
        // Limpiar campos
        $('#reg-username').val('');
        $('#reg-email').val('');
        $('#reg-password').val('');
      } else {
        JError(dat.message || "Error al registrar el usuario.");
      }
    }
  }

  function creditos() {
    JMsgInfo("© Inventario App 2026");
  }
</script>
