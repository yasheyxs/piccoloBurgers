<?php session_start(); ?>
<?php
include("admin/bd.php");

// Obtener banners
$sentencia = $conexion->prepare("SELECT * FROM tbl_banners ORDER BY id DESC LIMIT 1");
$sentencia->execute();
$lista_banners = $sentencia->fetchAll(PDO::FETCH_ASSOC);

// Obtener testimonios
$sentencia = $conexion->prepare("SELECT * FROM tbl_testimonios ORDER BY id DESC");
$sentencia->execute();
$lista_testimonios = $sentencia->fetchAll(PDO::FETCH_ASSOC);

// Procesar el formulario de contacto
if ($_POST) {
  $nombre = filter_var($_POST["nombre"], FILTER_SANITIZE_STRING);
  $correo = filter_var($_POST["correo"], FILTER_VALIDATE_EMAIL);
  $mensaje = filter_var($_POST["mensaje"], FILTER_SANITIZE_STRING);

  if ($nombre && $correo && $mensaje) {
    $sql = "INSERT INTO tbl_comentarios (nombre, correo, mensaje) VALUES (:nombre, :correo, :mensaje)";
    $resultado = $conexion->prepare($sql);
    $resultado->bindParam(':nombre', $nombre, PDO::PARAM_STR);
    $resultado->bindParam(':correo', $correo, PDO::PARAM_STR);
    $resultado->bindParam(':mensaje', $mensaje, PDO::PARAM_STR);
    $resultado->execute();

    $_SESSION['toast'] = "¡Gracias por tu comentario!";
  } else {
    $_SESSION['toast'] = "Hubo un error al enviar el formulario.";
  }

  header("Location: index.php#contacto");
  exit;
}


?>

<!doctype html>
<html lang="en">

<head>
  <title>Piccolo Burgers</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

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
      padding-top: 70px;
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

    img {
      max-width: 100%;
      height: auto;
    }

    .table-responsive {
      margin-top: 1rem;
    }

    table {
      width: 100%;
      word-wrap: break-word;
    }

    .navbar {
      background-color: #111;
    }

    .navbar-brand,
    .nav-link {
      font-family: var(--font-main);
      font-size: 1.2rem;
    }

    .btn,
    .form-control,
    #categoria {
      font-size: 1.2rem;
      border-radius: 8px;
      background-color: var(--gray-bg);
      color: var(--text-light);
      border: 1px solid #444;
    }

    .form-control::placeholder,
    #categoria option {
      color: var(--text-muted);
    }

    .form-control:focus {
      border-color: var(--main-gold);
      box-shadow: 0 0 0 0.2rem rgba(250, 195, 12, 0.25);
    }

    #categoria {
      padding: 0.375rem 1.75rem 0.375rem 0.75rem;
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg width='10' height='7' viewBox='0 0 10 7' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1L5 5L9 1' stroke='%23fac30c' stroke-width='2'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 0.75rem center;
      background-size: 10px 7px;
      cursor: pointer;
    }

    .btn-gold,
    .btn-agregar {
      background-color: var(--main-gold);
      color: #000;
      color: #3a2a00 !important;
      font-weight: bold;
      border: none;
      border-radius: 30px;
      font-size: 1rem;
      transition: background-color 0.3s ease, transform 0.3s ease;
    }

    .btn-gold:hover,
    .btn-agregar:hover {
      background-color: var(--gold-hover);
      transform: scale(1.05);
    }

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
      width: 100%;
      height: 200px;
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

    #contenedor-menu .card {
      min-height: 250px;
    }

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

    #horario p,
    #horario h3 {
      color: var(--text-light);
    }

    footer {
      background-color: #111;
      padding: 20px;
      text-align: center;
      font-size: 0.9rem;
      color: var(--text-muted);
    }

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

    .jumbotron {
      margin-bottom: 3rem;
      padding: 2rem;
      background: linear-gradient(to bottom, #1a1a1a 0%, #1f1f1f 100%);
      border-radius: 1rem;
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
    }

    #testimonios {
      margin-top: 2rem;
      padding-top: 3rem;
    }

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
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
      z-index: 999;
      transition: transform 0.3s, box-shadow 0.3s;
      text-decoration: none;
    }

    #scrollTopBtn:hover {
      transform: scale(1.1);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
      color: #000;
      text-decoration: none;
    }

    .alert-warning {
      background-color: #3a2a00;
      color: var(--main-gold);
      border: 1px solid var(--main-gold);
    }

    .alert-warning .btn-gold:hover {
      color: #3a2a00 !important;
    }

    #btn-mostrar-mas {
      display: block;
      margin: 20px auto;
      text-align: center;
    }

    .nav-link i.fas.fa-user-circle,
    .nav-link i.fas.fa-sign-out-alt {
      font-size: 1.35rem;
      vertical-align: middle;
    }

    .no-resize {
      resize: none;
    }

    .form-control:focus {
      background-color: #2c2c2c;
      color: #fff;
      border-color: #ffc107;
      box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
    }
  </style>


</head>

<body id="top">

  <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">

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

          <!-- Dropdown compacto -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="extrasDropdown" role="button" data-bs-toggle="dropdown">
              Más
            </a>
            <ul class="dropdown-menu dropdown-menu-dark">
              <li><a class="dropdown-item" href="#puntos">Puntos</a></li>
              <li><a class="dropdown-item" href="#ubicacion">Ubicación</a></li>
              <li><a class="dropdown-item" href="#contacto">Contacto</a></li>
              <li><a class="dropdown-item" href="#horario">Horarios</a></li>
            </ul>
          </li>

          <!-- Carrito -->
          <li class="nav-item">
            <a class="nav-link position-relative" href="carrito.php">
              <i class="fas fa-shopping-cart"></i>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="contador-carrito" style="font-size: 0.7rem;">
                0
              </span>
            </a>
          </li>

          <!-- Sesión -->
          <?php if (isset($_SESSION["cliente"])): ?>
            <li class="nav-item">
              <a href="perfil_cliente.php" class="nav-link" title="<?= htmlspecialchars($_SESSION["cliente"]["nombre"]) ?>">
                <i class="fas fa-user-circle"></i>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#logoutModal" title="Cerrar sesión">
                <i class="fas fa-sign-out-alt"></i>
              </a>
            </li>

          <?php else: ?>
            <li class="nav-item">
              <a href="login_cliente.php" class="btn btn-gold rounded-pill px-4 py-2 ms-2">
                <i></i> Iniciar sesión / Registrarse
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>

  <?php if (!isset($_SESSION["cliente"])): ?>
    <section class="container mt-3 text-center">
      <div class="alert alert-warning alert-dismissible fade show" role="alert" data-aos-up="fade-up" data-aos="fade-up" style="font-weight: bold; font-size: 1.2rem;">
        🎉 ¡Registrate ahora, ganá puntos y <span style="color: #fac30c;">canjealos por descuentos</span>! 🎉
        <a href="login_cliente.php" class="btn btn-gold rounded-pill px-4 py-2 ms-3 btn-sm">
          <i></i> Iniciar sesión / Registrarse
        </a>
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

  <section id="menu" class="container my-5">
    <h2 class="text-center mb-4">Nuestro Menú</h2>

    <!-- Controles de filtrado -->
    <div class="row mb-4">
      <div class="col-md-6">
        <input type="text" id="buscador-menu" class="form-control" placeholder="Buscar en el menú...">
      </div>
      <div class="col-md-6">
        <select id="categoria" class="form-select">
          <option value="">Todas las categorías</option>
          <option value="Acompañamientos">Acompañamientos</option>
          <option value="Hamburguesas">Hamburguesas</option>
          <option value="Bebidas">Bebidas</option>
          <option value="Lomitos y Sándwiches">Lomitos y Sándwiches</option>
          <option value="Pizzas">Pizzas</option>
        </select>
      </div>
    </div>

    <!-- Contenedor de tarjetas -->
    <div id="contenedor-menu" class="row row-cols-2 row-cols-md-4 g-4">
      <!-- Las tarjetas se insertan dinámicamente -->
    </div>

    <!-- Contenedor del botón "Mostrar más" -->
    <div id="contenedor-boton-mas" class="text-center mt-4"></div>
  </section>


  <section id="nosotros" class="container mt-5">
    <h2 class="text-center mb-4">Nosotros</h2>
    <div class="jumbotron p-4" data-aos-up="fade-up" data-aos="fade-up" style="background: linear-gradient(to bottom, #1a1a1a, #111); color: var(--text-light); border-radius: 1rem; box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);">
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

  <section id="ubicacion" class="container-fluid p-5 text-center" style="background: linear-gradient(to top, #1a1a1a, #111);">
    <h2 class="mb-4" data-aos="fade-up">Nuestra Ubicación</h2>
    <p class="mb-4" data-aos="fade-up" data-aos-delay="100">Encontranos fácilmente en nuestro local 🍔✨</p>
    <div class="d-flex justify-content-center" data-aos="zoom-in" data-aos-delay="200">
      <div class="shadow-lg" style="width: 350px; height: 350px; border-radius: 15px; overflow: hidden;">
        <iframe
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3400.107065015397!2d-63.53723899007664!3d-31.548676202549203!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94332a4ac325a7ad%3A0x91ff9ca646897a8f!2s25%20de%20Mayo%201295%2C%20X5963%20Villa%20del%20Rosario%2C%20C%C3%B3rdoba!5e0!3m2!1ses-419!2sar!4v1756841415057!5m2!1ses-419!2sar"
          width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"
          referrerpolicy="no-referrer-when-downgrade">
        </iframe>
      </div>
    </div>
  </section>

  <section id="contacto" class="container mt-4" data-aos-up="fade-up" data-aos="fade-up">
    <h2>Contacto</h2>
    <p>Estamos acá para servirte.</p>
    <?php if (isset($_SESSION['mensaje'])): ?>
      <div class="alert alert-warning text-center mt-3">
        <?php echo $_SESSION['mensaje'];
        unset($_SESSION['mensaje']); ?>
      </div>
    <?php endif; ?>

    <form action="?" method="post" id="formContacto">
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
        <textarea id="message" class="form-control no-resize" name="mensaje" rows="6" required></textarea>
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
  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
  <script>
    AOS.init({
      once: false,
      duration: 800
    });
  </script>

  <script>
    const buscadorInput = document.getElementById("buscador-menu");
    const categoriaSelect = document.getElementById("categoria");
    const contenedor = document.getElementById("contenedor-menu");
    const contenedorBotonMas = document.getElementById("contenedor-boton-mas");

    let offset = 0;
    const limit = 8;
    let btnMostrarMas = null;

    function actualizarContador() {
      const carrito = JSON.parse(localStorage.getItem("carrito")) || [];
      const contador = document.getElementById("contador-carrito");
      if (contador) contador.textContent = carrito.length;
    }

    function onAddClick(e) {
      const boton = e.currentTarget;
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

      const toastNombre = document.getElementById("toastProductoNombre");
      if (toastNombre) toastNombre.textContent = item.nombre;

      const toastEl = document.getElementById("toastAgregado");
      if (toastEl) {
        const toast = new bootstrap.Toast(toastEl, {
          delay: 2500
        });
        toast.show();
      }

    }

    function reattachAddButtons() {
      document.querySelectorAll(".btn-agregar").forEach(b => {
        b.removeEventListener('click', onAddClick);
        b.addEventListener('click', onAddClick);
      });
    }

    function debounce(fn, wait = 200) {
      let t;
      return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), wait);
      };
    }

    function filtrarMenu(reset = true) {
      const texto = buscadorInput.value.trim();
      const categoria = categoriaSelect.value;

      if (reset) {
        offset = 0;
        contenedor.innerHTML = "";
        contenedorBotonMas.innerHTML = "";
      }

      fetch(`filtrar_menu.php?categoria=${encodeURIComponent(categoria)}&busqueda=${encodeURIComponent(texto)}&offset=${offset}&limit=${limit}`)
        .then(resp => resp.text())
        .then(html => {
          const temp = document.createElement("div");
          temp.innerHTML = html;

          const tarjetas = temp.querySelectorAll(".col");
          tarjetas.forEach(t => contenedor.appendChild(t));

          const boton = temp.querySelector("#btn-mostrar-mas");
          if (boton) {
            contenedorBotonMas.innerHTML = "";
            contenedorBotonMas.appendChild(boton);
            boton.addEventListener("click", cargarMasProductos);
          }

          reattachAddButtons();
          AOS.refresh();
        })
        .catch(err => {
          console.error("Error al filtrar menú:", err);
        });
    }

    function cargarMasProductos() {
      const categoria = categoriaSelect.value;
      const texto = buscadorInput.value.trim();

      fetch(`filtrar_menu.php?categoria=${encodeURIComponent(categoria)}&busqueda=${encodeURIComponent(texto)}&offset=${offset}&limit=${limit}`)
        .then(response => response.text())
        .then(html => {
          const temp = document.createElement("div");
          temp.innerHTML = html;

          const tarjetas = temp.querySelectorAll(".col");
          tarjetas.forEach(t => contenedor.appendChild(t));

          const boton = temp.querySelector("#btn-mostrar-mas");
          contenedorBotonMas.innerHTML = "";
          if (boton) {
            contenedorBotonMas.appendChild(boton);
            boton.addEventListener("click", cargarMasProductos);
          }

          offset += limit;
          reattachAddButtons();
          AOS.refresh();
        })
        .catch(error => {
          console.error("Error al cargar más productos:", error);
        });
    }

    buscadorInput.addEventListener("input", debounce(() => filtrarMenu(true), 300));
    categoriaSelect.addEventListener("change", () => filtrarMenu(true));

    window.addEventListener("DOMContentLoaded", () => {
      actualizarContador();
      filtrarMenu(true);
    });

    document.addEventListener('DOMContentLoaded', function() {
      const toastEl = document.getElementById('toastContacto');
      if (toastEl) {
        const toast = new bootstrap.Toast(toastEl, {
          autohide: true,
          delay: 2500
        });
        toast.show();
      }
    });
  </script>

  <!-- Toast fijo en el DOM -->
  <div class="toast-container position-fixed bottom-0 end-0 p-3 z-3">
    <div id="toastAgregado" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          Producto <span id="toastProductoNombre"></span> agregado al carrito.
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
      </div>
    </div>
  </div>

  <?php include("componentes/carrito_button.php"); ?>
  <?php include("componentes/whatsapp_button.php"); ?>
  <?php include("componentes/scroll_button.php"); ?>

  <!-- Modal de cierre de sesión -->
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
          <a href="./logout_cliente.php" class="btn btn-danger px-4">
            <i class="fas fa-door-open me-1"></i> Cerrar Sesión
          </a>
        </div>
      </div>
    </div>
  </div>
  <?php if (isset($_SESSION['toast'])): ?>
    <div class="toast-container position-fixed bottom-0 end-0 p-3 z-3">
      <div id="toastContacto" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">
            <?php echo $_SESSION['toast'];
            unset($_SESSION['toast']); ?>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
        </div>
      </div>
    </div>

  <?php endif; ?>
</body>
</html>