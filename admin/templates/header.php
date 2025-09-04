<?php
session_start();
include_once(dirname(__DIR__, 2) . "/config.php");

$host = $_SERVER['HTTP_HOST'];
$url_base = "http://$host/piccoloBurgers/admin/";


if (MODO_DESARROLLO) {// Modo desarrollo, no se requiere autenticación
  $_SESSION["admin_usuario"] = USUARIO_DESARROLLO;
  $_SESSION["admin_logueado"] = true;
} else {// Modo producción, verificar autenticación
  if (!isset($_SESSION["admin_logueado"])) {
    header("Location: login.php");
  }
}
?>

<!doctype html>
<html lang="en">

<head>
  <title>Administrador del sitio web </title>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Bootstrap CSS v5.2.1 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">


  <script src="https://code.jquery.com/jquery-3.6.3.min.js"></script>

  <link rel="icon" type="image/png" href="<?php echo $url_base; ?>../img/favicon.png" />


  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.2/css/jquery.dataTables.min.css">
  <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
</head>

<body>
  <header>
    <!-- place navbar here -->

    <nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="<?php echo $url_base; ?>index.php">Administrador</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin" aria-controls="navbarAdmin" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarAdmin">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?php echo $url_base; ?>seccion/banners/">Banners</a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo $url_base; ?>seccion/testimonios/">Testimonios</a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo $url_base; ?>seccion/menu/">Menú</a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo $url_base; ?>seccion/materiasPrimas/">Materias Primas</a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo $url_base; ?>seccion/comentarios/">Comentarios</a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo $url_base; ?>seccion/proveedores/">Proveedores</a></li>

        <!-- Dropdown Gestión de personas -->
<li class="nav-item dropdown">
  <a class="nav-link dropdown-toggle" href="#" id="personasDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
    Gestión de personas
  </a>
  <ul class="dropdown-menu" aria-labelledby="personasDropdown">
    <li><a class="dropdown-item" href="<?php echo $url_base; ?>clientes.php">Clientes</a></li>
    <li><a class="dropdown-item" href="<?php echo $url_base; ?>seccion/usuarios/">Usuarios</a></li>
  </ul>
</li>

        <!-- Dropdown Transacciones -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="transaccionesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Transacciones
          </a>
          <ul class="dropdown-menu" aria-labelledby="transaccionesDropdown">
            <li><a class="dropdown-item" href="<?php echo $url_base; ?>seccion/ventas/">Ventas</a></li>
            <li><a class="dropdown-item" href="<?php echo $url_base; ?>seccion/compras/">Compras</a></li>
          </ul>
        </li>

        <!-- Dropdown Paneles -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="panelDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Paneles
          </a>
          <ul class="dropdown-menu" aria-labelledby="panelDropdown">
            <li><a class="dropdown-item" href="<?php echo $url_base; ?>panel_cocina.php">Panel de cocina</a></li>
            <li><a class="dropdown-item" href="<?php echo $url_base; ?>panel_delivery.php">Panel de delivery</a></li>
          </ul>
        </li>
      </ul>

      <!-- Ícono cerrar sesión -->
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link text-danger" href="<?php echo $url_base; ?>cerrar.php" title="Cerrar sesión">
            <i class="fa-solid fa-right-from-bracket fa-lg"></i>
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>


  </header>
  <main>
    <section class="container">