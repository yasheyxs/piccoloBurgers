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

    $_SESSION['toast'] = [
      'mensaje' => "¡Gracias por tu comentario!",
      'tipo' => 'success'
    ];
  } else {
    $_SESSION['toast'] = [
      'mensaje' => "Hubo un error al enviar el formulario.",
      'tipo' => 'danger'
    ];
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

  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>

  <!-- AOS CSS -->
  <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
  <link rel="stylesheet" href="./custom.css">

</head>

<body id="top">

  <nav class="navbar navbar-expand-lg navbar-dark sticky-navbar bg-dark">
    <div class="container">
      <a class="navbar-brand" href="#"><i class="fas fa-utensils"></i> Piccolo Burgers</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <!-- Enlaces principales -->
          <li class="nav-item"><a class="nav-link" href="#inicio">Inicio</a></li>
          <li class="nav-item"><a class="nav-link" href="#menu">Menú</a></li>
          <li class="nav-item"><a class="nav-link" href="#nosotros">Nosotros</a></li>
          <li class="nav-item"><a class="nav-link" href="#testimonios">Testimonio</a></li>

          <!-- Dropdown compacto -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="extrasDropdown" role="button" data-bs-toggle="dropdown">
              Más
            </a>
            <ul class="dropdown-menu dropdown-menu-dark">
              <li><a class="dropdown-item" href="#puntos">Puntos</a></li>
              <li><a class="dropdown-item" href="#ubicacion">Ubicación</a></li>
              <li><a class="dropdown-item" href="#contacto">Contacto</a></li>
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
              <a href="#" class="nav-link" title="Cerrar sesión" data-bs-toggle="modal" data-bs-target="#logoutModal">
                <i class="fas fa-sign-out-alt"></i>
              </a>

            </li>
          <?php else: ?>
            <li class="nav-item">
              <a href="login_cliente.php" class="btn btn-gold rounded-pill px-4 py-2 ms-2">
                Iniciar sesión / Registrarse
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>


  <?php if (!isset($_SESSION["cliente"])): ?>
    <div id="registro-burbuja" class="registro-burbuja">
      <button type="button" class="cerrar-burbuja" onclick="cerrarBurbuja()">×</button>

      <h5 class="fw-bold mb-2 text-gold">¡Registrate y ganá puntos!</h5>
      <p>
        Acumulá <strong class="text-gold">puntos exclusivos</strong> en cada compra y canjealos por <strong>descuentos irresistibles</strong>.
      </p>

      <a href="registro_cliente.php" class="btn btn-gold w-100 mt-2">Registrarse</a>
    </div>
  <?php endif; ?>


  <section id="inicio" class="container-fluid p-0">
    <div class="banner-img" style="position:relative; background:url('img/BannerBG.jpg') center/cover no-repeat; height:100vh;">
      <div class="banner-text">
        <?php foreach ($lista_banners as $banner): ?>
          <h1 class="banner-heading"><?php echo $banner['titulo']; ?></h1>
          <p class="banner-subtext"><?php echo $banner['descripcion']; ?></p>
          <a href="<?php echo $banner['link']; ?>" class="btn btn-gold banner-btn">Ver Menú</a>
        <?php endforeach; ?>
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

    </div>

    <!-- Botón "Mostrar más" -->
    <div id="contenedor-boton-mas" class="text-center mt-4">
      <button id="btn-mostrar-mas" class="btn-gold">Mostrar más</button>
    </div>
  </section>

  <section id="nosotros" class="container mt-5">
    <div class="row align-items-center p-5"
      data-aos="fade-up"
      style="background: linear-gradient(145deg, #1e1e1e, #0d0d0d); color: var(--text-light); border-radius: 1.5rem; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);">

      <!-- Texto -->
      <div class="col-lg-6 mb-4 mb-lg-0 d-flex flex-column align-items-center align-items-lg-start">
        <h2 class="fw-bold display-6 mb-4 text-center w-100" style="color: var(--main-gold); letter-spacing: 1px;">
          Nosotros
        </h2>
        <p class="lead text-justify" style="font-size: 1.1rem; max-width: 750px; line-height: 1.8; margin: 0 auto; text-align: justify;">
          En <strong style="color: var(--main-gold);">Piccolo Burgers</strong> somos apasionados por crear las hamburguesas más sabrosas y cargadas de sabor, usando ingredientes frescos y de calidad.
          <br><br>
          Nuestro compromiso es ofrecerte una <strong>experiencia gastronómica inolvidable</strong>, con un servicio cálido y un ambiente acogedor.
          <br><br>
          ¡Gracias por elegirnos para compartir <span style="color: var(--main-gold);">momentos deliciosos</span>!
        </p>
      </div>

      <!-- Imagen -->
      <div class="col-lg-6 text-center">
        <img src="img/SobreNosotros.jpg" alt="Nosotros - Piccolo Burgers" class="img-fluid rounded-3 shadow-lg" style="max-height: 350px; object-fit: cover;">
      </div>
    </div>
  </section>

  <section id="puntos" class="container mt-5">
    <div class="row align-items-center p-5"
      data-aos="fade-up"
      style="background: linear-gradient(145deg, #1e1e1e, #0d0d0d); color: var(--text-light); border-radius: 1.5rem; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);">

      <!-- Imagen -->
      <div class="col-lg-6 text-center mb-4 mb-lg-0">
        <img src="img/Puntos.jpg" alt="Sistema de Puntos - Piccolo Burgers" class="img-fluid rounded-3 shadow-lg" style="max-height: 350px; object-fit: cover;">
      </div>

      <!-- Texto -->
      <div class="col-lg-6 d-flex flex-column align-items-center align-items-lg-start">
        <h2 class="fw-bold display-6 mb-4 text-center w-100" style="color: var(--main-gold); letter-spacing: 1px;">
          Sistema de Puntos
        </h2>
        <p class="lead text-justify" style="font-size: 1.15rem; max-width: 750px; line-height: 1.8; margin: 0 auto; text-align: justify;">
          Cada vez que hacés un pedido registrado,
          <strong style="color: var(--main-gold);">ganás puntos</strong>
          que podés canjear por
          <strong>descuentos exclusivos</strong> en tus próximas compras.
          <br><br>
          Cuanto más pedís,
          <strong style="color: var(--main-gold);">más ahorrás</strong> 🍔✨
        </p>
      </div>
    </div>
  </section>

  <section id="contacto" class="container mt-5">
    <div class="row p-5 align-items-center"
      data-aos="fade-up"
      style="background: linear-gradient(145deg, #1e1e1e, #0d0d0d); color: var(--text-light); border-radius: 1.5rem; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);">

      <!-- Formulario -->
      <div class="col-lg-6 d-flex flex-column justify-content-between mb-4 mb-lg-0">
        <div>
          <h2 class="fw-bold display-6 mb-3" style="color: var(--main-gold); letter-spacing: 1px;">
            Contacto
          </h2>
          <p class="lead mb-4" style="text-align: justify;">
            Estamos acá para servirte. Escribinos tu consulta y te respondemos a la brevedad.
          </p>
        </div>

        <form action="?" method="post" id="formContacto">
          <div class="mb-3">
            <label for="name">Nombre:</label>
            <input type="text" class="form-control rounded-3" name="nombre" placeholder="Escribe tu nombre..." required>
          </div>
          <div class="mb-3">
            <label for="email">Correo electrónico:</label>
            <input type="email" class="form-control rounded-3" name="correo" placeholder="Escribe tu correo electrónico..." required>
          </div>
          <div class="mb-3">
            <label for="message">Mensaje:</label>
            <textarea id="message" class="form-control rounded-3 no-resize" name="mensaje" rows="6" placeholder="Escribe tu mensaje..." required></textarea>
          </div>
          <input type="submit" class="btn btn-gold mt-2" value="Enviar mensaje">
        </form>
      </div>

      <!-- Imagen decorativa extendida -->
      <div class="col-lg-6 text-center">
        <img src="./img/Contacto.webp"
          alt="Contacto - Piccolo Burgers"
          class="img-fluid rounded-3 shadow-lg"
          style="max-height: 350px; width: 100%; object-fit: cover; border-radius: 1.2rem;">
      </div>
    </div>
  </section>


  <section id="ubicacion" class="container mt-5">
    <div class="row align-items-center p-5"
      data-aos="fade-up"
      style="background: linear-gradient(145deg, #1e1e1e, #0d0d0d); color: var(--text-light); border-radius: 1.5rem; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);">

      <!-- Texto -->
      <div class="col-lg-6 d-flex flex-column align-items-center align-items-lg-start mb-4 mb-lg-0">
        <h2 class="fw-bold display-6 mb-4 text-center w-100" style="color: var(--main-gold); letter-spacing: 1px;">
          Nuestra Ubicación
        </h2>
        <p class="lead text-justify" style="font-size: 1.15rem; max-width: 750px; line-height: 1.8; margin: 0 auto; text-align: justify;">
          Encontranos fácilmente en nuestro local en <strong style="color: var(--main-gold);">Villa del Rosario</strong>, Córdoba.
          <br><br>
          Estamos ubicados en <strong>25 de Mayo 1295</strong>, a pasos del centro. Acercate a disfrutar nuestras hamburguesas artesanales en un ambiente cálido y moderno 🍔✨
        </p>
      </div>

      <!-- Mapa -->
      <div class="col-lg-6 text-center">
        <div class="shadow-lg rounded-3 overflow-hidden" style="height: 350px;">
          <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3400.107065015397!2d-63.53723899007664!3d-31.548676202549203!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94332a4ac325a7ad%3A0x91ff9ca646897a8f!2s25%20de%20Mayo%201295%2C%20X5963%20Villa%20del%20Rosario%2C%20C%C3%B3rdoba!5e0!3m2!1ses-419!2sar!4v1756841415057!5m2!1ses-419!2sar"
            width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"
            referrerpolicy="no-referrer-when-downgrade">
          </iframe>
        </div>
      </div>
    </div>
  </section>


  <section id="testimonios" class="py-5" style="background: linear-gradient(to bottom, #2c2c2c, #1a1a1a); overflow: hidden;">
    <div class="container">
      <h2 class="text-center mb-4">Testimonios</h2>

      <div class="testimonios-carousel-container" style="position: relative; height: 140px; overflow: hidden;">
        <div class="testimonios-carousel" id="testimonios-carousel">
          <?php foreach ($lista_testimonios as $testimonio): ?>
            <div class="testimonio-card">
              <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                  <p class="card-text"><?= htmlspecialchars($testimonio["opinion"]) ?></p>
                  <div class="card-footer mt-auto">
                    <?= htmlspecialchars($testimonio["nombre"]) ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>

          <?php foreach ($lista_testimonios as $testimonio): ?>
            <div class="testimonio-card">
              <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-between">
                  <p class="card-text"><?= htmlspecialchars($testimonio["opinion"]) ?></p>
                  <div class="card-footer mt-auto">
                    <?= htmlspecialchars($testimonio["nombre"]) ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Script Testimonios -->
  <script>
    const wrapper = document.getElementById('testimonios-wrapper');
    const btnLeft = document.getElementById('btn-left');
    const btnRight = document.getElementById('btn-right');
    const scrollAmount = 340;

    btnLeft.addEventListener('click', () => {
      wrapper.scrollBy({
        left: -scrollAmount,
        behavior: 'smooth'
      });
    });

    btnRight.addEventListener('click', () => {
      wrapper.scrollBy({
        left: scrollAmount,
        behavior: 'smooth'
      });
    });
  </script>


  <footer id="footer" class="mt-5" style="background: linear-gradient(to top, #111, #1e1e1e); color: var(--text-light);">
    <div class="container py-5">
      <div class="row text-center text-md-start">

        <!-- Info principal -->
        <div class="col-md-4 mb-4">
          <h4 class="fw-bold mb-3" style="color: var(--main-gold);">Piccolo Burgers VDR</h4>
          <p>🍔 100% cargadas de sabor</p>
          <p>📍 25 de Mayo 1295</p>
          <p>🍽️ Servicio a mesa y Take Away</p>
        </div>

        <!-- Horarios -->
        <div class="col-md-4 mb-4">
          <h4 class="fw-bold mb-3" style="color: var(--main-gold);">Horario de atención</h4>
          <p><strong>Martes a Domingo y feriados</strong></p>
          <p>⌚ 20:00 hs - 00:30 hs</p>
          <p><em>Lunes cerrado</em></p>
        </div>

        <!-- Contacto -->
        <div class="col-md-4 mb-4">
          <h4 class="fw-bold mb-3" style="color: var(--main-gold);">Contacto</h4>
          <div class="d-flex justify-content-center justify-content-md-start gap-3">
            <a href="https://wa.me/543573438947" target="_blank" style="color: var(--main-gold);">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                <path d="M13.601 2.326A7.875 7.875 0 0 0 8.003.125a7.875 7.875 0 0 0-6.6 11.95L.125 15.875l3.8-1.25a7.875 7.875 0 0 0 4.078 1.125h.003a7.875 7.875 0 0 0 7.875-7.875 7.875 7.875 0 0 0-2.28-5.574zM8.003 14.25a6.25 6.25 0 0 1-3.2-.875l-.228-.137-2.25.75.75-2.25-.15-.237a6.25 6.25 0 1 1 5.078 2.75zm3.65-4.55c-.2-.1-1.175-.575-1.35-.637-.175-.062-.3-.1-.425.1-.125.2-.487.637-.6.762-.112.125-.225.137-.425.037-.2-.1-.85-.312-1.625-.987-.6-.537-1-1.2-1.125-1.4-.112-.2-.012-.3.088-.4.088-.087.2-.225.3-.337.1-.112.137-.2.2-.325.062-.125.025-.237-.012-.337-.037-.1-.425-1.025-.587-1.4-.15-.362-.3-.312-.425-.312h-.362c-.125 0-.325.037-.487.237-.162.2-.625.612-.625 1.487 0 .875.637 1.725.725 1.85.088.125 1.25 1.912 3.025 2.675.425.183.75.292 1.012.375.425.137.812.118 1.118.075.342-.05 1.175-.475 1.337-.937.162-.462.162-.85.112-.937-.05-.087-.175-.137-.375-.237z" />
              </svg>
            </a>
            <a href="https://www.facebook.com/profile.php?id=100087896013957" target="_blank" style="color: var(--main-gold);">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8.94 8.5H10l.5-2H8.94V5.5c0-.58.12-.9.94-.9H10V2.6c-.16-.02-.72-.06-1.37-.06-1.37 0-2.31.83-2.31 2.36V6.5H5v2h1.26v5h2.68v-5z" />
              </svg>
            </a>
            <a href="https://www.instagram.com/piccoloburgers/" target="_blank" style="color: var(--main-gold);">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 5.5A2.5 2.5 0 1 0 8 10a2.5 2.5 0 0 0 0-4.5zM8 9a1 1 0 1 1 0-2 1 1 0 0 1 0 2z" />
                <path d="M11.5 1h-7A3.5 3.5 0 0 0 1 4.5v7A3.5 3.5 0 0 0 4.5 15h7a3.5 3.5 0 0 0 3.5-3.5v-7A3.5 3.5 0 0 0 11.5 1zm2.5 10.5a2.5 2.5 0 0 1-2.5 2.5h-7a2.5 2.5 0 0 1-2.5-2.5v-7A2.5 2.5 0 0 1 4.5 2h7a2.5 2.5 0 0 1 2.5 2.5v7z" />
                <path d="M12 4.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z" />
              </svg>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Línea inferior -->
    <div class="bg-dark text-light text-center py-3">
      <p>&copy; 2025 Piccolo Burgers — Developed by:
        <strong>Jazmin Abigail Gaido - Mariano Jesús Ceballos - Juan Pablo Medina</strong>
      </p>
    </div>
  </footer>


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

  <script>
    function cerrarBurbuja() {
      const burbuja = document.getElementById('registro-burbuja');
      burbuja.classList.add('fade-out');
      setTimeout(() => {
        burbuja.style.display = 'none';
      }, 500);
    }
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
      <div id="toastContacto"
        class="toast align-items-center text-bg-<?= $_SESSION['toast']['tipo'] ?> border-0"
        role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">
            <?= htmlspecialchars($_SESSION['toast']['mensaje']) ?>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto"
            data-bs-dismiss="toast" aria-label="Cerrar"></button>
        </div>
      </div>
    </div>
    <?php unset($_SESSION['toast']); ?>
  <?php endif; ?>

  <?php include("componentes/carrito_button.php"); ?>
  <?php include("componentes/whatsapp_button.php"); ?>
  <?php include("componentes/scroll_button.php"); ?>

</body>

</html>