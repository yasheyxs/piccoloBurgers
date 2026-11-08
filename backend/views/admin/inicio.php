<?php
/**
 * Portada del panel: bienvenida, acciones rápidas, "vista rápida" por rol y resumen del mes.
 *
 * @var string $usuario
 * @var string $rol      rol con el que se está usando el panel (puede ser la vista elegida)
 * @var string $rolReal  rol del usuario
 * @var array<string, mixed>|null $resumen  VentasController::resumen() del mes, solo para administración
 * @var string|null $errorResumen
 * @var string $url_base
 */
use Piccolo\Http\Admin\UsuariosController;

$etiquetas = UsuariosController::ROLES;
$esAdmin = $rol === 'admin';
$viendoComoOtro = $rolReal === 'admin' && !$esAdmin;
$pesos = static fn(float $monto): string => '$' . number_format($monto, 2, ',', '.');
?>
<link rel="stylesheet" href="<?= piccolo_e($url_base) ?>assets/css/inicio.css">

<div id="inicioPanel" data-base="<?= piccolo_e($url_base) ?>">
  <br>
  <div class="row align-items-md-stretch">
    <div class="col-md-12">
      <div class="welcome-box">
        <h2>Hola, <?= piccolo_e($usuario) ?></h2>
        <p>Entraste como <strong><?= piccolo_e($etiquetas[$rolReal] ?? $rolReal) ?></strong>. Usá el menú de arriba para ir a cada sección.</p>
        <?php if ($esAdmin): ?>
          <p class="text-muted mb-0">Desde acá podés editar el menú, revisar comentarios, administrar usuarios, controlar ventas y compras, y supervisar cocina y delivery.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($viendoComoOtro): ?>
    <div class="alert alert-info text-center mt-4" role="status">
      Estás viendo el panel como <strong><?= piccolo_e($etiquetas[$rol] ?? $rol) ?></strong>. Para volver a tu vista, elegí "Vista administrador" acá abajo.
    </div>
  <?php endif; ?>

  <?php if ($esAdmin): ?>
    <div class="container my-5">
      <h3 class="mb-4 text-center"><i class="fa-solid fa-bolt me-2" aria-hidden="true"></i>Acciones rápidas</h3>
      <div class="row g-4 justify-content-center">
        <?php foreach ([
            ['btn-add', 'fa-plus', 'Agregar producto'],
            ['btn-user', 'fa-user-plus', 'Agregar usuario'],
            ['btn-report', 'fa-file-pdf', 'Reporte de ventas en PDF'],
            ['btn-roles', 'fa-key', 'Ver como otro rol'],
            ['btn-pool', 'fa-circle-dot', 'Fichas'],
            ['btn-drinks', 'fa-glass-water', 'Bebidas'],
        ] as [$clase, $icono, $texto]): ?>
          <div class="col-md-3 col-lg-2">
            <button class="quick-action <?= $clase ?> w-100" type="button"><i class="fa-solid <?= $icono ?>" aria-hidden="true"></i><?= $texto ?></button>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($rolReal === 'admin'): ?>
    <div class="container">
      <div class="row justify-content-center mt-3">
        <div class="col-md-6 col-lg-4">
          <div id="rolesPanel" class="card shadow-sm<?= $viendoComoOtro ? '' : ' d-none' ?>" aria-hidden="<?= $viendoComoOtro ? 'false' : 'true' ?>">
            <div class="card-body">
              <h5 class="card-title text-center mb-3">Ver el panel como…</h5>
              <form id="rolForm" class="d-grid gap-2">
                <?php foreach ($etiquetas as $valor => $nombre): ?>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="rol" id="rol_<?= $valor ?>" value="<?= $valor ?>" <?= $rol === $valor ? 'checked' : '' ?>>
                    <label class="form-check-label" for="rol_<?= $valor ?>">Vista <?= strtolower($nombre) ?></label>
                  </div>
                <?php endforeach; ?>
              </form>
              <div id="rolMensaje" class="alert mt-3 d-none" role="alert"></div>
              <p class="text-muted small text-center mb-0">Sirve para revisar qué ve cada rol. Tus permisos reales no cambian.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($esAdmin): ?>
    <div class="container metrics-section my-5">
      <h3 class="mb-4 text-center"><i class="fa-solid fa-chart-column me-2" aria-hidden="true"></i>Ventas del mes</h3>
      <?php if ($errorResumen): ?>
        <div class="alert alert-warning text-center" role="alert"><?= piccolo_e($errorResumen) ?></div>
      <?php elseif ($resumen): ?>
        <div class="row g-4 justify-content-center">
          <div class="col-md-4 col-sm-6">
            <div class="card h-100 text-center p-4"><div class="card-body">
              <div class="metric-icon text-success"><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i></div>
              <h5 class="card-title">Total vendido</h5>
              <p class="text-muted small mb-2">Pedidos + fichas + bebidas, sin cancelados</p>
              <p class="display-6 fw-bold text-success mb-0"><?= $pesos($resumen['total']) ?></p>
            </div></div>
          </div>
          <div class="col-md-4 col-sm-6">
            <div class="card h-100 text-center p-4"><div class="card-body">
              <div class="metric-icon text-info"><i class="fa-solid fa-receipt" aria-hidden="true"></i></div>
              <h5 class="card-title">Pedidos</h5>
              <p class="display-6 fw-bold text-info mb-0"><?= number_format($resumen['cantidad_pedidos'], 0, ',', '.') ?></p>
            </div></div>
          </div>
          <div class="col-md-4 col-sm-8">
            <div class="card h-100 text-center p-4"><div class="card-body">
              <div class="metric-icon text-warning"><i class="fa-solid fa-star" aria-hidden="true"></i></div>
              <h5 class="card-title">Producto estrella</h5>
              <?php if ($resumen['producto_estrella']): ?>
                <p class="h4 fw-bold mb-1"><?= piccolo_e($resumen['producto_estrella']['nombre']) ?></p>
                <p class="text-muted mb-0"><?= (int) $resumen['producto_estrella']['cantidad'] ?> vendidos</p>
              <?php else: ?>
                <p class="text-muted mb-0">Todavía no hay ventas este mes</p>
              <?php endif; ?>
            </div></div>
          </div>
        </div>
      <?php endif; ?>
      <div class="text-center my-5">
        <a href="<?= piccolo_e($url_base) ?>seccion/ventas/" class="btn sales-panel-cta"><i class="fa-solid fa-chart-line" aria-hidden="true"></i><span>Ir al reporte de ventas</span></a>
      </div>
    </div>
  <?php endif; ?>
</div>

<script src="<?= piccolo_e($url_base) ?>assets/js/pages/inicio.js"></script>
