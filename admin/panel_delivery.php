<?php
include("../admin/bd.php");
include("../admin/templates/header.php");

$sentencia = $conexion->prepare("SELECT * FROM tbl_pedidos WHERE estado = 'En camino' ORDER BY fecha DESC");
$sentencia->execute();
$pedidos = $sentencia->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap');
  @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;600&display=swap');

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
    color: var(--text-light);
    background: url('../public/img/HamLoginCliente.jpg') no-repeat center center fixed;
    background-size: cover;
    background-attachment: fixed;
  }

  main {
    padding-top: 2rem;
    padding-bottom: 3rem;
  }

  h3.page-title {
    font-family: var(--font-title);
    font-size: 2.4rem;
    letter-spacing: 1px;
    text-transform: uppercase;
    text-align: center;
    color: var(--text-light);
    text-shadow: 0 0 12px rgba(0, 0, 0, 0.6);
  }

  .glass-card {
    background: rgba(44, 44, 44, 0.7);
    border-radius: 20px;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-left: 5px solid var(--main-gold);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }

  .glass-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.45);
  }

  .glass-card h5 {
    font-family: var(--font-title);
    font-size: 1.9rem;
    color: var(--text-light);
    letter-spacing: 0.5px;
    margin-bottom: 1rem;
    text-shadow: 0 0 8px rgba(0, 0, 0, 0.7);
  }

  .glass-card p,
  .glass-card li,
  .glass-card span,
  .glass-card strong {
    color: var(--text-light);
    font-size: 1rem;
  }

  .glass-card ul {
    padding-left: 1.2rem;
    margin-bottom: 1rem;
  }

  .glass-card i {
    color: var(--main-gold);
  }

  .contact-link {
    color: var(--main-gold);
    font-weight: 600;
    text-decoration: none;
  }

  .contact-link:hover {
    color: var(--gold-hover);
    text-decoration: underline;
  }

  .btn-gold {
    background-color: var(--main-gold);
    color: #000;
    font-weight: bold;
    border: none;
    border-radius: 30px;
    padding: 0.7rem 1.5rem;
    font-size: 1rem;
    transition: all 0.3s ease;
  }

  .btn-gold:hover {
    background-color: var(--gold-hover);
    transform: scale(1.05);
  }

  .text-muted {
    color: var(--text-muted) !important;
  }

  .alert-info {
    background: rgba(44, 44, 44, 0.75);
    border: 1px solid var(--main-gold);
    color: var(--text-light);
    border-radius: 16px;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
  }
</style>

<div class="container mt-4">
  <h3 class="page-title mb-4">Pedidos a entregar</h3>

  <?php if (count($pedidos) === 0): ?>
    <div class="alert alert-info">No hay pedidos para entregar por el momento.</div>
  <?php else: ?>
    <?php $localidadPredeterminada = 'Villa del Rosario, Córdoba, Argentina'; ?>
    <?php foreach ($pedidos as $pedido): ?>
      <?php
      $stmt_detalle = $conexion->prepare("SELECT nombre, cantidad FROM tbl_pedidos_detalle WHERE pedido_id = ?");
      $stmt_detalle->execute([$pedido['ID']]);
      $productos = $stmt_detalle->fetchAll(PDO::FETCH_ASSOC);

      $telefono = trim((string)($pedido['telefono'] ?? ''));
      $telefonoEnlace = preg_replace('/[^0-9+]/', '', $telefono);

      $direccionBase = trim((string)($pedido['direccion'] ?? ''));
      $direccionCompleta = $direccionBase !== ''
        ? $direccionBase . ', ' . $localidadPredeterminada
        : '';
      $mapUrl = $direccionCompleta !== ''
        ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($direccionCompleta)
        : '';

      $metodoPago = trim((string)($pedido['metodo_pago'] ?? ''));
      $referencias = trim((string)($pedido['referencias'] ?? ''));
      ?>
      <div class="glass-card" data-pedido-id="<?= $pedido['ID'] ?>">
        <h5>#<?= $pedido['ID'] ?> - <?= htmlspecialchars($pedido['nombre']) ?></h5>
        <p class="mb-2">
          <i class="fas fa-phone me-2"></i>
          <?php if ($telefono !== '' && $telefonoEnlace !== ''): ?>
            <a class="contact-link" href="tel:<?= htmlspecialchars($telefonoEnlace) ?>">

              <?= htmlspecialchars($telefono) ?>
            </a>
          <?php else: ?>
            <span class="text-muted">Sin teléfono</span>
          <?php endif; ?>
        </p>
        <p class="mb-2">
          <i class="fas fa-map-marker-alt me-2"></i>
          <?php if ($mapUrl !== ''): ?>
            <a class="contact-link" href="<?= htmlspecialchars($mapUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"> <?= htmlspecialchars($direccionBase) ?>
            </a>
          <?php else: ?>
            <span class="text-muted">Sin dirección</span>
          <?php endif; ?>
        </p>
        <p class="mb-2">
          <i class="fas fa-credit-card me-2"></i>
          <?php if ($metodoPago !== ''): ?>
            <?= htmlspecialchars($metodoPago) ?>
          <?php else: ?>
            <span class="text-muted">Sin método de pago</span>
          <?php endif; ?>
        </p>
        <p class="fw-semibold mb-2">🛍️ Productos:</p>
        <ul class="mb-3">
          <?php foreach ($productos as $producto): ?>
            <li><?= htmlspecialchars($producto['cantidad']) ?> x <?= htmlspecialchars($producto['nombre']) ?></li>
          <?php endforeach; ?>
        </ul>
        <p class="mb-0"><strong>📌 Referencias:</strong> <?= htmlspecialchars($referencias) ?: 'Sin referencias' ?></p>
        <div class="mt-4">
          <button class="btn btn-gold w-100" data-id="<?= $pedido['ID'] ?>" data-action="entregado">Entregado</button>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
  async function marcarEntregado(pedidoId, boton) {

    const textoOriginal = boton.textContent;
    boton.disabled = true;
    boton.textContent = 'Actualizando...';

    let pedidoActualizado = false;

    try {
      const formData = new FormData();
      formData.append('pedido_id', pedidoId);
      formData.append('nuevo_estado', 'Listo');

      const response = await fetch('../admin/actualizar_estado.php', {
        method: 'POST',
        body: formData
      });

      if (!response.ok) {
        throw new Error('Error al actualizar el estado.');
      }

      let resultado;
      try {
        resultado = await response.json();
      } catch (errorJson) {
        console.error('Error al analizar la respuesta JSON:', errorJson);
        throw new Error('La respuesta del servidor no es válida.');
      }

      if (resultado && resultado.success) {
        pedidoActualizado = true;
        boton.closest('.glass-card').remove();
      } else {
        const mensaje = resultado && resultado.message ?
          resultado.message :
          'La respuesta del servidor no es válida.';
        throw new Error(mensaje);
      }
    } catch (error) {
      const mensajeError = error instanceof Error && error.message && error.message !== 'Failed to fetch' ?
        error.message :
        'Error al conectar con el servidor.';
      alert(mensajeError);
      console.error(error);
    } finally {
      if (!pedidoActualizado) {
        boton.disabled = false;
        boton.textContent = textoOriginal;
      }
    }
  }

  document.querySelectorAll('[data-action="entregado"]').forEach(btn => {
    btn.addEventListener('click', () => {
      const pedidoId = btn.getAttribute('data-id');
      marcarEntregado(pedidoId, btn);
    });
  });

  setInterval(() => location.reload(), 10000);
</script>

<?php include("../admin/templates/footer.php"); ?>