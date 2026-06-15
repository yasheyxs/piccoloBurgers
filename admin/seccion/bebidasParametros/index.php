<?php
require_once __DIR__ . '/../../bd.php';
require_once __DIR__ . '/../../../app/Services/bebidas_schema.php';

verificarRol('admin');
asegurarTablasBebidas($conexion);

$errores = [];
$mensajeExito = '';
$edicionFallida = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenSesion = (string) ($_SESSION['csrf_token'] ?? '');
    $token = (string) ($_POST['csrf_token'] ?? '');

    if ($tokenSesion === '' || !hash_equals($tokenSesion, $token)) {
        $errores[] = 'El token de seguridad es inválido. Recarga la página.';
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $valorJarra = filter_var(str_replace(',', '.', (string) ($_POST['valor_jarra'] ?? '')), FILTER_VALIDATE_FLOAT);
        $valorPromo = filter_var(str_replace(',', '.', (string) ($_POST['valor_promo'] ?? '')), FILTER_VALIDATE_FLOAT);
        $jarrasPorPromo = filter_var(
            $_POST['jarras_por_promo'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 99]]
        );
        $activa = isset($_POST['activa']);
        $datosEnviados = [
            'id' => $id,
            'nombre' => $nombre,
            'valor_jarra' => $valorJarra === false ? '' : (float) $valorJarra,
            'valor_promo' => $valorPromo === false ? '' : (float) $valorPromo,
            'jarras_por_promo' => $jarrasPorPromo === false ? 3 : (int) $jarrasPorPromo,
            'activa' => $activa,
        ];

        try {
            bebidaGuardar(
                $conexion,
                $id,
                $nombre,
                $valorJarra === false ? 0 : (float) $valorJarra,
                $valorPromo === false ? 0 : (float) $valorPromo,
                $jarrasPorPromo === false ? 0 : (int) $jarrasPorPromo,
                $activa
            );
            $mensajeExito = $id > 0 ? 'Bebida actualizada correctamente.' : 'Bebida creada correctamente.';
        } catch (PDOException $error) {
            $errores[] = str_contains($error->getMessage(), 'Duplicate')
                ? 'Ya existe una bebida con ese nombre.'
                : 'No se pudo guardar la bebida.';
            if ($id > 0) {
                $edicionFallida = $datosEnviados;
            }
        } catch (RuntimeException $error) {
            $errores[] = $error->getMessage();
            if ($id > 0) {
                $edicionFallida = $datosEnviados;
            }
        }
    }
}

$bebidas = bebidaListar($conexion);
$adminPageIdentifier = 'bebidas-parametros';
include __DIR__ . '/../../templates/header.php';
?>

<div class="py-4">
  <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
    <div>
      <h1 class="h3 mb-2">Parámetros de Bebidas</h1>
      <p class="text-muted mb-0">Administra bebidas, disponibilidad, valores y composición de cada promoción.</p>
    </div>
    <a class="btn btn-outline-primary align-self-lg-start" href="../bebidas/"
      title="Volver a Bebidas" aria-label="Volver a Bebidas">
      <i class="fa-solid fa-glass-water" aria-hidden="true"></i>
    </a>
  </div>

  <?php if ($errores): ?>
    <div class="alert alert-danger">
      <?php foreach ($errores as $error): ?><div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>
  <?php if ($mensajeExito): ?>
    <div class="alert alert-success"><?= htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-12 col-lg-4">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent">
          <h2 class="h5 mb-0">Nueva bebida</h2>
        </div>
        <div class="card-body">
          <form method="post" class="d-grid gap-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="id" value="0">
            <div>
              <label class="form-label fw-semibold" for="nombre">Nombre</label>
              <input class="form-control" id="nombre" name="nombre" maxlength="100" required>
            </div>
            <div>
              <label class="form-label fw-semibold" for="valor_jarra">Valor de jarra</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input class="form-control" id="valor_jarra" name="valor_jarra" type="number" min="0.01" step="0.01" required>
              </div>
            </div>
            <div>
              <label class="form-label fw-semibold" for="valor_promo">Valor de promo</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input class="form-control" id="valor_promo" name="valor_promo" type="number" min="0.01" step="0.01" required>
              </div>
            </div>
            <div>
              <label class="form-label fw-semibold" for="jarras_por_promo">Jarras por promoción</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-layer-group"></i></span>
                <input class="form-control" id="jarras_por_promo" name="jarras_por_promo" type="number"
                  min="1" max="99" step="1" value="3" required>
              </div>
              <div class="form-text">Cantidad de jarras que se entregan por cada promoción vendida.</div>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" id="activa" name="activa" type="checkbox"
                checked>
              <label class="form-check-label" for="activa">Disponible para nuevas ventas</label>
            </div>
            <div class="d-flex gap-2 justify-content-end">
              <button class="btn btn-warning text-dark" type="submit">
                <i class="fa-solid fa-floppy-disk me-1"></i> Guardar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-8">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-transparent"><h2 class="h5 mb-0">Bebidas configuradas</h2></div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead><tr><th>Bebida</th><th>Jarra</th><th>Promo</th><th>Jarras por promo</th><th>Estado</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($bebidas as $bebida): ?>
                  <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($bebida['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>$<?= number_format($bebida['valor_jarra'], 2, ',', '.'); ?></td>
                    <td>$<?= number_format($bebida['valor_promo'], 2, ',', '.'); ?></td>
                    <td><?= (int) $bebida['jarras_por_promo']; ?></td>
                    <td>
                      <span class="badge <?= $bebida['activa'] ? 'text-bg-success' : 'text-bg-secondary'; ?>">
                        <?= $bebida['activa'] ? 'Activa' : 'Inactiva'; ?>
                      </span>
                    </td>
                    <td class="text-end">
                      <button class="btn btn-sm btn-outline-primary" type="button" title="Editar"
                        data-bs-toggle="modal" data-bs-target="#modalEditarBebida"
                        data-edit-bebida
                        data-id="<?= (int) $bebida['id']; ?>"
                        data-nombre="<?= htmlspecialchars($bebida['nombre'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-valor-jarra="<?= htmlspecialchars((string) $bebida['valor_jarra'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-valor-promo="<?= htmlspecialchars((string) $bebida['valor_promo'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-jarras-por-promo="<?= (int) $bebida['jarras_por_promo']; ?>"
                        data-activa="<?= $bebida['activa'] ? '1' : '0'; ?>">
                        <i class="fa-solid fa-pen"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$bebidas): ?>
                  <tr><td colspan="6" class="text-center text-muted py-4">Todavía no hay bebidas configuradas.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalEditarBebida" tabindex="-1" aria-labelledby="modalEditarBebidaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form method="post" data-edit-form>
          <div class="modal-header">
            <h2 class="modal-title fs-5" id="modalEditarBebidaLabel">Editar bebida</h2>
            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body d-grid gap-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="id" data-edit-id>
            <div>
              <label class="form-label fw-semibold" for="editar_nombre">Nombre</label>
              <input class="form-control" id="editar_nombre" name="nombre" maxlength="100" data-edit-nombre required>
            </div>
            <div>
              <label class="form-label fw-semibold" for="editar_valor_jarra">Valor de jarra</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input class="form-control" id="editar_valor_jarra" name="valor_jarra" type="number"
                  min="0.01" step="0.01" data-edit-valor-jarra required>
              </div>
            </div>
            <div>
              <label class="form-label fw-semibold" for="editar_valor_promo">Valor de promo</label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input class="form-control" id="editar_valor_promo" name="valor_promo" type="number"
                  min="0.01" step="0.01" data-edit-valor-promo required>
              </div>
            </div>
            <div>
              <label class="form-label fw-semibold" for="editar_jarras_por_promo">Jarras por promoción</label>
              <input class="form-control" id="editar_jarras_por_promo" name="jarras_por_promo" type="number"
                min="1" max="99" step="1" data-edit-jarras required>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" id="editar_activa" name="activa" type="checkbox" data-edit-activa>
              <label class="form-check-label" for="editar_activa">Disponible para nuevas ventas</label>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-warning text-dark" type="submit">
              <i class="fa-solid fa-floppy-disk me-1"></i> Guardar cambios
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const modalElement = document.getElementById('modalEditarBebida');
  const editForm = document.querySelector('[data-edit-form]');
  if (!modalElement || !editForm) return;

  function cargarEdicion(datos) {
    editForm.querySelector('[data-edit-id]').value = datos.id ?? '';
    editForm.querySelector('[data-edit-nombre]').value = datos.nombre ?? '';
    editForm.querySelector('[data-edit-valor-jarra]').value = datos.valorJarra ?? '';
    editForm.querySelector('[data-edit-valor-promo]').value = datos.valorPromo ?? '';
    editForm.querySelector('[data-edit-jarras]').value = datos.jarrasPorPromo ?? 3;
    editForm.querySelector('[data-edit-activa]').checked = String(datos.activa) === '1' || datos.activa === true;
  }

  document.querySelectorAll('[data-edit-bebida]').forEach(button => {
    button.addEventListener('click', () => cargarEdicion({
      id: button.dataset.id,
      nombre: button.dataset.nombre,
      valorJarra: button.dataset.valorJarra,
      valorPromo: button.dataset.valorPromo,
      jarrasPorPromo: button.dataset.jarrasPorPromo,
      activa: button.dataset.activa
    }));
  });

  <?php if ($edicionFallida): ?>
    cargarEdicion(<?= json_encode([
        'id' => $edicionFallida['id'],
        'nombre' => $edicionFallida['nombre'],
        'valorJarra' => $edicionFallida['valor_jarra'],
        'valorPromo' => $edicionFallida['valor_promo'],
        'jarrasPorPromo' => $edicionFallida['jarras_por_promo'],
        'activa' => $edicionFallida['activa'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>);
    bootstrap.Modal.getOrCreateInstance(modalElement).show();
  <?php endif; ?>
});
</script>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
