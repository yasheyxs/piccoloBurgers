<?php
/**
 * Filtro de período y jornada de las pantallas de estadísticas.
 *
 * @var array<string, mixed> $datos  desde, hasta, jornadas y seleccionada
 */
$seleccionadaId = $datos['seleccionada']['id'] ?? 0;
?>
<form method="get" class="row g-2 mb-4 justify-content-center">
  <div class="col-12 col-md-auto">
    <label class="form-label" for="desde">Desde:</label>
    <input type="date" class="form-control" name="desde" id="desde" value="<?= piccolo_e($datos['desde']) ?>">
  </div>
  <div class="col-12 col-md-auto">
    <label class="form-label" for="hasta">Hasta:</label>
    <input type="date" class="form-control" name="hasta" id="hasta" value="<?= piccolo_e($datos['hasta']) ?>">
  </div>
  <div class="col-12 col-md-auto">
    <label class="form-label" for="jornada">Jornada:</label>
    <select class="form-select" name="jornada" id="jornada">
      <option value="0">La última del período</option>
      <?php foreach ($datos['jornadas'] as $jornada): ?>
        <option value="<?= $jornada['id'] ?>" <?= $jornada['id'] === $seleccionadaId ? 'selected' : '' ?>>#<?= $jornada['id'] ?> · <?= date('d/m/Y H:i', strtotime($jornada['apertura'])) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-12 col-md-auto d-flex align-items-end flex-wrap gap-2">
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter" aria-hidden="true"></i> Filtrar</button>
    <?php foreach (\Piccolo\Http\Admin\Periodo::atajos() as $nombre => [$desde, $hasta]): ?>
      <a href="?<?= piccolo_e(http_build_query(['desde' => $desde, 'hasta' => $hasta])) ?>" class="btn btn-outline-secondary"><?= piccolo_e($nombre) ?></a>
    <?php endforeach; ?>
  </div>
</form>
