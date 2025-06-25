<?php session_start(); ?>

<?php 
include("admin/bd.php");

$categorias_disponibles = ["Acompañamientos", "Hamburguesas", "Bebidas", "Lomitos y Sándwiches", "Pizzas"];

$categoria_seleccionada = $_GET['categoria'] ?? '';
$lista_menu = [];

$sentencia = $conexion->prepare("SELECT * FROM tbl_banners ORDER BY id DESC limit 1 ");
$sentencia->execute();
$lista_banners = $sentencia->fetchAll(PDO::FETCH_ASSOC);

$sentencia = $conexion->prepare("SELECT * FROM tbl_testimonios ORDER BY id DESC limit 2");
$sentencia->execute();
$lista_testimonios = $sentencia->fetchAll(PDO::FETCH_ASSOC);

if ($categoria_seleccionada && in_array($categoria_seleccionada, $categorias_disponibles)) {
    $sentencia = $conexion->prepare("SELECT * FROM tbl_menu WHERE categoria = ? ORDER BY id DESC");
    $sentencia->execute([$categoria_seleccionada]);
    $lista_menu = $sentencia->fetchAll(PDO::FETCH_ASSOC);
} else {
    $sentencia = $conexion->prepare("SELECT * FROM tbl_menu ORDER BY id DESC limit 4");
    $sentencia->execute();
    $lista_menu = $sentencia->fetchAll(PDO::FETCH_ASSOC);
}

if ($_POST) {
    $nombre = filter_var($_POST["nombre"], FILTER_SANITIZE_STRING);
    $correo = filter_var($_POST["correo"], FILTER_VALIDATE_EMAIL);
    $mensaje = filter_var($_POST["mensaje"], FILTER_SANITIZE_STRING);

    if ($nombre && $correo && $mensaje) {
        $sql = "INSERT INTO tbl_comentarios (nombre, correo, mensaje)
                VALUES (:nombre, :correo, :mensaje)";

        $resultado = $conexion->prepare($sql);
        $resultado->bindParam(':nombre', $nombre, PDO::PARAM_STR);
        $resultado->bindParam(':correo', $correo, PDO::PARAM_STR);
        $resultado->bindParam(':mensaje', $mensaje, PDO::PARAM_STR);
        $resultado->execute();
    }
    header("Location:index.php");
}
?>

<!doctype html>
<html lang="en">
<head>
  <title>Piccolo Burgers</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

  <style>
    body {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 1.7rem;
    }
    .btn-gold {
      background-color: #fac30c;
      color: #000;
      font-weight: bold;
      border: none;
    }
    .btn-gold:hover {
      background-color: #e0ae00;
      color: #000;
    }
    footer p {
      margin: 0;
      font-size: 1rem;
    }

    .card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      overflow: hidden;
      border-radius: 12px;
    }

    .card:hover {
      transform: scale(1.03);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    }

    .card-img-top {
      height: 200px;
      object-fit: cover;
      transition: transform 0.3s ease;
    }

    .card:hover .card-img-top {
      transform: scale(1.05);
    }

    .card .btn-agregar {
      background-color: #fac30c;
      color: #000;
      font-weight: bold;
      border: none;
      padding: 8px 20px;
      border-radius: 20px;
      margin-top: 10px;
      transition: background-color 0.3s ease, transform 0.2s ease;
      width: 100%;
    }

    .card .btn-agregar:hover {
      background-color: #e0ae00;
      transform: scale(1.05);
    }


  </style>
</head>
<body id="top">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="#"><i class="fas fa-utensils"></i> Piccolo Burgers</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="#inicio">Inicio</a></li>
        <li class="nav-item"><a class="nav-link" href="#menu">Menú</a></li>
        <li class="nav-item"><a class="nav-link" href="#testimonios">Testimonio</a></li>
        <li class="nav-item"><a class="nav-link" href="#contacto">Contacto</a></li>
        <li class="nav-item"><a class="nav-link" href="#horario">Horarios</a></li>
        <li class="nav-item">
          <a class="nav-link position-relative" href="carrito.php">
            <i class="fas fa-shopping-cart"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="contador-carrito" style="font-size: 0.7rem;">
              0
            </span>
          </a>
        </li>
      </ul>
      <?php if (isset($_SESSION["cliente"])): ?>
        <a href="perfil_cliente.php" class="btn btn-outline-light ms-3">
          👤 <?= htmlspecialchars($_SESSION["cliente"]["nombre"]) ?>
        </a>
        <a href="logout_cliente.php" class="btn btn-gold ms-2">Cerrar sesión</a>
        <?php else: ?>
          <a href="login_cliente.php" class="btn btn-outline-light ms-3">Iniciar sesión</a>
      <?php endif; ?>

    </div>
  </div>
</nav>

<section class="container-fluid p-0">
  <div class="banner-img" style="position:relative; background:url('img/pexels-atomlaborblog-776314.webp') center/cover no-repeat; height:400px;">
    <div class="banner-text" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); text-align:center; color:#fff; text-shadow: 5px 5px 9px rgba(0,0,0,0.7);">
      <?php foreach($lista_banners as $banner){ ?>
        <h1 style="font-size: 5rem;"><?php echo $banner['titulo'];?></h1>
        <p><?php echo $banner['descripcion'];?></p>
        <a href="<?php echo $banner['link'];?>" class="btn btn-gold">Ver Menú</a>
      <?php } ?>
    </div>
  </div>
</section>

<section class="container mt-4 text-center">
  <div class="jumbotron bg-dark text-white p-4">
    <h2>¡Bienvenidx a Piccolo Burgers!</h2>
    <p>Descubre las verdaderas hamburguesas. Siempre 100% cargadas de sabor.</p>
  </div>
</section>

<section id="testimonios" class="bg-light py-5">
  <div class="container">
    <h2 class="text-center mb-4">Testimonios</h2>
    <div class="row">
      <?php foreach ($lista_testimonios as $testimonio){ ?>
        <div class="col-md-6 d-flex">
          <div class="card mb-4 w-100">
            <div class="card-body">
              <p class="card-text"><?php echo $testimonio["opinion"];?></p>
            </div>
            <div class="card-footer text-muted">
              <?php echo $testimonio["nombre"]; ?>
            </div>
          </div>
        </div>
      <?php } ?>
    </div>
  </div>
</section>

<section id="menu" class="container mt-4">
  <h2 class="text-center">Menú</h2>

  <div class="mb-3 text-center">
    <form class="d-inline">
      <label for="categoria" class="form-label me-2">Filtrar por categoría:</label>
      <select id="categoria" class="form-select d-inline w-auto">

        <option value="">Todos</option>
        <?php foreach ($categorias_disponibles as $cat): ?>
          <option value="<?= $cat ?>" <?= ($cat == $categoria_seleccionada) ? 'selected' : '' ?>><?= $cat ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>

  <div id="contenedor-menu" class="row row-cols-1 row-cols-md-4 g-4">
    <?php foreach($lista_menu as $registro) { ?>
      <div class="col d-flex">
        <div class="card position-relative">
          <img src="img/menu/<?php echo $registro["foto"];?>" class="card-img-top" alt="Foto de <?php echo $registro["nombre"]; ?>">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title"><?php echo $registro["nombre"];?></h5>
            <p class="card-text small"><strong><?php echo $registro["ingredientes"];?></strong></p>
            <p class="card-text"><strong>Precio:</strong> $<?php echo $registro["precio"];?></p>
            <p class="card-text"><small><em><?php echo $registro["categoria"] ?? ''; ?></em></small></p>
            <button class="btn btn-agregar mt-auto" 
                    data-id="<?php echo $registro['ID']; ?>" 
                    data-nombre="<?php echo $registro['nombre']; ?>" 
                    data-precio="<?php echo $registro['precio']; ?>" 
                    data-img="img/menu/<?php echo $registro['foto']; ?>">
              Agregar
            </button>
          </div>
        </div>
      </div>
    <?php } ?>
  </div>
</section>

<section id="contacto" class="container mt-4">
  <h2>Contacto</h2>
  <p>Estamos acá para servirte.</p>
  <form action="?" method="post">
    <div class="mb-3">
      <label for="name">Nombre:</label>
      <input type="text" class="form-control" name="nombre" placeholder="Escribe tu nombre..." required>
    </div>
    <div class="mb-3">
      <label for="email">Correo electrónico:</label>
      <input type="email" class="form-control" name="correo" placeholder="Escribe tu correo electrónico..." required>
    </div>
    <div class="mb-3">
      <label for="message">Mensaje:</label>
      <textarea id="message" class="form-control" name="mensaje" rows="6"></textarea>
    </div>
    <input type="submit" class="btn btn-gold" value="Enviar mensaje">
  </form>
</section>

<div id="horario" class="text-center bg-light p-4">
  <h3 class="mb-4">Horario de atención</h3>
  <div>
    <p><strong>Martes a Domingo y feriados</strong></p>
    <p><strong>20:00 hs - 00:30 hs</strong></p>
  </div>
  <div>
    <p><em>Lunes cerrado</em></p>
  </div>
</div>


<footer class="bg-dark text-light text-center py-3">
  <p>&copy; 2025 Piccolo Burgers — Developed by: <strong>Jazmin Abigail Gaido - Mariano Jesús Ceballos - Juan Pablo Medina</strong></p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>

<script>
  function actualizarContador() {
    const carrito = JSON.parse(localStorage.getItem("carrito")) || [];
    document.getElementById("contador-carrito").textContent = carrito.length;
  }

  document.querySelectorAll(".btn-agregar").forEach(boton => {
  boton.addEventListener("click", () => {
    const item = {
      id: boton.dataset.id,
      nombre: boton.dataset.nombre,
      precio: parseFloat(boton.dataset.precio),
      img: boton.dataset.img
    };

    let carrito = JSON.parse(localStorage.getItem("carrito")) || [];
    carrito.push(item);
    localStorage.setItem("carrito", JSON.stringify(carrito));
    actualizarContador();

    // Mostrar nombre en el toast
    document.getElementById("toastProductoNombre").textContent = item.nombre;

    // Mostrar toast
    const toast = new bootstrap.Toast(document.getElementById("toastAgregado"), { delay: 2500 });
    toast.show();

  });
});


  window.onload = actualizarContador;
</script>

<a href="#top" id="scrollTopBtn" class="btn btn-gold" style="
  position: fixed;
  bottom: 30px;
  right: 30px;
  z-index: 999;
  display: none;
  font-size: 1.5rem;
  border-radius: 50%;
  padding: 12px 16px;
">
  <i class="fas fa-arrow-up"></i>
</a>

<script>
  // Mostrar u ocultar botón según scroll
  window.onscroll = function () {
    const btn = document.getElementById("scrollTopBtn");
    if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
      btn.style.display = "block";
    } else {
      btn.style.display = "none";
    }
  };
</script>

<script>
  document.getElementById("categoria").addEventListener("change", function () {
    const categoria = this.value;
    fetch("filtrar_menu.php?categoria=" + encodeURIComponent(categoria))
      .then(response => response.text())
      .then(html => {
        document.getElementById("contenedor-menu").innerHTML = html;
        document.querySelectorAll(".btn-agregar").forEach(boton => {
          boton.addEventListener("click", () => {
            const item = {
              id: boton.dataset.id,
              nombre: boton.dataset.nombre,
              precio: parseFloat(boton.dataset.precio),
              img: boton.dataset.img
            };
            let carrito = JSON.parse(localStorage.getItem("carrito")) || [];
            carrito.push(item);
            localStorage.setItem("carrito", JSON.stringify(carrito));
            actualizarContador();

            // Mostrar nombre del producto en el modal
            document.getElementById("nombreProductoAgregado").textContent = item.nombre;

            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById("popupAgregado"));
            modal.show();
          });
        });
      });

  });
</script>

<!-- Toast de producto agregado -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
  <div id="toastAgregado" class="toast align-items-center text-white bg-success border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">
        <strong id="toastProductoNombre"></strong> fue agregado al carrito.
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
    </div>
  </div>
</div>


</body>
</html>