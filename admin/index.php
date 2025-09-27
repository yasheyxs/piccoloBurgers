<?php
include("templates/header.php");

$rolActual = $rol ?? 'admin';
$rolSesion = $_SESSION['rol'] ?? null;

if (!isset($_SESSION['rol_autenticado']) && $rolSesion !== null) {
  $_SESSION['rol_autenticado'] = $rolSesion;
}

$rolAutenticado = $_SESSION['rol_autenticado'] ?? $rolSesion;
$esAdminAutenticado = $rolAutenticado === 'admin';

$etiquetasRol = [
  'admin' => 'Administrador',
  'empleado' => 'Empleado',
  'delivery' => 'Delivery',
];

$metricasVentas = [
  'total_ventas' => 0,
  'total_pedidos' => 0,
  'producto_estrella' => [
    'nombre' => 'N/A',
    'cantidad' => 0,
  ],
  'error' => null,
];

if ($esAdminAutenticado) {
  $variablesRequeridas = ['MYSQL_HOST', 'MYSQL_DATABASE', 'MYSQL_USER', 'MYSQL_PASSWORD'];
  $configuracionDisponible = array_reduce($variablesRequeridas, function ($estado, $variable) {
    if (!$estado) {
      return false;
    }

    $valor = getenv($variable);
    return $valor !== false && $valor !== '';
  }, true);

  if ($configuracionDisponible) {
    try {
      require_once __DIR__ . '/bd.php';

      $fechaInicio = date('Y-m-01');
      $fechaFin = date('Y-m-d 23:59:59');

      $stmt = $conexion->prepare("SELECT SUM(pd.precio * pd.cantidad) AS total_ventas
          FROM tbl_pedidos_detalle pd
          JOIN tbl_pedidos p ON pd.pedido_id = p.ID
          WHERE p.fecha BETWEEN :inicio AND :fin");
      $stmt->bindParam(':inicio', $fechaInicio);
      $stmt->bindParam(':fin', $fechaFin);
      $stmt->execute();
      $metricasVentas['total_ventas'] = (float)($stmt->fetchColumn() ?? 0);

      $stmt = $conexion->prepare("SELECT COUNT(*) AS total_pedidos
          FROM tbl_pedidos p
          WHERE p.fecha BETWEEN :inicio AND :fin");
      $stmt->bindParam(':inicio', $fechaInicio);
      $stmt->bindParam(':fin', $fechaFin);
      $stmt->execute();
      $metricasVentas['total_pedidos'] = (int)($stmt->fetchColumn() ?? 0);

      $stmt = $conexion->prepare("SELECT pd.nombre, SUM(pd.cantidad) AS total_vendido
        FROM tbl_pedidos_detalle pd
        JOIN tbl_pedidos p ON pd.pedido_id = p.ID
        WHERE p.fecha BETWEEN :inicio AND :fin
        GROUP BY pd.nombre
        ORDER BY total_vendido DESC
        LIMIT 1");
      $stmt->bindParam(':inicio', $fechaInicio);
      $stmt->bindParam(':fin', $fechaFin);
      $stmt->execute();
      $productoEstrella = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($productoEstrella) {
        $metricasVentas['producto_estrella'] = [
          'nombre' => $productoEstrella['nombre'] ?? 'N/A',
          'cantidad' => (int)($productoEstrella['total_vendido'] ?? 0),
        ];
      }
    } catch (PDOException $exception) {
      $metricasVentas['error'] = 'No se pudieron cargar las métricas de ventas.';
      error_log('Error en métricas del panel administrativo: ' . $exception->getMessage());
    }
  } else {
    $metricasVentas['error'] = 'Configuración de base de datos no disponible para calcular métricas.';
  }
}

?>

<style>
  .quick-action {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 120px;
    border-radius: 12px;
    color: #fff;
    font-weight: bold;
    font-size: 1rem;
    text-align: center;
    border: none;
    transition: all 0.3s ease;
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
  }

  .quick-action span {
    font-size: 2rem;
    margin-bottom: 0.5rem;
  }

  /* Efecto hover */
  .quick-action:hover {
    transform: translateY(-5px) scale(1.05);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
    filter: brightness(1.1);
  }

  /* Colores  */
  .btn-add {
    background: linear-gradient(135deg, #ff6a00, #ee0979);
  }

  .btn-provider {
    background: linear-gradient(135deg, #6a11cb, #2575fc);
  }

  .btn-user {
    background: linear-gradient(135deg, #00b09b, #96c93d);
  }

  .btn-report {
    background: linear-gradient(135deg, #f7971e, #ffd200);
    color: #222;
  }

  .btn-roles {
    background: linear-gradient(135deg, #ff416c, #ff4b2b);
  }

  .welcome-box {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.92), rgba(235, 239, 244, 0.76));
    border-radius: 20px;
    padding: 3rem;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
    text-align: center;
    margin-bottom: 2rem;
  }

  .welcome-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
  }

  .welcome-box h2 {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(90deg, #ff6a00, #ee0979);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 1rem;
  }

  .welcome-box p {
    font-size: 1.1rem;
    line-height: 1.6;
    color: #3a3f45;
    margin-bottom: 0.75rem;
    transition: color 0.3s ease;
  }

  .welcome-box .text-muted {
    font-size: 0.95rem;
    color: #5b6169 !important;
    transition: color 0.3s ease;
  }

  body.admin-dark .welcome-box {
    background: linear-gradient(135deg, rgba(30, 34, 44, 0.95), rgba(18, 21, 28, 0.92));
    box-shadow: 0 12px 35px rgba(4, 6, 10, 0.55);
  }

  body.admin-dark .welcome-box p {
    color: #d5dae3;
  }

  body.admin-dark .welcome-box .text-muted {
    color: #a5adba !important;
  }

  .metrics-section .card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
  }

  .metrics-section .card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 35px rgba(0, 0, 0, 0.15);
  }

  .metrics-section .metric-icon {
    font-size: 2rem;
    margin-bottom: 0.75rem;
  }

</style>

<br />
<div class="row align-items-md-stretch">
  <div class="col-md-12">
    <div class="welcome-box">
      <h2>Bienvenidx, administrador <?php echo $_SESSION["admin_usuario"]; ?> 👋</h2>
      <p>Este es tu centro de control: desde acá podés editar el menú, revisar comentarios, administrar usuarios, controlar ventas y compras, y supervisar los paneles de cocina y delivery.</p>
      <p class="text-muted">Usá el menú superior para explorar tus herramientas. Cada cambio impacta directamente en la experiencia de tus clientes, ¡así que hacelo brillar ✨!</p>
    </div>
  </div>
</div>


<!-- 🔹 Sección de Acciones Rápidas -->
<div class="container my-5">
  <h3 class="mb-4 text-center">⚡ Acciones rápidas</h3>
  <?php if ($esAdminAutenticado) { ?>
    <div class="row g-4 justify-content-center">

      <div class="col-md-3 col-lg-2">
        <button class="quick-action btn-add w-100" type="button">
          <span>➕</span>
          Agregar producto
        </button>
      </div>

      <div class="col-md-3 col-lg-2">
        <button class="quick-action btn-provider w-100" type="button">
          <span>📋</span>
          Crear proveedor
        </button>
      </div>

      <div class="col-md-3 col-lg-2">
        <button class="quick-action btn-user w-100" type="button">
          <span>👤</span>
          Invitar usuario
        </button>
      </div>
      <div class="col-md-3 col-lg-2">
        <button class="quick-action btn-report w-100" type="button">
          <span>📑</span>
          Generar PDF
        </button>
      </div>


      <div class="col-md-3 col-lg-2">
        <button class="quick-action btn-roles w-100" type="button">
          <span>🔑</span>
          Acceso a roles
        </button>
      </div>



    </div>
  <?php } else { ?>
    <div class="alert alert-warning text-center" role="alert">
      Solo los administradores pueden acceder a estas acciones rápidas.
    </div>
  <?php } ?>
</div>

<?php if ($esAdminAutenticado) { ?>
  <div class="container">
    <div class="row justify-content-center mt-3">
      <div class="col-md-6 col-lg-4">
        <div id="rolesPanel" class="card shadow-sm d-none" aria-hidden="true">
          <div class="card-body">
            <h5 class="card-title text-center mb-3">Cambiar vista rápida</h5>
            <form id="rolForm" class="d-grid gap-2">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="rol" id="rolAdmin" value="admin" <?= $rolActual === 'admin' ? 'checked' : '' ?>>
                <label class="form-check-label" for="rolAdmin">Vista administrador</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="rol" id="rolEmpleado" value="empleado" <?= $rolActual === 'empleado' ? 'checked' : '' ?>>
                <label class="form-check-label" for="rolEmpleado">Vista empleado</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="rol" id="rolDelivery" value="delivery" <?= $rolActual === 'delivery' ? 'checked' : '' ?>>
                <label class="form-check-label" for="rolDelivery">Vista delivery</label>
              </div>
            </form>
            <div id="rolMensaje" class="alert mt-3 d-none" role="alert"></div>
            <p class="text-muted small text-center mb-0">Vista actual: <strong><?= htmlspecialchars($etiquetasRol[$rolActual] ?? ucfirst($rolActual)) ?></strong></p>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php } ?>

<br><br>

<?php if ($esAdminAutenticado) { ?>

  <div class="container metrics-section mb-5">
    <h3 class="mb-4 text-center">
      <i class="fa-solid fa-chart-column me-2" aria-hidden="true"></i>
      Resumen de ventas (mes en curso)
    </h3>

    <?php if ($metricasVentas['error']) { ?>
      <div class="alert alert-warning text-center" role="alert">
        <?= htmlspecialchars($metricasVentas['error']); ?>
      </div>
    <?php } ?>

    <div class="row g-4 justify-content-center">
      <div class="col-md-4 col-sm-6">
        <div class="card h-100 text-center p-4">
          <div class="card-body">
            <div class="metric-icon text-success">
              <i class="fa-solid fa-sack-dollar" aria-hidden="true"></i>
            </div>
            <h5 class="card-title">Total de ventas</h5>
            <p class="display-6 fw-bold text-success mb-0">
              $<?= number_format($metricasVentas['total_ventas'], 2); ?>
            </p>
          </div>
        </div>
      </div>

      <div class="col-md-4 col-sm-6">
        <div class="card h-100 text-center p-4">
          <div class="card-body">
            <div class="metric-icon text-info">
              <i class="fa-solid fa-receipt" aria-hidden="true"></i>
            </div>
            <h5 class="card-title">Pedidos totales</h5>
            <p class="display-6 fw-bold text-info mb-0">
              <?= number_format($metricasVentas['total_pedidos']); ?>
            </p>
          </div>
        </div>
      </div>

      <div class="col-md-4 col-sm-8">
        <div class="card h-100 text-center p-4">
          <div class="card-body">
            <div class="metric-icon text-warning">
              <i class="fa-solid fa-star" aria-hidden="true"></i>
            </div>
            <h5 class="card-title">Producto estrella</h5>
            <p class="h4 fw-bold mb-1">
              <?= htmlspecialchars($metricasVentas['producto_estrella']['nombre']); ?>
            </p>
            <p class="text-muted mb-0">
              Cantidad vendida: <?= number_format($metricasVentas['producto_estrella']['cantidad']); ?>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php } ?>


<?php if ($esAdminAutenticado) { ?>
  <div class="container text-center my-5">
    <a href="<?= $url_base; ?>seccion/ventas/" class="btn btn-outline-primary btn-lg px-4">
      Ir al panel de ventas
    </a>
  </div>
<?php } ?>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const baseUrl = <?= json_encode($url_base) ?>;

    const navigationActions = [{
        selector: '.btn-add',
        path: 'seccion/menu/crear.php'
      },
      {
        selector: '.btn-provider',
        path: 'seccion/proveedores/crear.php'
      },
      {
        selector: '.btn-user',
        path: 'seccion/usuarios/crear.php'
      }
    ];

    navigationActions.forEach(({
      selector,
      path
    }) => {
      const button = document.querySelector(selector);
      if (button) {
        button.addEventListener('click', () => {
          window.location.href = baseUrl + path;
        });
      }
    });

    const pdfButton = document.querySelector('.btn-report');
    if (pdfButton) {
      pdfButton.addEventListener('click', () => {
        window.open(baseUrl + 'seccion/ventas/export_pdf.php', '_blank');
      });
    }

    const rolesButton = document.querySelector('.btn-roles');
    const rolesPanel = document.getElementById('rolesPanel');
    if (rolesButton && rolesPanel) {
      rolesButton.addEventListener('click', () => {
        rolesPanel.classList.toggle('d-none');
        const isHidden = rolesPanel.classList.contains('d-none');
        rolesPanel.setAttribute('aria-hidden', isHidden ? 'true' : 'false');
        rolesButton.setAttribute('aria-expanded', isHidden ? 'false' : 'true');

        if (!isHidden) {
          rolesPanel.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
          });
        }
      });
    }

    const rolForm = document.getElementById('rolForm');
    const rolMensaje = document.getElementById('rolMensaje');

    if (rolForm && rolMensaje) {
      rolForm.addEventListener('change', async (event) => {
        if (event.target.name !== 'rol') {
          return;
        }

        rolMensaje.classList.add('d-none');
        rolMensaje.classList.remove('alert-success', 'alert-danger');

        const formData = new FormData(rolForm);

        try {
          const response = await fetch(baseUrl + 'cambiar_rol.php', {
            method: 'POST',
            body: formData,
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
          });

          const data = await response.json();

          if (!response.ok || !data.exito) {
            throw new Error(data.mensaje || 'No se pudo actualizar la vista.');
          }

          const vistaNombre = data.vista || data.rol;
          rolMensaje.textContent = `Vista ${vistaNombre} activada.`;
          rolMensaje.classList.add('alert-success');
          rolMensaje.classList.remove('d-none');

          setTimeout(() => {
            window.location.reload();
          }, 800);
        } catch (error) {
          rolMensaje.textContent = error.message;
          rolMensaje.classList.add('alert-danger');
          rolMensaje.classList.remove('d-none');
        }
      });
    }
  });
</script>


<?php include("templates/footer.php"); ?>