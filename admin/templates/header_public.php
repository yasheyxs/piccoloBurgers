<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Piccolo Burgers</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Estilos base -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" href="../../public/img/favicon.png" type="image/x-icon" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">

  <style>
    body {
      background-color: #f8f9fa;
      color: #212529;
      font-family: 'Inter', sans-serif;
    }

    .navbar {
      background-color: #ffffff;
      border-bottom: 1px solid #ddd;
    }

    .navbar-brand {
      color: #212529 !important;
      font-weight: bold;
      font-size: 1.4rem;
    }

    .nav-link {
      color: #212529 !important;
      font-weight: 500;
    }

    .nav-link:hover {
      color: #0056b3 !important;
    }

    .form-control,
    .form-select {
      background-color: #ffffff;
      color: #212529;
      border: 1px solid #ced4da;
      border-radius: 4px;
    }

    .form-control:focus,
    .form-select:focus {
      border-color: #80bdff;
      box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }
  </style>
</head>

<body>
  <nav class="navbar navbar-light mb-4">
    <div class="container d-flex justify-content-between align-items-center">
      <a class="navbar-brand" href="../../public/index.php">
        <i class="fas fa-utensils me-2"></i> Piccolo Burgers
      </a>
    </div>
  </nav>
