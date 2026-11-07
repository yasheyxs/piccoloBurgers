<?php
require_once piccolo_path('src/Core/database.php');
require_once piccolo_path('src/Services/reservas_virtuales.php');
require_once piccolo_path('src/Services/menu_catalogo.php');

iniciarSesionSiEsNecesario();

$resultado = buscarMenu(
  $conexion,
  (string) ($_GET['categoria'] ?? ''),
  (string) ($_GET['busqueda'] ?? ''),
  isset($_GET['offset']) ? (int) $_GET['offset'] : 0,
  isset($_GET['limit']) ? (int) $_GET['limit'] : 8
);

$lista_menu = $resultado['items'];
$totalItems = $resultado['total'];

$idsMenus = array_map(static fn($item) => $item['ID'] ?? null, $lista_menu);
$idsMenus = array_values(array_filter($idsMenus, static fn($id) => $id !== null));
$disponibilidades = obtenerDisponibilidadMenu($conexion, $idsMenus);

ob_start();
foreach ($lista_menu as $registro): ?>
  <div class="col d-flex" data-aos="fade-up">
    <div class="card position-relative d-flex flex-column h-100 w-100">
      <img src="img/menu/<?= htmlspecialchars($registro["foto"]) ?>" class="card-img-top" alt="Foto de <?= htmlspecialchars($registro["nombre"]) ?>">
      <div class="card-body d-flex flex-column">
        <h5 class="card-title"><?= htmlspecialchars($registro["nombre"]) ?></h5>
        <p class="card-text small"><strong><?= htmlspecialchars($registro["ingredientes"]) ?></strong></p>
        <p class="card-text"><strong>Precio:</strong> $<?= htmlspecialchars($registro["precio"]) ?></p>
        <p class="card-text"><small><em><?= htmlspecialchars($registro["categoria"] ?? '') ?></em></small></p>
        <?php
        $infoDisponibilidad = $disponibilidades[(string) $registro['ID']] ?? ['unidades_disponibles' => 0];
        $hayStock = ($infoDisponibilidad['unidades_disponibles'] ?? 0) > 0;
        ?> <button class="btn btn-agregar mt-auto<?= $hayStock ? '' : ' btn-sin-stock' ?>" <?= $hayStock ? '' : ' disabled' ?>
          data-id="<?= htmlspecialchars($registro['ID']) ?>"
          data-nombre="<?= htmlspecialchars($registro['nombre']) ?>"
          data-precio="<?= htmlspecialchars($registro['precio']) ?>"
          data-img="img/menu/<?= htmlspecialchars($registro['foto']) ?>"
          data-disponibles="<?= (int) ($infoDisponibilidad['unidades_disponibles'] ?? 0) ?>">
          <?= $hayStock ? 'Agregar' : 'Sin stock' ?>
        </button>
      </div>
    </div>
  </div>
<?php endforeach;
$htmlContent = ob_get_clean();

header('Content-Type: application/json');
echo json_encode([
  'html' => $htmlContent,
  'totalItems' => (int) $totalItems,
]);
exit;
