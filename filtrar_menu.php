<?php
include("admin/bd.php");

$categorias_disponibles = ["Acompañamientos", "Hamburguesas", "Bebidas", "Lomitos y Sándwiches", "Pizzas"];

$categoria = $_GET['categoria'] ?? '';
$busqueda = trim($_GET['busqueda'] ?? '');
$lista_menu = [];

$parametros = [];
$sql = "SELECT * FROM tbl_menu WHERE 1=1";

// Filtrar por categoría si es válida
if ($categoria && in_array($categoria, $categorias_disponibles)) {
  $sql .= " AND categoria = ?";
  $parametros[] = $categoria;
}

// Filtrar por búsqueda en NOMBRE (ya no en ingredientes)
if ($busqueda !== '') {
  $sql .= " AND nombre LIKE ?";
  $parametros[] = "%$busqueda%";
}

$sql .= " ORDER BY id DESC";

$stmt = $conexion->prepare($sql);
$stmt->execute($parametros);
$lista_menu = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mostrar resultados
foreach ($lista_menu as $registro): ?>
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

<?php if (count($lista_menu) === 0): ?>
  <div class="d-flex justify-content-center align-items-center mt-5 w-100">
    <div style="
      background-color: #3a2a00;
      color: #fac30c;
      border: 1px solid #fac30c;
      font-size: 1.3rem;
      font-weight: bold;
      border-radius: 12px;
      padding: 1.5rem 2rem;
      max-width: 700px;
      width: 100%;
      text-align: center;
      display: inline-block;
    ">
      No se encontraron productos que coincidan con tu búsqueda.
    </div>
  </div>
<?php endif; ?>

