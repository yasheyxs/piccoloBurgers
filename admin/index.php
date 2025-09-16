<?php
include("templates/header.php");

$rolActual = $rol ?? 'admin';
$etiquetasRol = [
  'admin' => 'Administrador',
  'empleado' => 'Empleado',
  'delivery' => 'Delivery',
];
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
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(240, 240, 240, 0.7));
    border-radius: 20px;
    padding: 3rem;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
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
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 1rem;
  }

  .welcome-box p {
    font-size: 1.1rem;
    line-height: 1.6;
    color: #444;
    margin-bottom: 0.75rem;
  }

  .welcome-box .text-muted {
    font-size: 0.95rem;
    color: #666 !important;
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
  <div class="row g-4 justify-content-center">

    <div class="col-md-3 col-lg-2">
      <button class="quick-action btn-add w-100" type="button">
        <span>➕</span>
        Agregar producto
      </button>
    </div>

    <div class="col-md-3 col-lg-2">
      <button class="quick-action btn-provider w-100" type="button"> <!-- 🔹 corregido -->
        <span>📋</span>
        Crear proveedor
      </button>
    </div>

    <div class="col-md-3 col-lg-2">
      <button class="quick-action btn-user w-100" type="button"> <!-- 🔹 corregido -->
        <span>👤</span>
        Invitar usuario
      </button>
    </div>

    <div class="col-md-3 col-lg-2">
      <button class="quick-action btn-report w-100" type="button"> <!-- 🔹 corregido -->
        <span>📑</span>
        Generar PDF
      </button>
    </div>

    <div class="col-md-3 col-lg-2">
      <button class="quick-action btn-roles w-100" type="button"> <!-- 🔹 único btn-roles -->
        <span>🔑</span>
        Acceso a roles
      </button>
    </div>


  </div>
</div>

<div class="container">
  <div class="row justify-content-center mt-3">
    <div class="col-md-6 col-lg-4">
      <div id="rolesPanel" class="card shadow-sm d-none" aria-hidden="true">
        <div class="card-body">
          <h5 class="card-title text-center mb-3">Cambiar vista rápida</h5>
          <form id="rolForm" class="d-grid gap-2">
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