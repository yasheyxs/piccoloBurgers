<?php session_start(); ?>
<?php
include("admin/bd.php");

$categorias_disponibles = ["Acompañamientos", "Hamburguesas", "Bebidas", "Lomitos y Sándwiches", "Pizzas"]; // Categorías disponibles

$categoria_seleccionada = $_GET['categoria'] ?? '';
$lista_menu = [];
// Verificar si la categoría seleccionada es válida
$sentencia = $conexion->prepare("SELECT * FROM tbl_banners ORDER BY id DESC limit 1 ");
$sentencia->execute();
$lista_banners = $sentencia->fetchAll(PDO::FETCH_ASSOC);
// Obtener testimonios
$sentencia = $conexion->prepare("SELECT * FROM tbl_testimonios ORDER BY id DESC");
$sentencia->execute();
$lista_testimonios = $sentencia->fetchAll(PDO::FETCH_ASSOC);
// Filtrar el menú por categoría si se ha seleccionado una
if ($categoria_seleccionada && in_array($categoria_seleccionada, $categorias_disponibles)) {
  $sentencia = $conexion->prepare("SELECT * FROM tbl_menu WHERE categoria = ? ORDER BY id DESC");
  $sentencia->execute([$categoria_seleccionada]);
  $lista_menu = $sentencia->fetchAll(PDO::FETCH_ASSOC);
} else {// Si no se ha seleccionado una categoría o es inválida, mostrar todo el menú
  $sentencia = $conexion->prepare("SELECT * FROM tbl_menu ORDER BY id DESC");
  $sentencia->execute();
  $lista_menu = $sentencia->fetchAll(PDO::FETCH_ASSOC);
}
// Procesar el formulario de contacto
if ($_POST) {
  $nombre = filter_var($_POST["nombre"], FILTER_SANITIZE_STRING);
  $correo = filter_var($_POST["correo"], FILTER_VALIDATE_EMAIL);
  $mensaje = filter_var($_POST["mensaje"], FILTER_SANITIZE_STRING);
  // Validar campos
  if ($nombre && $correo && $mensaje) {
    $sql = "INSERT INTO tbl_comentarios (nombre, correo, mensaje)
                VALUES (:nombre, :correo, :mensaje)";

    $resultado = $conexion->prepare($sql);
    $resultado->bindParam(':nombre', $nombre, PDO::PARAM_STR);
    $resultado->bindParam(':correo', $correo, PDO::PARAM_STR);
    $resultado->bindParam(':mensaje', $mensaje, PDO::PARAM_STR);
    $resultado->execute();
  }// Si el formulario se envió correctamente, redirigir a la página de inicio
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
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;600&display=swap" rel="stylesheet">

  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
  <link rel="icon" href="./img/favicon.png" type="image/x-icon" />

  <!-- AOS CSS -->
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">


  <style>
    :root {
      --main-gold: #fac30c;
      --gold-hover: #e0ae00;
      --dark-bg: #1a1a1a;
      --gray-bg: #2c2c2c;
      --text-light: #ffffff;
      --text-muted: #cccccc;
      --font-main: 'Inter', sans-serif;
      --font-title: 'Bebas Neue', sans-serif;
    }

    body {
      font-family: var(--font-main);
      background-color: var(--dark-bg);
      color: var(--text-light);
      font-size: 1rem;
      line-height: 1.6;
    }

    h1,
    h2,
    h3,
    .navbar-brand {
      font-family: var(--font-title);
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    h1 {
      font-size: 4rem;
    }

    h2 {
      font-size: 2.5rem;
      margin-bottom: 1rem;
    }

    h3 {
      font-size: 2rem;
      margin-bottom: 1rem;
    }

    /* Navbar */
    .navbar {
      background-color: #111;
    }

    .navbar-brand,
    .nav-link {
      font-family: var(--font-main);
      font-size: 1.2rem;
    }

    /* Botón dorado */
    .btn-gold {
      background-color: var(--main-gold);
      color: #000;
      font-weight: bold;
      border: none;
      border-radius: 30px;
      padding: 10px 30px;
      transition: all 0.3s ease;
      font-size: 1rem;
    }

    .btn-gold:hover {
      background-color: var(--gold-hover);
      transform: scale(1.05);
    }

    /* Cards */
    .card {
      background-color: var(--gray-bg);
      border-radius: 16px;
      border: none;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      overflow: hidden;
    }

    .card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 24px rgba(0, 0, 0, 0.4);
    }

    .card-img-top {
      display: block;
      max-height: 200px;
      width: 100%;
      object-fit: cover;
      transition: transform 0.3s ease;
    }

    .card:hover .card-img-top {
      transform: scale(1.05);
    }

    .card-title {
      font-family: var(--font-title);
      font-size: 1.8rem;
      color: var(--text-light);
    }

    .card-text {
      font-size: 0.9rem;
      color: var(--text-muted);
    }

    .card-footer {
      background-color: transparent;
      color: var(--text-light);
      font-weight: 600;
      font-size: 1rem;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
      border-left: 4px solid var(--main-gold);
      padding-left: 12px;
    }

    .card-footer::before {
      content: "👤 ";
    }

    .form-control {
      background-color: var(--gray-bg);
      color: var(--text-light);
      border: 1px solid #444;
      font-size: 1.2rem;
      border-radius: 8px;
    }

    .form-control::placeholder {
      color: var(--text-muted);
    }

    .form-control:focus {
      background-color: var(--gray-bg);
      color: var(--text-light);
      border-color: var(--main-gold);
      box-shadow: 0 0 0 0.2rem rgba(250, 195, 12, 0.25);
    }

    /*select del filtro */
    #categoria {
      background-color: var(--gray-bg);
      color: var(--text-light);
      border: 1px solid #444;
      font-size: 1.2rem;
      border-radius: 8px;
      padding: 0.375rem 1.75rem 0.375rem 0.75rem;
      /* Ajuste para flecha */
      appearance: none;
      /* Quitar estilo nativo para personalizar */
      -webkit-appearance: none;
      -moz-appearance: none;
      background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg width='10' height='7' viewBox='0 0 10 7' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1L5 5L9 1' stroke='%23fac30c' stroke-width='2'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 0.75rem center;
      background-size: 10px 7px;
      cursor: pointer;
    }

    #categoria option {
      background-color: var(--gray-bg);
      color: var(--text-light);
    }


    /* Botón agregar */
    .btn-agregar {
      background-color: var(--main-gold);
      color: #000;
      font-weight: bold;
      border: none;
      padding: 10px 20px;
      border-radius: 30px;
      width: 100%;
      font-size: 1rem;
      transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .btn-agregar:hover {
      background-color: var(--gold-hover);
      transform: scale(1.05);
    }

    /* Hero */
    .hero {
      background: radial-gradient(circle at top left, #2c2c2c 0%, #1a1a1a 100%);
      padding: 100px 20px;
      text-align: center;
      position: relative;
    }

    .hero h1 {
      font-family: var(--font-title);
      font-size: 5rem;
      color: var(--main-gold);
      text-shadow: 4px 4px 10px rgba(0, 0, 0, 0.7);
    }

    .hero p {
      color: var(--text-muted);
      font-size: 1.1rem;
      max-width: 600px;
      margin: 20px auto;
    }

    /* Horario */
    #horario p,
    #horario h3 {
      color: var(--text-light);
    }

    /* Footer */
    footer {
      background-color: #111;
      padding: 20px;
      text-align: center;
      font-size: 0.9rem;
      color: var(--text-muted);
    }

    /* Banner */

    .banner-img::after {
      content: "";
      position: absolute;
      bottom: 0;
      left: 0;
      height: 100px;
      width: 100%;
      background: linear-gradient(to bottom, rgba(26, 26, 26, 0), #1a1a1a 90%);
      z-index: 1;
    }

    .banner-text {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      text-align: center;
      color: #fff;
      text-shadow: 5px 5px 9px rgba(0, 0, 0, 0.7);
      z-index: 2;
    }

    .banner-text p {
      text-shadow: 4px 4px 10px rgba(0, 0, 0, 0.7);
    }

  /* jumbotron */
    .jumbotron {
      margin-bottom: 3rem;
      padding: 2rem;
      background: linear-gradient(to bottom, #1a1a1a 0%, #1f1f1f 100%);
      border-radius: 1rem;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
    }

    /* testimonios */
    #testimonios {
      margin-top: 2rem;
      padding-top: 3rem;
    }

    /* ScrollTop button */
    #scrollTopBtn {
  position: fixed;
  bottom: 30px;
  right: 30px;
  background-color: var(--main-gold);
  color: #000;
  font-size: 1.8rem;
  border-radius: 50%;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
  z-index: 999;
  transition: transform 0.3s, box-shadow 0.3s;
  text-decoration: none;
}

#scrollTopBtn:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 16px rgba(0,0,0,0.4);
  color: #000;
  text-decoration: none;
}

.alert-warning {
  background-color: #3a2a00;
  color: #fac30c;
  border: 1px solid #fac30c;
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
          <li class="nav-item"><a class="nav-link" href="#nosotros">Nosotros</a></li>
          <li class="nav-item"><a class="nav-link" href="#puntos">Puntos</a></li>
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

  <?php if (!isset($_SESSION["cliente"])): ?>
  <section class="container mt-3 text-center">
    <div class="alert alert-warning alert-dismissible fade show" role="alert" data-aos-up="fade-up" data-aos="fade-up" style="font-weight: bold; font-size: 1.2rem;">
      🎉 ¡Registrate ahora, ganá puntos y <span style="color: #fac30c;">canjealos por descuentos</span>! 🎉
      <a href="login_cliente.php" class="btn btn-sm btn-gold ms-3">Iniciar sesión / Registrarse</a>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
  </section>
<?php endif; ?>


  <section class="container-fluid p-0">
    <div class="banner-img" style="position:relative; background:url('img/pexels-valeriya-1199960.jpg') center/cover no-repeat; height:400px;">
      <div class="banner-text" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); text-align:center; color:#fff; text-shadow: 5px 5px 9px rgba(0,0,0,0.7);">
        <?php foreach ($lista_banners as $banner) { ?>
          <h1 style="font-size: 5rem;"><?php echo $banner['titulo']; ?></h1>
          <p><?php echo $banner['descripcion']; ?></p>
          <a href="<?php echo $banner['link']; ?>" class="btn btn-gold">Ver Menú</a>
        <?php } ?>
      </div>
    </div>
  </section>

  <section class="container mt-4 text-center">
    <div class="jumbotron p-4" data-aos-up="fade-up" data-aos="fade-up" style="background: linear-gradient(to bottom, #1a1a1a, #111); color: var(--text-light);">
      <h2>¡Bienvenidx a Piccolo Burgers!</h2>
      <p>Descubre las verdaderas hamburguesas. Siempre 100% cargadas de sabor.</p>
    </div>
  </section>

  <section id="testimonios" class="py-5" style="background: linear-gradient(to bottom, #2c2c2c, #1a1a1a);">
    <div class="container">
      <h2 class="text-center mb-4">Testimonios</h2>
      <div class="row">
        <?php foreach ($lista_testimonios as $testimonio) { ?>
          <div class="col-md-6 d-flex" data-aos-up="fade-up" data-aos="fade-up">
            <div class="card mb-4 w-100">
              <div class="card-body">
                <p class="card-text"><?php echo $testimonio["opinion"]; ?></p>
              </div>
              <div class="card-footer">
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
    <select id="categoria" class="form-select d-inline w-auto me-3">
      <option value="">Todos</option>
      <?php foreach ($categorias_disponibles as $cat): ?>
        <option value="<?= $cat ?>" <?= ($cat == $categoria_seleccionada) ? 'selected' : '' ?>><?= $cat ?></option>
      <?php endforeach; ?>
    </select>

    <input type="text" id="buscador-menu" class="form-control d-inline w-auto" style="max-width: 300px;" placeholder="Buscar por nombre...">
  </form>
</div>


    <div id="contenedor-menu" class="row row-cols-1 row-cols-md-4 g-4">
      <?php foreach ($lista_menu as $registro) { ?>
        <div class="col d-flex" data-aos-up="fade-up" data-aos="fade-up">
          <div class="card position-relative d-flex flex-column h-100 w-100">

            <img src="img/menu/<?php echo $registro["foto"]; ?>" class="card-img-top" alt="Foto de <?php echo $registro["nombre"]; ?>">
            <div class="card-body d-flex flex-column">
              <h5 class="card-title"><?php echo $registro["nombre"]; ?></h5>
              <p class="card-text small"><strong><?php echo $registro["ingredientes"]; ?></strong></p>
              <p class="card-text"><strong>Precio:</strong> $<?php echo $registro["precio"]; ?></p>
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

  <section id="nosotros" class="container mt-5">
  <h2 class="text-center mb-4">Nosotros</h2>
  <div class="jumbotron p-4"  data-aos-up="fade-up" data-aos="fade-up" style="background: linear-gradient(to bottom, #1a1a1a, #111); color: var(--text-light); border-radius: 1rem; box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);">
    <p class="lead text-center" style="font-size: 1.2rem; max-width: 700px; margin: 0 auto;">
      En <strong>Piccolo Burgers</strong> somos apasionados por crear las hamburguesas más sabrosas y cargadas de sabor, usando ingredientes frescos y de calidad. Nuestro compromiso es ofrecerte una experiencia gastronómica inolvidable, con un servicio cálido y un ambiente acogedor. ¡Gracias por elegirnos para compartir momentos deliciosos!
    </p>
  </div>
</section>

<section id="puntos" class="container mt-5">
  <h2 class="text-center mb-4">Sistema de puntos</h2>
  <div class="jumbotron p-4" data-aos-up="fade-up" data-aos="fade-up" style="background: linear-gradient(to bottom, #1a1a1a, #111); color: var(--text-light); border-radius: 1rem; box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);">
    <p class="lead text-center" style="font-size: 1.2rem; max-width: 800px; margin: 0 auto;">
      Cada vez que hacés un pedido registrado, <strong>ganás puntos</strong> que podés canjear por <strong>descuentos exclusivos</strong> en tus próximas compras.  
      <br><br>
      Cuanto más pedís, <strong>más ahorrás</strong> 🍔✨
    </p>
  </div>
</section>

  <section id="contacto" class="container mt-4" data-aos-up="fade-up" data-aos="fade-up">
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

  <div id="horario" class="text-center p-5" data-aos-up="fade-up" data-aos="fade-up" style="background: linear-gradient(to top, #2c2c2c, #1a1a1a);">
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
    // Actualizar contador de carrito al cargar la página
    function actualizarContador() {
      const carrito = JSON.parse(localStorage.getItem("carrito")) || [];
      document.getElementById("contador-carrito").textContent = carrito.length;
    }
// Agregar evento a los botones de agregar al carrito
    document.querySelectorAll(".btn-agregar").forEach(boton => {
      boton.addEventListener("click", () => {
        const item = {
          id: boton.dataset.id,
          nombre: boton.dataset.nombre,
          precio: parseFloat(boton.dataset.precio),
          img: boton.dataset.img
        }; // Obtener el carrito del localStorage o inicializarlo si no existe
        // Agregar el item al carrito
        let carrito = JSON.parse(localStorage.getItem("carrito")) || [];
        carrito.push(item);
        localStorage.setItem("carrito", JSON.stringify(carrito));
        actualizarContador();

        // Mostrar nombre en el toast
        document.getElementById("toastProductoNombre").textContent = item.nombre;

        // Mostrar toast
        const toast = new bootstrap.Toast(document.getElementById("toastAgregado"), {
          delay: 2500
        });
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
    // Mostrar u ocultar botón de flecha según scroll
    window.onscroll = function() {
      const btn = document.getElementById("scrollTopBtn");
      if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
        btn.style.display = "block";
      } else {
        btn.style.display = "none";
      }
    };
  </script>

  <script>
    // Filtrar el menú por categoría al cambiar el select
    document.getElementById("categoria").addEventListener("change", function() {
      const categoria = this.value;
      fetch("filtrar_menu.php?categoria=" + encodeURIComponent(categoria)) // Llamar al script que filtra el menú
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
              };// Obtener el carrito del localStorage o inicializarlo si no existe
              let carrito = JSON.parse(localStorage.getItem("carrito")) || []; // Agregar el item al carrito
              carrito.push(item);// Guardar el carrito actualizado en el localStorage
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

  <!-- Toast de producto -->
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

  <?php include("componentes/whatsapp_button.php"); ?>

  <script>
  const buscadorInput = document.getElementById("buscador-menu");
  const categoriaSelect = document.getElementById("categoria");

  function filtrarMenu() {
    const texto = buscadorInput.value.toLowerCase();
    const categoria = categoriaSelect.value;

    fetch(`filtrar_menu.php?categoria=${encodeURIComponent(categoria)}&busqueda=${encodeURIComponent(texto)}`)
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

            document.getElementById("toastProductoNombre").textContent = item.nombre;
            const toast = new bootstrap.Toast(document.getElementById("toastAgregado"), { delay: 2500 });
            toast.show();
          });
        });
      });
  }

  categoriaSelect.addEventListener("change", filtrarMenu);
  buscadorInput.addEventListener("input", filtrarMenu);

  let lastScroll = 0;

window.addEventListener('scroll', () => {
  const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
  const scrollingDown = currentScroll > lastScroll;
  lastScroll = currentScroll <= 0 ? 0 : currentScroll; // Evitar negativos

  // Para cada elemento que tenga data-aos-up y data-aos-down
  document.querySelectorAll('[data-aos-up][data-aos-down]').forEach(el => {
    if(scrollingDown){
      // Scroll hacia abajo: usar animación data-aos-up
      el.setAttribute('data-aos', el.getAttribute('data-aos-up'));
    } else {
      // Scroll hacia arriba: usar animación data-aos-down
      el.setAttribute('data-aos', el.getAttribute('data-aos-down'));
    }
  });

  AOS.refresh(); // refrescar animaciones
});

</script>

<!-- AOS JS -->
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({
  once: false,
  duration: 800,
});

</script>

</body>

</html>