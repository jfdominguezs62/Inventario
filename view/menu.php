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

$oWeb = new TWeb( APP_TITLE );
$oWeb->lAwesome = true; 
$oWeb->SetIcon( IMAGE_PATH . 'favicon.ico' );
$oWeb->SetFontFamily( FONT_FAMILY );
$oWeb->Activate();

$nombreUsuario = $oSession->GetVar('usuario');
$rolUsuario = $oSession->GetVar('rol') ?: 'operador';
?>

<div class="container-fluid p-0">
  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm py-3 px-4">
    <a class="navbar-brand fw-bold" href="#">
      <i class="fas fa-boxes me-2"></i> Control de Inventario
    </a>
    <div class="ms-auto d-flex align-items-center">
      <span class="text-white me-3">
        <i class="fas fa-user-circle me-1"></i> <span class="fw-bold"><?php echo htmlspecialchars($nombreUsuario); ?></span>
        <span class="badge badge-light ms-1"><?php echo strtoupper($rolUsuario); ?></span>
      </span>
      <button class="btn btn-outline-light btn-sm fw-bold" onclick="logout()">
        Cerrar Sesión <i class="fas fa-sign-out-alt ms-1"></i>
      </button>
    </div>
  </nav>

  <!-- Contenido Principal -->
  <div class="container my-5">
    <div class="row justify-content-center mb-4">
      <div class="col-md-8">
        <div class="card border-0 shadow-lg" style="border-radius: 16px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);">
          <div class="card-body p-5 text-center">
            <div class="mb-4">
              <i class="fas fa-check-circle text-success" style="font-size: 80px;"></i>
            </div>
            <h1 class="fw-bold text-dark mb-2">¡Bienvenido al Sistema!</h1>
            <p class="text-muted mb-4" style="font-size: 18px;">
              Has iniciado sesión correctamente como <strong><?php echo htmlspecialchars($nombreUsuario); ?></strong>.
            </p>
            <div class="p-4 bg-light rounded" style="border-radius: 12px; border-left: 5px solid #007bff;">
              <p class="mb-0 text-start text-secondary fst-italic">
                "Selecciona un módulo para comenzar a trabajar."
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Módulos -->
    <div class="row justify-content-center">
      <div class="col-md-8">
        <h5 class="fw-bold text-dark mb-3"><i class="fas fa-th-large me-2"></i>Módulos Disponibles</h5>
        <div class="row">
          <?php if ( in_array($rolUsuario, ['admin', 'operador', 'consultor']) ) : ?>
          <div class="col-md-6 mb-3">
            <a href="articulos.php" class="text-decoration-none">
              <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; transition: transform .2s; cursor: pointer;"
                   onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform=''">
                <div class="card-body text-center p-4">
                  <div class="rounded-circle bg-primary d-inline-flex justify-content-center align-items-center mb-3" style="width: 70px; height: 70px;">
                    <i class="fas fa-cube text-white" style="font-size: 32px;"></i>
                  </div>
                  <h5 class="fw-bold text-dark mb-1">Artículos</h5>
                  <p class="text-muted small mb-0">Catálogo de productos del inventario</p>
                </div>
              </div>
            </a>
          </div>
          <?php endif; ?>
          <?php if ( in_array($rolUsuario, ['admin', 'operador', 'consultor']) ) : ?>
          <div class="col-md-6 mb-3">
            <a href="vales.php" class="text-decoration-none">
              <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; transition: transform .2s; cursor: pointer;"
                   onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform=''">
                <div class="card-body text-center p-4">
                  <div class="rounded-circle bg-warning d-inline-flex justify-content-center align-items-center mb-3" style="width: 70px; height: 70px;">
                    <i class="fas fa-exchange-alt text-white" style="font-size: 32px;"></i>
                  </div>
                  <h5 class="fw-bold text-dark mb-1">Vales</h5>
                  <p class="text-muted small mb-0">Entradas y salidas de inventario</p>
                </div>
              </div>
            </a>
          </div>
          <?php endif; ?>
          <?php if ( in_array($rolUsuario, ['admin', 'operador', 'supervisor']) ) : ?>
          <div class="col-md-6 mb-3">
            <a href="trabajadores.php" class="text-decoration-none">
              <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; transition: transform .2s; cursor: pointer;"
                   onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform=''">
                <div class="card-body text-center p-4">
                  <div class="rounded-circle bg-dark d-inline-flex justify-content-center align-items-center mb-3" style="width: 70px; height: 70px;">
                    <i class="fas fa-hard-hat text-white" style="font-size: 32px;"></i>
                  </div>
                  <h5 class="fw-bold text-dark mb-1">Trabajadores</h5>
                  <p class="text-muted small mb-0">Catálogo de trabajadores y bodegueros</p>
                </div>
              </div>
            </a>
          </div>
          <?php endif; ?>
          <?php if ( in_array($rolUsuario, ['admin', 'operador', 'supervisor']) ) : ?>
          <div class="col-md-6 mb-3">
            <a href="consulta_vales.php" class="text-decoration-none">
              <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; transition: transform .2s; cursor: pointer;"
                   onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform=''">
                <div class="card-body text-center p-4">
                  <div class="rounded-circle bg-info d-inline-flex justify-content-center align-items-center mb-3" style="width: 70px; height: 70px;">
                    <i class="fas fa-search text-white" style="font-size: 32px;"></i>
                  </div>
                  <h5 class="fw-bold text-dark mb-1">Consulta Vales</h5>
                  <p class="text-muted small mb-0">Búsqueda y filtrado de vales</p>
                </div>
              </div>
            </a>
          </div>
          <?php endif; ?>
          <?php if ( in_array($rolUsuario, ['admin', 'supervisor']) ) : ?>
          <div class="col-md-6 mb-3">
            <a href="inventario_fisico.php" class="text-decoration-none">
              <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; transition: transform .2s; cursor: pointer;"
                   onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform=''">
                <div class="card-body text-center p-4">
                  <div class="rounded-circle bg-success d-inline-flex justify-content-center align-items-center mb-3" style="width: 70px; height: 70px;">
                    <i class="fas fa-clipboard-list text-white" style="font-size: 32px;"></i>
                  </div>
                  <h5 class="fw-bold text-dark mb-1">Inventario Físico</h5>
                  <p class="text-muted small mb-0">Toma de inventario y ajustes</p>
                </div>
              </div>
            </a>
          </div>
          <?php endif; ?>
          <?php if ( in_array($rolUsuario, ['admin', 'operador', 'supervisor', 'consultor']) ) : ?>
          <div class="col-md-6 mb-3">
            <a href="reportes.php" class="text-decoration-none">
              <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; transition: transform .2s; cursor: pointer;"
                   onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform=''">
                <div class="card-body text-center p-4">
                  <div class="rounded-circle bg-secondary d-inline-flex justify-content-center align-items-center mb-3" style="width: 70px; height: 70px;">
                    <i class="fas fa-chart-bar text-white" style="font-size: 32px;"></i>
                  </div>
                  <h5 class="fw-bold text-dark mb-1">Reportes</h5>
                  <p class="text-muted small mb-0">Reportes de inventario y movimientos</p>
                </div>
              </div>
            </a>
          </div>
          <?php endif; ?>
          <?php if ( $rolUsuario === 'admin' ) : ?>
          <div class="col-md-6 mb-3">
            <a href="usuarios.php" class="text-decoration-none">
              <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; transition: transform .2s; cursor: pointer;"
                   onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform=''">
                <div class="card-body text-center p-4">
                  <div class="rounded-circle bg-danger d-inline-flex justify-content-center align-items-center mb-3" style="width: 70px; height: 70px;">
                    <i class="fas fa-users-cog text-white" style="font-size: 32px;"></i>
                  </div>
                  <h5 class="fw-bold text-dark mb-1">Usuarios</h5>
                  <p class="text-muted small mb-0">Administración de usuarios y roles</p>
                </div>
              </div>
            </a>
          </div>
          <?php endif; ?>
          <?php if ( $rolUsuario === 'admin' ) : ?>
          <div class="col-md-6 mb-3">
            <a href="cierre_mes.php" class="text-decoration-none">
              <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; transition: transform .2s; cursor: pointer;"
                   onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform=''">
                <div class="card-body text-center p-4">
                  <div class="rounded-circle bg-warning d-inline-flex justify-content-center align-items-center mb-3" style="width: 70px; height: 70px;">
                    <i class="fas fa-sync-alt text-white" style="font-size: 32px;"></i>
                  </div>
                  <h5 class="fw-bold text-dark mb-1">Cierre de Mes</h5>
                  <p class="text-muted small mb-0">Reiniciar acumuladores para nuevo mes</p>
                </div>
              </div>
            </a>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
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
</script>

<?php 
$oWeb->End(); 
?>
