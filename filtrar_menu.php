<?php
include("admin/bd.php");

$categorias_disponibles = ["Acompañamientos", "Hamburguesas", "Bebidas", "Lomitos y Sándwiches", "Pizzas"];
$categoria = $_GET['categoria'] ?? '';
$lista_menu = [];

if ($categoria && in_array($categoria, $categorias_disponibles)) {// Si la categoría es válida, filtrar el menú
  $stmt = $conexion->prepare("SELECT * FROM tbl_menu WHERE categoria = ? ORDER BY id DESC");
  $stmt->execute([$categoria]);
  $lista_menu = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {// Si categoría es inválida, mostrar los últimos 4 elementos del menú
  $stmt = $conexion->prepare("SELECT * FROM tbl_menu ORDER BY id DESC limit 4");
  $stmt->execute();
  $lista_menu = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

foreach ($lista_menu as $registro): ?>// Mostrar cada registro del menú
  <div class="col d-flex">
    <div class="card position-relative d-flex flex-column h-100 w-100">
      <img src="img/menu/<?= $registro["foto"] ?>" class="card-img-top" alt="Foto de <?= $registro["nombre"] ?>">
      <div class="card-body d-flex flex-column">
        <h5 class="card-title"><?= $registro["nombre"] ?></h5>
        <p class="card-text small"><strong><?= $registro["ingredientes"] ?></strong></p>
        <p class="card-text"><strong>Precio:</strong> $<?= $registro["precio"] ?></p>
        <p class="card-text"><small><em><?= $registro["categoria"] ?? '' ?></em></small></p>
        <button class="btn btn-agregar mt-auto"
          data-id="<?= $registro['ID'] ?>"
          data-nombre="<?= $registro['nombre'] ?>"
          data-precio="<?= $registro['precio'] ?>"
          data-img="img/menu/<?= $registro['foto'] ?>">
          Agregar
        </button>
      </div>
    </div>
  </div>
<?php endforeach; ?>