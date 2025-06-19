<?php 
include("admin/bd.php");

$sentencia = $conexion->prepare("SELECT * FROM tbl_banners ORDER BY id DESC limit 1 ");
$sentencia->execute();
$lista_banners = $sentencia->fetchAll(PDO::FETCH_ASSOC);

$sentencia = $conexion->prepare("SELECT * FROM tbl_testimonios ORDER BY id DESC limit 2");
$sentencia->execute();
$lista_testimonios = $sentencia->fetchAll(PDO::FETCH_ASSOC);

$sentencia = $conexion->prepare("SELECT * FROM tbl_menu ORDER BY id DESC limit 4");
$sentencia->execute();
$lista_menu = $sentencia->fetchAll(PDO::FETCH_ASSOC);

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
  </style>
</head>
<body>

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
      </ul>
    </div>
  </div>
</nav>

<section class="container-fluid p-0">
  <div class="banner-img" style="position:relative; background:url('img/pexels-atomlaborblog-776314.webp') center/cover no-repeat; height:400px;">
    <div class="banner-text" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); text-align:center; color:#fff;">
      <?php foreach($lista_banners as $banner){ ?>
        <h1><?php echo $banner['titulo'];?></h1>
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
  <div class="row row-cols-1 row-cols-md-4 g-4">
    <?php foreach($lista_menu as $registro) { ?>
      <div class="col d-flex">
        <div class="card">
          <img src="images/menu/<?php echo $registro["foto"];?>" class="card-img-top">
          <div class="card-body">
            <h5 class="card-title"><?php echo $registro["nombre"];?></h5>
            <p class="card-text small"><strong><?php echo $registro["ingredientes"];?></strong></p>
            <p class="card-text"><strong>Precio:</strong> $<?php echo $registro["precio"];?></p>
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
    <p><strong>Lunes a Viernes</strong></p>
    <p><strong>11:00 a.m. - 10:00 p.m.</strong></p>
  </div>
  <div>
    <p><strong>Sábado</strong></p>
    <p><strong>12:00 a.m. - 5:00 p.m.</strong></p>
  </div>
  <div>
    <p><strong>Domingo</strong></p>
    <p><strong>7:00 a.m. - 2:00 p.m.</strong></p>
  </div>
</div>

<footer class="bg-dark text-light text-center py-3">
  <p>&copy; 2025 Piccolo Burgers — Developed by: <strong>Jazmin Abigail Gaido - Mariano Jesús Ceballos - Juan Pablo Medina</strong></p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>

</body>
</html>