<!doctype html>
<html lang="en">

<head>
  <?php
  $pageTitle = 'Piccolo Burgers';
  $extraCss = [
    'https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css',
    'assets/css/index.css',
  ];
  include __DIR__ . '/partials/head.php';
  ?>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>

</head>

<body id="top">

  <?php
  $navBasePath = '';
  $navHomeLink = '#';
  include __DIR__ . '/partials/navbar.php';
  ?>


  <main>
    <?php if (!isset($_SESSION["cliente"])): ?>
      <div id="registro-burbuja" class="registro-burbuja">
        <button type="button" class="cerrar-burbuja" onclick="cerrarBurbuja()">×</button>

        <h5 class="fw-bold mb-2 text-gold">¡Registrate y ganá puntos!</h5>
        <p>
          Acumulá <strong class="text-gold">puntos exclusivos</strong> en cada compra y canjealos por <strong>descuentos irresistibles</strong>.
        </p>

      <a href="cliente/registro_cliente.php" class="btn btn-gold w-100 mt-2">Registrarse</a>
      </div>
    <?php endif; ?>


   <section id="inicio" class="container-fluid p-0">
    <div class="banner-img hero-banner-image">
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
        <div class="input-group">
          <input type="text" id="buscador-menu" class="form-control" placeholder="Buscar en el menú...">
          <button type="button" id="limpiar-filtro-menu" class="btn btn-outline-light btn-clear-filter" title="Limpiar búsqueda">
            Limpiar
          </button>
        </div>
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
    <div class="row align-items-center p-5 section-panel"
      data-aos="fade-up">

      <!-- Texto -->
      <div class="col-lg-6 mb-4 mb-lg-0 d-flex flex-column align-items-center align-items-lg-start">
        <h2 class="fw-bold display-6 mb-4 text-center w-100 section-heading">
          Nosotros
        </h2>
        <p class="lead text-justify section-description">
          En <strong class="text-gold">Piccolo Burgers</strong> somos apasionados por crear las hamburguesas más sabrosas y cargadas de sabor, usando ingredientes frescos y de calidad.
          <br><br>
          Nuestro compromiso es ofrecerte una <strong>experiencia gastronómica inolvidable</strong>, con un servicio cálido y un ambiente acogedor.
          <br><br>
          ¡Gracias por elegirnos para compartir <span class="text-gold">momentos deliciosos</span>!
        </p>
      </div>

      <!-- Imagen -->
      <div class="col-lg-6 text-center">
        <img src="img/SobreNosotros.jpg" alt="Nosotros - Piccolo Burgers" class="img-fluid rounded-3 shadow-lg section-image">
      </div>
    </div>
  </section>

  <section id="puntos" class="container mt-5">
    <div class="row align-items-center p-5 section-panel"
      data-aos="fade-up">

      <!-- Imagen -->
      <div class="col-lg-6 text-center mb-4 mb-lg-0">
        <img src="img/Puntos.jpg" alt="Sistema de Puntos - Piccolo Burgers" class="img-fluid rounded-3 shadow-lg section-image">
      </div>

      <!-- Texto -->
      <div class="col-lg-6 d-flex flex-column align-items-center align-items-lg-start">
        <h2 class="fw-bold display-6 mb-4 text-center w-100 section-heading">
          Sistema de Puntos
        </h2>
        <p class="lead text-justify section-description section-description-lg">
          Cada vez que hacés un pedido registrado,
          <strong class="text-gold">ganás puntos</strong>
          que podés canjear por
          <strong>descuentos exclusivos</strong> en tus próximas compras.
          <br><br>
          Cuanto más pedís,
          <strong class="text-gold">más ahorrás</strong> 🍔✨
        </p>
      </div>
    </div>
  </section>

  <section id="contacto" class="container mt-5">
    <div class="row p-5 align-items-center section-panel"
      data-aos="fade-up">

      <!-- Formulario -->
      <div class="col-lg-6 d-flex flex-column justify-content-between mb-4 mb-lg-0">
        <div>
          <h2 class="fw-bold display-6 mb-3 section-heading">
            Contacto
          </h2>
          <p class="lead mb-4 text-justify">
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
          class="img-fluid rounded-3 shadow-lg contact-image">
      </div>
    </div>
  </section>


  <section id="ubicacion" class="container mt-5">
    <div class="row align-items-center p-5 section-panel"
      data-aos="fade-up">

      <!-- Texto -->
      <div class="col-lg-6 d-flex flex-column align-items-center align-items-lg-start mb-4 mb-lg-0">
        <h2 class="fw-bold display-6 mb-4 text-center w-100 section-heading">
          Nuestra Ubicación
        </h2>
        <p class="lead text-justify section-description section-description-lg">
          Encontranos fácilmente en nuestro local en <strong class="text-gold">Villa del Rosario</strong>, Córdoba.
          <br><br>
          Estamos ubicados en <strong>25 de Mayo 1295</strong>, a pasos del centro. Acercate a disfrutar nuestras hamburguesas artesanales en un ambiente cálido y moderno 🍔✨
        </p>
      </div>

      <!-- Mapa -->
      <div class="col-lg-6 text-center">
        <div class="shadow-lg rounded-3 overflow-hidden map-container">
          <iframe class="map-frame"
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3400.107065015397!2d-63.53723899007664!3d-31.548676202549203!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94332a4ac325a7ad%3A0x91ff9ca646897a8f!2s25%20de%20Mayo%201295%2C%20X5963%20Villa%20del%20Rosario%2C%20C%C3%B3rdoba!5e0!3m2!1ses-419!2sar!4v1756841415057!5m2!1ses-419!2sar"
            width="100%" height="100%" allowfullscreen="" loading="lazy"
            referrerpolicy="no-referrer-when-downgrade">
          </iframe>
        </div>
      </div>
    </div>
  </section>


  <section id="testimonios" class="py-5 testimonios-section">
    <div class="container">
      <h2 class="text-center mb-4">Testimonios</h2>

      <div class="testimonios-carousel-container">
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
  </main>

  <!-- Script Testimonios -->
  <footer id="footer" class="mt-5 footer-gradient">
    <div class="container py-5">
      <div class="row text-center text-md-start">

        <!-- Info principal -->
        <div class="col-md-4 mb-4">
          <h4 class="fw-bold mb-3 text-gold">Piccolo Burgers VDR</h4>
          <p>🍔 100% cargadas de sabor</p>
          <p>📍 25 de Mayo 1295</p>
          <p>🍽️ Servicio a mesa y Take Away</p>
        </div>

        <!-- Horarios -->
        <div class="col-md-4 mb-4">
          <h4 class="fw-bold mb-3 text-gold">Horario de atención</h4>
          <p><strong>Martes a Domingo y feriados</strong></p>
          <p>⌚ 20:00 hs - 00:30 hs</p>
          <p><em>Lunes cerrado</em></p>
        </div>

        <!-- Contacto -->
        <div class="col-md-4 mb-4">
          <h4 class="fw-bold mb-3 text-gold">Contacto</h4>
          <div class="d-flex justify-content-center justify-content-md-start gap-3">
            <a href="https://wa.me/543573438947" target="_blank" class="social-link">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                <path d="M13.601 2.326A7.875 7.875 0 0 0 8.003.125a7.875 7.875 0 0 0-6.6 11.95L.125 15.875l3.8-1.25a7.875 7.875 0 0 0 4.078 1.125h.003a7.875 7.875 0 0 0 7.875-7.875 7.875 7.875 0 0 0-2.28-5.574zM8.003 14.25a6.25 6.25 0 0 1-3.2-.875l-.228-.137-2.25.75.75-2.25-.15-.237a6.25 6.25 0 1 1 5.078 2.75zm3.65-4.55c-.2-.1-1.175-.575-1.35-.637-.175-.062-.3-.1-.425.1-.125.2-.487.637-.6.762-.112.125-.225.137-.425.037-.2-.1-.85-.312-1.625-.987-.6-.537-1-1.2-1.125-1.4-.112-.2-.012-.3.088-.4.088-.087.2-.225.3-.337.1-.112.137-.2.2-.325.062-.125.025-.237-.012-.337-.037-.1-.425-1.025-.587-1.4-.15-.362-.3-.312-.425-.312h-.362c-.125 0-.325.037-.487.237-.162.2-.625.612-.625 1.487 0 .875.637 1.725.725 1.85.088.125 1.25 1.912 3.025 2.675.425.183.75.292 1.012.375.425.137.812.118 1.118.075.342-.05 1.175-.475 1.337-.937.162-.462.162-.85.112-.937-.05-.087-.175-.137-.375-.237z" />
              </svg>
            </a>
            <a href="https://www.facebook.com/profile.php?id=100087896013957" target="_blank" class="social-link">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8.94 8.5H10l.5-2H8.94V5.5c0-.58.12-.9.94-.9H10V2.6c-.16-.02-.72-.06-1.37-.06-1.37 0-2.31.83-2.31 2.36V6.5H5v2h1.26v5h2.68v-5z" />
              </svg>
            </a>
            <a href="https://www.instagram.com/piccoloburgers/" target="_blank" class="social-link">
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

  <?php include __DIR__ . '/../componentes/carrito_button.php'; ?>
  <?php include __DIR__ . '/../componentes/whatsapp_button.php'; ?>
  <?php include __DIR__ . '/../componentes/scroll_button.php'; ?>
  
  <script src="assets/js/carrito_reservas.js"></script>
  <script src="assets/js/index.js"></script>

</body>

</html>