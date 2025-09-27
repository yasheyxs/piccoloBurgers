<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
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

include_once(dirname(__DIR__, 2) . "/config/config.php");
require_once dirname(__DIR__) . '/helpers/url.php';


$url_base = piccolo_admin_base_url();

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$adminBasePath = preg_replace('#/admin(?:/.*)?$#', '/admin/', $requestPath);

if (empty($adminBasePath) || $adminBasePath === $requestPath) {
  $adminBasePath = '/admin/';
}

$url_base = rtrim(sprintf('%s://%s%s', $scheme, $host, $adminBasePath), '/') . '/';

if (MODO_DESARROLLO) {
  $_SESSION["admin_usuario"] = USUARIO_DESARROLLO;
  $_SESSION["admin_logueado"] = true;
  $_SESSION["rol"] = "admin";
} else {
  if (!isset($_SESSION["admin_logueado"])) {
    header("Location: login.php");
    exit();
  }
}

$rol = $_SESSION["rol"] ?? "";
?>

<!doctype html>
<html lang="es">
<head>
  <title>Administrador del sitio web</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="<?php echo $url_base; ?>../public/img/favicon.png" />
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="<?php echo $url_base; ?>assets/css/datatables-custom.css">

  <style>
    .dropdown-menu {
      z-index: 1050;
    }
  </style>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
  <script src="<?php echo $url_base; ?>assets/js/datatables-init.js"></script>
</head>

<body>
  <header>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
      <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="<?php echo $url_base; ?>index.php">Administrador</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin" aria-controls="navbarAdmin" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarAdmin">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">

            <?php if ($rol === "admin") { ?>
              <li class="nav-item"><a class="nav-link" href="<?php echo $url_base; ?>seccion/banners/">Banners</a></li>
              <li class="nav-item"><a class="nav-link" href="<?php echo $url_base; ?>seccion/testimonios/">Testimonios</a></li>
              <li class="nav-item"><a class="nav-link" href="<?php echo $url_base; ?>seccion/menu/">Menú</a></li>
              <li class="nav-item"><a class="nav-link" href="<?php echo $url_base; ?>seccion/materiasPrimas/">Materias Primas</a></li>
              <li class="nav-item"><a class="nav-link" href="<?php echo $url_base; ?>seccion/comentarios/">Comentarios</a></li>
              <li class="nav-item"><a class="nav-link" href="<?php echo $url_base; ?>seccion/proveedores/">Proveedores</a></li>

              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="personasDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  Gestión de personas
                </a>
                <ul class="dropdown-menu" aria-labelledby="personasDropdown">
                  <li><a class="dropdown-item" href="<?php echo $url_base; ?>clientes.php">Clientes</a></li>
                  <li><a class="dropdown-item" href="<?php echo $url_base; ?>seccion/usuarios/">Usuarios</a></li>
                </ul>
              </li>

              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="transaccionesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  Transacciones
                </a>
                <ul class="dropdown-menu" aria-labelledby="transaccionesDropdown">
                  <li><a class="dropdown-item" href="<?php echo $url_base; ?>seccion/ventas/">Ventas</a></li>
                  <li><a class="dropdown-item" href="<?php echo $url_base; ?>seccion/compras/">Compras</a></li>
                </ul>
              </li>
            <?php } ?>

            <?php if (in_array($rol, ["admin", "empleado", "delivery"])) { ?>
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="panelDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  Paneles
                </a>
                <ul class="dropdown-menu" aria-labelledby="panelDropdown">
                  <?php if ($rol === "admin" || $rol === "empleado") { ?>
                    <li><a class="dropdown-item" href="<?php echo $url_base; ?>panel_cocina.php">Panel de cocina</a></li>
                  <?php } ?>
                  <?php if ($rol === "admin" || $rol === "delivery") { ?>
                    <li><a class="dropdown-item" href="<?php echo $url_base; ?>panel_delivery.php">Panel de delivery</a></li>
                  <?php } ?>
                </ul>
              </li>
            <?php } ?>
          </ul>

          <ul class="navbar-nav ms-auto">
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
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="modalCerrarSesionLabel">¿Cerrar sesión?</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
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

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const dropdownElements = document.querySelectorAll('[data-bs-toggle="dropdown"]');
    dropdownElements.forEach(function (dropdownToggleEl) {
      new bootstrap.Dropdown(dropdownToggleEl);
    });
  });
</script>

</body>
</html>
