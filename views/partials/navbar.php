<?php
$navBasePath = isset($navBasePath) ? (string) $navBasePath : '';
$navHomeLink = isset($navHomeLink) ? (string) $navHomeLink : ($navBasePath !== '' ? $navBasePath : '#');
$navBrandLabel = isset($navBrandLabel) ? (string) $navBrandLabel : 'Piccolo Burgers';
$navCarritoLink = isset($navCarritoLink) ? (string) $navCarritoLink : 'carrito.php';

$navSectionBase = $navBasePath !== '' ? rtrim($navBasePath, '#') : '';
$sectionLinkPrefix = $navSectionBase !== '' ? $navSectionBase . '#' : '#';

$navSections = [
    'inicio' => 'Inicio',
    'menu' => 'Menú',
    'nosotros' => 'Nosotros',
    'testimonios' => 'Testimonio',
];

$navExtraSections = [
    'puntos' => 'Puntos',
    'ubicacion' => 'Ubicación',
    'contacto' => 'Contacto',
];
?>
<nav class="navbar navbar-expand-lg navbar-dark sticky-navbar bg-dark">
  <div class="container">
    <a class="navbar-brand" href="<?= htmlspecialchars($navHomeLink, ENT_QUOTES, 'UTF-8'); ?>">
      <i class="fas fa-utensils"></i> <?= htmlspecialchars($navBrandLabel, ENT_QUOTES, 'UTF-8'); ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php foreach ($navSections as $section => $label): ?>
          <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($sectionLinkPrefix . $section, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></a></li>
        <?php endforeach; ?>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="extrasDropdown" role="button" data-bs-toggle="dropdown">
            Más
          </a>
          <ul class="dropdown-menu dropdown-menu-dark">
            <?php foreach ($navExtraSections as $section => $label): ?>
              <li><a class="dropdown-item" href="<?= htmlspecialchars($sectionLinkPrefix . $section, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link position-relative" href="<?= htmlspecialchars($navCarritoLink, ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-shopping-cart"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-counter" id="contador-carrito">
              0
            </span>
          </a>
        </li>

        <?php if (isset($_SESSION['cliente'])): ?>
          <li class="nav-item">
            <a href="cliente/perfil_cliente.php" class="nav-link" title="<?= htmlspecialchars($_SESSION['cliente']['nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
              <i class="fas fa-user-circle"></i>
            </a>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link" title="Cerrar sesión" data-bs-toggle="modal" data-bs-target="#logoutModal">
              <i class="fas fa-sign-out-alt"></i>
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a href="cliente/login_cliente.php" class="btn btn-gold rounded-pill px-4 py-2 ms-2">
              Iniciar sesión / Registrarse
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-white border-0 shadow-lg">
      <div class="modal-header border-bottom-0">
        <h5 class="modal-title fw-bold" id="logoutModalLabel">
          <i class="fas fa-sign-out-alt me-2 text-warning"></i> ¿Cerrar sesión?
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body text-center">
        <p class="fs-5 mb-0">¿Estás seguro de que querés cerrar sesión?</p>
      </div>
      <div class="modal-footer justify-content-center border-top-0">
        <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">
          <i></i> Quedarme
        </button>
        <a href="cliente/logout_cliente.php" class="btn btn-danger px-4">
          <i class="fas fa-door-open me-1"></i> Cerrar Sesión
        </a>
      </div>
    </div>
  </div>
</div>
