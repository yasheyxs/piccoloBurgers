<?php
if (session_status() === PHP_SESSION_NONE) {
  piccolo_admin_iniciar_sesion();
}

if (empty($_SESSION['csrf_token'])) {
  try {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  } catch (Exception $e) {
    // Si la generación del token falla, abortamos la carga de la vista para evitar estados inseguros.
    http_response_code(500);
    echo 'No fue posible iniciar la sesión de manera segura.';
    exit;
  }
}

require_once __DIR__ . '/../../src/Core/bootstrap.php';
include_once piccolo_path('config/config.php');


$url_base = piccolo_admin_base_url();


if (MODO_DESARROLLO) {
  $_SESSION["admin_usuario"] = USUARIO_DESARROLLO;
  $_SESSION["admin_logueado"] = true;
  $_SESSION["rol"] = "admin";
} else {
  if (!isset($_SESSION["admin_logueado"])) {
    header('Location: ' . $url_base . 'login.php');
    exit();
  }
}

$rol = $_SESSION["rol"] ?? "";

$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
$adminPageIdentifier = $adminPageIdentifier ?? $currentScript;

$bodyClasses = ['admin-body'];
if (in_array($adminPageIdentifier, ['panel_delivery.php', 'delivery-panel'], true)) {
  $bodyClasses[] = 'delivery-panel';
}

$bodyClassAttribute = implode(' ', array_unique(array_filter($bodyClasses)));

?>

<!doctype html>
<html lang="es">

<head>
  <title>Administrador del sitio web</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
  <script src="<?php echo $url_base; ?>assets/js/csrf.js"></script>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="<?php echo htmlspecialchars(public_url('img/favicon.png'), ENT_QUOTES, 'UTF-8'); ?>" />
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="<?php echo $url_base; ?>assets/css/datatables-custom.css">
  <link rel="stylesheet" href="<?php echo $url_base; ?>assets/css/admin-theme.css">

  <style>
    .dropdown-menu {
      z-index: 1050;
    }
  </style>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
  <script src="<?php echo $url_base; ?>assets/js/datatables-init.js"></script>
  <script src="<?php echo $url_base; ?>assets/js/feedback.js"></script>
  <script src="<?php echo $url_base; ?>assets/js/confirmar.js"></script>
</head>

<body class="<?php echo htmlspecialchars($bodyClassAttribute, ENT_QUOTES, 'UTF-8'); ?>">
  <header>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
      <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="<?php echo $url_base; ?>index.php">Administrador</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin" aria-controls="navbarAdmin" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarAdmin">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">

            <?php
            // Menú del panel: cada opción se muestra solo si el rol puede entrar (config/permisos.php)
            $menuPanel = [
                ['Landing', [
                    ['Banner', 'seccion/banners/index.php'],
                    ['Testimonios', 'seccion/testimonios/index.php'],
                    ['Comentarios', 'seccion/comentarios/index.php'],
                ]],
                ['Menú', 'seccion/menu/index.php'],
                ['Materias Primas', 'seccion/materiasPrimas/index.php'],
                ['Proveedores', 'seccion/proveedores/index.php'],
                ['Puntos', 'seccion/puntos/index.php'],
                ['Premios', 'seccion/premios/index.php'],
                ['Gestión de personas', [
                    ['Clientes', 'clientes.php'],
                    ['Usuarios', 'seccion/usuarios/index.php'],
                ]],
                ['Transacciones', [
                    ['Ventas', 'seccion/ventas/index.php'],
                    ['Compras', 'seccion/compras/index.php'],
                ]],
                ['Paneles', [
                    ['Panel de cocina', 'panel_cocina.php'],
                    ['Panel de delivery', 'panel_delivery.php'],
                ]],
                ['Pools', [
                    ['Fichas', 'seccion/pool/index.php'],
                    ['Estadísticas de Pool', 'seccion/estadisticasPool/index.php'],
                    ['Parámetros', 'seccion/poolParametros/index.php'],
                ]],
                ['Bebidas', [
                    ['Gestión', 'seccion/bebidas/index.php'],
                    ['Estadísticas', 'seccion/estadisticasBebidas/index.php'],
                    ['Parámetros', 'seccion/bebidasParametros/index.php'],
                ]],
            ];

            $enlaceMenu = static fn(string $ruta): string => $url_base . preg_replace('#index\.php$#', '', $ruta);

            foreach ($menuPanel as $indice => [$titulo, $destino]):
                if (is_string($destino)):
                    if (!piccolo_admin_puede($destino)) {
                        continue;
                    } ?>
              <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars($enlaceMenu($destino), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $titulo; ?></a></li>
                <?php else:
                    $opciones = array_filter($destino, static fn($opcion) => piccolo_admin_puede($opcion[1]));
                    if (!$opciones) {
                        continue;
                    } ?>
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="menuGrupo<?php echo $indice; ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <?php echo $titulo; ?>
                </a>
                <ul class="dropdown-menu" aria-labelledby="menuGrupo<?php echo $indice; ?>">
                  <?php foreach ($opciones as [$texto, $ruta]): ?>
                    <li><a class="dropdown-item" href="<?php echo htmlspecialchars($enlaceMenu($ruta), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $texto; ?></a></li>
                  <?php endforeach; ?>
                </ul>
              </li>
            <?php endif;
            endforeach; ?>
          </ul>

          <ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item theme-toggle-wrapper">
              <button id="darkModeToggle" class="btn btn-sm btn-outline-secondary theme-toggle" type="button" title="Activar modo oscuro" aria-label="Activar modo oscuro" aria-pressed="false">
                <i class="fa-solid fa-moon"></i>
              </button>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="<?php echo $url_base; ?>password/cambiar_password.php" title="Cambiar mi contraseña" aria-label="Cambiar mi contraseña">
                <i class="fa-solid fa-key" aria-hidden="true"></i>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-danger" href="#" data-bs-toggle="modal" data-bs-target="#modalCerrarSesion" title="Cerrar sesión">
                <i class="fa-solid fa-right-from-bracket fa-lg"></i>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <main>
    <section class="container">

      <!-- Modal de confirmación de cierre de sesión -->
      <div class="modal fade" id="modalCerrarSesion" tabindex="-1" aria-labelledby="modalCerrarSesionLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content logout-modal">
            <div class="modal-header">
              <div class="d-flex align-items-center gap-3">
                <span class="logout-modal__icon" aria-hidden="true">
                  <i class="fa-solid fa-right-from-bracket"></i>
                </span>
                <h5 class="modal-title mb-0" id="modalCerrarSesionLabel">¿Cerrar sesión?</h5>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
              ¿Estás seguro de que querés cerrar sesión? Esta acción te desconectará del panel de administración.
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Quedarse</button>
              <a href="<?php echo $url_base; ?>cerrar.php" class="btn btn-danger">Salir</a>
            </div>
          </div>
        </div>
      </div>
