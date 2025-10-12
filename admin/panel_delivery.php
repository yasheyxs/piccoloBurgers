<?php
include("../admin/bd.php");
require_once __DIR__ . '/../includes/estado_pago_helpers.php';
$adminPageIdentifier = 'delivery-panel';
include("../admin/templates/header.php");

try {
  $sentencia = $conexion->prepare("SELECT * FROM tbl_pedidos WHERE estado = 'En camino' ORDER BY fecha DESC");
  $sentencia->execute();
  $pedidos = $sentencia->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $pedidos = [];
}

$valorPagoPositivo = piccolo_resolver_valor_pago($conexion, 'Si') ?? 'Si';
$valorPagoNegativo = piccolo_resolver_valor_pago($conexion, 'No') ?? 'No';
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

  html,
  body {
    height: 100%;
    margin: 0;
    padding: 0;
  }

  body {
    font-family: var(--font-main);
    color: var(--text-light);
    background: url("/img/HamLoginCliente.jpg") no-repeat center center fixed;
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

  <?php $localidadPredeterminada = 'Villa del Rosario, Córdoba, Argentina'; ?>

  <div id="alerta-sin-pedidos" class="alert alert-info<?= count($pedidos) === 0 ? '' : ' d-none' ?>">
    No hay pedidos para entregar por el momento.
  </div>

  <div id="lista-pedidos">
    <?php if (count($pedidos) > 0): ?>
      <?php foreach ($pedidos as $pedido): ?>
        <?php
        $stmt_detalle = $conexion->prepare("SELECT nombre, cantidad FROM tbl_pedidos_detalle WHERE pedido_id = ?");
        $stmt_detalle->execute([$pedido['ID']]);
        $productos = $stmt_detalle->fetchAll(PDO::FETCH_ASSOC);

        $telefono = trim((string)($pedido['telefono'] ?? ''));
        $telefonoNormalizado = $telefono !== '' ? '+' . ltrim($telefono, '+') : '';
        $telefonoEnlace = preg_replace('/[^0-9+]/', '', $telefono);
        if ($telefonoEnlace !== '') {
          $telefonoEnlace = '+' . ltrim($telefonoEnlace, '+');
        }

        $direccionBase = trim((string)($pedido['direccion'] ?? ''));
        $direccionCompleta = $direccionBase !== ''
          ? $direccionBase . ', ' . $localidadPredeterminada
          : '';
        $mapUrl = $direccionCompleta !== ''
          ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($direccionCompleta)
          : '';

        $metodoPago = trim((string)($pedido['metodo_pago'] ?? ''));
        $referencias = trim((string)($pedido['referencias'] ?? ''));
        $interpretacionPago = piccolo_interpretar_estado_pago_para_presentacion(
          $pedido['esta_pago'] ?? '',
          $valorPagoPositivo,
          $valorPagoNegativo
        );
        $estadoPago = $interpretacionPago['valor'];
        $estadoPagoTexto = $interpretacionPago['texto'];
        $estadoPagoClase = $interpretacionPago['clase'];
        $totalPedido = number_format((float)($pedido['total'] ?? 0), 2, ',', '.');
        ?>
        <div class="glass-card" data-pedido-id="<?= $pedido['ID'] ?>" data-esta-pago="<?= htmlspecialchars($estadoPago, ENT_QUOTES, 'UTF-8') ?>">
          <h5>#<?= $pedido['ID'] ?> - <?= htmlspecialchars($pedido['nombre']) ?></h5>
          <p class="mb-2">
            <i class="fas fa-phone me-2"></i>
            <?php if ($telefonoNormalizado !== '' && $telefonoEnlace !== ''): ?>
              <a class="contact-link" href="tel:<?= htmlspecialchars($telefonoEnlace) ?>">
                <?= htmlspecialchars($telefonoNormalizado) ?>

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
          <p class="mb-2">
            <i class="fas fa-money-bill-wave me-2"></i>
            Total: $<?= $totalPedido ?>
          </p>
          <p class="mb-2">
            <i class="fas fa-receipt me-2"></i>
            ¿Está pago?: <span class="<?= $estadoPagoClase ?>" data-estado-pago><?= $estadoPagoTexto ?></span>
          </p>
          <p class="fw-semibold mb-2">🛍️ Productos:</p>
          <ul class="mb-3">
            <?php if (count($productos) > 0): ?>
              <?php foreach ($productos as $producto): ?>
                <li><?= htmlspecialchars($producto['cantidad']) ?> x <?= htmlspecialchars($producto['nombre']) ?></li>
              <?php endforeach; ?>
            <?php else: ?>
              <li class="text-muted">Sin productos</li>
            <?php endif; ?>
          </ul>
          <p class="mb-0"><strong>📌 Referencias:</strong> <?= htmlspecialchars($referencias) ?: 'Sin referencias' ?></p>
          <div class="mt-4">
            <button class="btn btn-gold w-100" data-id="<?= $pedido['ID'] ?>" data-action="entregado">Entregado</button>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<script>
  const pedidosContainer = document.getElementById('lista-pedidos');
  const alertaSinPedidos = document.getElementById('alerta-sin-pedidos');
  const LOCALIDAD_PREDETERMINADA = 'Villa del Rosario, Córdoba, Argentina';
  const formateadorPesos = new Intl.NumberFormat('es-AR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
  let sincronizacionEnCurso = false;

  function actualizarVisibilidadMensaje() {
    if (!alertaSinPedidos || !pedidosContainer) {
      return;
    }

    if (pedidosContainer.children.length === 0) {
      alertaSinPedidos.classList.remove('d-none');
    } else {
      alertaSinPedidos.classList.add('d-none');
    }
  }

  function normalizarEstadoPago(valor) {
    if (valor === null || valor === undefined) {
      return 'No';
    }

    let texto = valor.toString().trim().toLowerCase();

    try {
      texto = texto.normalize('NFD');
    } catch (error) {
      // Algunos navegadores pueden no soportar normalize.
    }

    texto = texto.replace(/[\u0300-\u036f]/g, '');
    texto = texto.replace(/[\s'"`’]/g, '');

    const valoresPositivos = new Set(['si', 'sí', 'yes', 'pagado', 'pago', 'abonado', '1', 'true']);
    const valoresNegativos = new Set(['no', '0', '', 'false', 'pendiente']);

    if (valoresPositivos.has(texto)) {
      return 'Si';
    }

    if (valoresNegativos.has(texto)) {
      return 'No';
    }

    return 'No';
  }

  function obtenerTextoEstadoPago(estado) {
    return estado === 'Si' ? 'Sí ✅' : 'No ❌';
  }

  function obtenerClaseEstadoPago(estado) {
    return estado === 'Si' ? 'text-success fw-semibold' : 'text-warning fw-semibold';
  }

  function escapeHtml(texto) {
    if (texto === null || texto === undefined) {
      return '';
    }

    return texto.toString().replace(/[&<>"']/g, (caracter) => {
      switch (caracter) {
        case '&':
          return '&amp;';
        case '<':
          return '&lt;';
        case '>':
          return '&gt;';
        case '"':
          return '&quot;';
        default:
          return '&#039;';
      }
    });
  }

  function formatearTotal(total) {
    const numero = Number(total);
    const totalValido = Number.isFinite(numero) ? numero : 0;
    return formateadorPesos.format(totalValido);
  }

  function normalizarTelefonoParaMostrar(telefono) {
    if (telefono === null || telefono === undefined) {
      return '';
    }

    const texto = String(telefono).trim();
    if (texto === '') {
      return '';
    }

    const sinPrefijo = texto.replace(/^\++/, '');
    return `+${sinPrefijo}`;
  }

  function obtenerTelefonoEnlace(telefono) {
    if (telefono === null || telefono === undefined) {
      return '';
    }

    const numeros = String(telefono).trim().replace(/[^0-9+]/g, '');
    if (numeros === '') {
      return '';
    }

    const sinPrefijo = numeros.replace(/^\++/, '');
    return `+${sinPrefijo}`;
  }

  function crearTarjetaPedido(pedido) {
    const tarjeta = document.createElement('div');
    tarjeta.className = 'glass-card';

    const pedidoId = pedido && pedido.ID !== undefined ? String(pedido.ID) : '';
    if (pedidoId === '') {
      return null;
    }

    tarjeta.dataset.pedidoId = pedidoId;

    const estadoPago = normalizarEstadoPago(pedido.esta_pago);
    tarjeta.dataset.estaPago = estadoPago;

    const telefono = pedido && pedido.telefono != null ? String(pedido.telefono).trim() : '';
    const telefonoNormalizado = normalizarTelefonoParaMostrar(telefono);
    const telefonoEnlace = obtenerTelefonoEnlace(telefono);

    const direccionBase = pedido && pedido.direccion != null ? String(pedido.direccion).trim() : '';
    const mapaQuery = direccionBase !== '' ? `${direccionBase}, ${LOCALIDAD_PREDETERMINADA}` : '';
    const mapUrl = mapaQuery !== '' ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(mapaQuery)}` : '';

    const metodoPago = pedido && pedido.metodo_pago != null ? String(pedido.metodo_pago).trim() : '';
    const referencias = pedido && pedido.referencias != null ? String(pedido.referencias).trim() : '';

    const productos = Array.isArray(pedido.productos) ? pedido.productos : [];
    const productosHtml = productos.length > 0 ?
      productos
      .map((producto) => {
        const cantidad = producto && producto.cantidad != null ? String(producto.cantidad) : '';
        const nombre = producto && producto.nombre != null ? String(producto.nombre) : '';
        return `<li>${escapeHtml(cantidad)} x ${escapeHtml(nombre)}</li>`;
      })
      .join('') :
      '<li class="text-muted">Sin productos</li>';

    const telefonoHtml = (telefonoNormalizado !== '' && telefonoEnlace !== '')
      ? `<a class="contact-link" href="tel:${escapeHtml(telefonoEnlace)}">${escapeHtml(telefonoNormalizado)}</a>`:
      '<span class="text-muted">Sin teléfono</span>';

    const direccionHtml = mapUrl !== '' ?
      `<a class="contact-link" href="${escapeHtml(mapUrl)}" target="_blank" rel="noopener noreferrer"> ${escapeHtml(direccionBase)}</a>` :
      '<span class="text-muted">Sin dirección</span>';

    const metodoPagoHtml = metodoPago !== '' ?
      escapeHtml(metodoPago) :
      '<span class="text-muted">Sin método de pago</span>';

    const referenciasHtml = referencias !== '' ? escapeHtml(referencias) : 'Sin referencias';

    const totalFormateado = formatearTotal(pedido.total);

    tarjeta.innerHTML = `
      <h5>#${escapeHtml(pedidoId)} - ${escapeHtml(pedido.nombre ?? '')}</h5>
      <p class="mb-2">
        <i class="fas fa-phone me-2"></i>
        ${telefonoHtml}
      </p>
      <p class="mb-2">
        <i class="fas fa-map-marker-alt me-2"></i>
        ${direccionHtml}
      </p>
      <p class="mb-2">
        <i class="fas fa-credit-card me-2"></i>
        ${metodoPagoHtml}
      </p>
      <p class="mb-2">
        <i class="fas fa-money-bill-wave me-2"></i>
        Total: $${escapeHtml(totalFormateado)}
      </p>
      <p class="mb-2">
        <i class="fas fa-receipt me-2"></i>
        ¿Está pago?: <span class="${obtenerClaseEstadoPago(estadoPago)}" data-estado-pago>${obtenerTextoEstadoPago(estadoPago)}</span>
      </p>
      <p class="fw-semibold mb-2">🛍️ Productos:</p>
      <ul class="mb-3">
        ${productosHtml}
      </ul>
        <p class="mb-0"><strong>📌 Referencias:</strong> ${referenciasHtml}</p>
        <div class="mt-4">
          <button class="btn btn-gold w-100" data-id="${escapeHtml(pedidoId)}" data-action="entregado">Entregado</button>
        </div>
    `;

    return tarjeta;
  }

  function actualizarTarjetaConDatos(pedido) {
    if (!pedidosContainer || !pedido || pedido.ID === undefined || pedido.ID === null) {
      return;
    }

    const pedidoId = String(pedido.ID);
    const estadoPago = normalizarEstadoPago(pedido.esta_pago);
    const selector = `.glass-card[data-pedido-id="${pedidoId}"]`;
    const tarjetaExistente = pedidosContainer.querySelector(selector);

    if (tarjetaExistente) {
      tarjetaExistente.dataset.estaPago = estadoPago;
      const spanEstadoPago = tarjetaExistente.querySelector('[data-estado-pago]');
      if (spanEstadoPago) {
        spanEstadoPago.textContent = obtenerTextoEstadoPago(estadoPago);
        spanEstadoPago.className = obtenerClaseEstadoPago(estadoPago);
      }
      return;
    }

    const nuevaTarjeta = crearTarjetaPedido(pedido);
    if (nuevaTarjeta) {
      pedidosContainer.prepend(nuevaTarjeta);
    }
  }

  async function sincronizarPedidos() {
    if (!pedidosContainer || sincronizacionEnCurso) {
      return;
    }

    sincronizacionEnCurso = true;

    try {
      const response = await fetch('../admin/obtener_pedidos_delivery.php', {
        cache: 'no-store'
      });

      if (!response.ok) {
        throw new Error('Error al obtener los pedidos.');
      }

      const data = await response.json();
      if (!data || data.success !== true || !Array.isArray(data.pedidos)) {
        const mensaje = data && data.message ? data.message : 'La respuesta del servidor no es válida.';
        throw new Error(mensaje);
      }

      const idsVigentes = new Set();

      data.pedidos.forEach((pedido) => {
        if (!pedido || pedido.ID === undefined || pedido.ID === null) {
          return;
        }

        const id = String(pedido.ID);
        idsVigentes.add(id);
        actualizarTarjetaConDatos(pedido);
      });

      pedidosContainer.querySelectorAll('.glass-card').forEach((tarjeta) => {
        const id = tarjeta.getAttribute('data-pedido-id');
        if (id && !idsVigentes.has(id)) {
          tarjeta.remove();
        }
      });

      actualizarVisibilidadMensaje();
    } catch (error) {
      console.error('No se pudo sincronizar la información de los pedidos:', error);
    } finally {
      sincronizacionEnCurso = false;
    }
  }

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

  function crearFormularioProtegido() {
    if (!csrfToken) {
      throw new Error('No se encontró el token CSRF en la página.');
    }
    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    return formData;
  }

  async function marcarEntregado(pedidoId, boton) {

    const textoOriginal = boton.textContent;
    boton.disabled = true;
    boton.textContent = 'Actualizando...';

    let pedidoActualizado = false;

    try {
      const formData = crearFormularioProtegido();
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
        const tarjeta = boton.closest('.glass-card');
        if (tarjeta) {
          tarjeta.remove();
          actualizarVisibilidadMensaje();
        }
      } else {
        const mensaje = resultado && resultado.message ? resultado.message : 'La respuesta del servidor no es válida.';
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

  document.addEventListener('click', (event) => {
    const boton = event.target.closest('[data-action="entregado"]');
    if (!boton || !pedidosContainer || !pedidosContainer.contains(boton)) {
      return;
    }

    const pedidoId = boton.getAttribute('data-id');
    if (pedidoId) {
      marcarEntregado(pedidoId, boton);
    }
  });

  actualizarVisibilidadMensaje();
  sincronizarPedidos();
  setInterval(sincronizarPedidos, 4000);
</script>

<?php include("../admin/templates/footer.php"); ?>