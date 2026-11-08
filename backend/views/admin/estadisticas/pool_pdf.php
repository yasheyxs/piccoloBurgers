<?php
/**
 * HTML de las estadísticas del pool en PDF (lo dibuja Dompdf). Las barras se hacen con tablas,
 * igual que en el reporte de ventas, así no hace falta ninguna imagen externa.
 *
 * @var array<string, mixed> $datos  lo que devuelve EstadisticasController::pool()
 */
$pesos = static fn(float $monto): string => '$' . number_format($monto, 2, ',', '.');
$fecha = static fn(?string $valor): string => $valor ? date('d/m/Y H:i', strtotime($valor)) : 'En curso';
$resumen = $datos['resumen'];
$ultimas = array_slice(array_reverse($datos['jornadas']), -12);
$maximo = max([1, ...array_column($ultimas, 'vendidas')]);
$barra = static function (int $valor, int $maximo): string {
    $ancho = (int) round(100 * $valor / $maximo);
    return $ancho > 0 ? '<div class="barra" style="width: ' . $ancho . '%"></div>' : '';
};
$maximoMesa = max(1, $resumen['mesas']['azul']['vendidas'], $resumen['mesas']['rojo']['vendidas']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: "DejaVu Sans", sans-serif; color: #333; font-size: 10px; }
  h1, h2 { text-align: center; color: #444; margin: 4px 0; }
  h2 { font-size: 12px; font-weight: normal; }
  h3 { color: #444; margin: 16px 0 6px; font-size: 12px; }
  .metricas { width: 100%; border-collapse: separate; border-spacing: 8px; }
  .metricas td { background: #f4f6f8; border-radius: 6px; padding: 8px; text-align: center; }
  .metricas .valor { font-size: 16px; font-weight: bold; color: #111; margin-top: 4px; }
  table.datos { width: 100%; border-collapse: collapse; table-layout: fixed; }
  table.datos tr { page-break-inside: avoid; }
  table.datos th { background: #36A2EB; color: #fff; padding: 5px; text-align: left; }
  table.datos td { border-bottom: 1px solid #ddd; padding: 4px 5px; }
  table.datos .num { text-align: right; white-space: nowrap; }
  .barra { height: 9px; background: #2563eb; }
  .barra.roja { background: #dc2626; }
  .vacio { color: #888; font-style: italic; }
</style>
</head>
<body>
  <h1>Estadísticas del pool</h1>
  <h2>Del <?= date('d/m/Y', strtotime($datos['desde'])) ?> al <?= date('d/m/Y', strtotime($datos['hasta'])) ?></h2>

  <table class="metricas">
    <tr>
      <td>Jornadas<div class="valor"><?= $resumen['jornadas'] ?></div></td>
      <td>Fichas vendidas<div class="valor"><?= $resumen['vendidas'] ?></div></td>
      <td>Promedio por jornada<div class="valor"><?= number_format($resumen['promedio_por_jornada'], 2, ',', '.') ?></div></td>
      <td>Valor vendido<div class="valor"><?= $pesos($resumen['monto']) ?></div></td>
    </tr>
  </table>

  <h3>Mesa azul y roja</h3>
  <table class="datos">
    <tr><th style="width: 18%">Mesa</th><th class="num" style="width: 14%">Fichas</th><th class="num" style="width: 18%">Valor vendido</th><th></th></tr>
    <?php foreach (['azul' => 'Pool azul', 'rojo' => 'Pool rojo'] as $mesa => $nombre): ?>
      <tr>
        <td><?= $nombre ?></td>
        <td class="num"><?= $resumen['mesas'][$mesa]['vendidas'] ?></td>
        <td class="num"><?= $pesos($resumen['mesas'][$mesa]['monto']) ?></td>
        <td><?= str_replace('class="barra"', 'class="barra' . ($mesa === 'rojo' ? ' roja' : '') . '"', $barra($resumen['mesas'][$mesa]['vendidas'], $maximoMesa)) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>

  <h3>Fichas por jornada<?= count($datos['jornadas']) > count($ultimas) ? ' (las últimas ' . count($ultimas) . ')' : '' ?></h3>
  <?php if ($ultimas): ?>
    <table class="datos">
      <tr><th style="width: 18%">Jornada</th><th class="num" style="width: 14%">Fichas</th><th></th></tr>
      <?php foreach ($ultimas as $j): ?>
        <tr><td>#<?= $j['id'] ?> · <?= date('d/m', strtotime($j['apertura'])) ?></td><td class="num"><?= $j['vendidas'] ?></td><td><?= $barra($j['vendidas'], $maximo) ?></td></tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p class="vacio">No hubo jornadas en el período.</p>
  <?php endif; ?>

  <?php if ($datos['jornadas']): ?>
    <h3>Jornadas del período</h3>
    <table class="datos">
      <tr>
        <th style="width: 6%">#</th><th>Apertura</th><th>Cierre</th><th style="width: 8%">Estado</th>
        <th class="num">Vendidas</th><th class="num">Valor vendido</th><th class="num">Pendientes</th><th class="num">Jugadores</th><th class="num">Duración</th>
      </tr>
      <?php foreach ($datos['jornadas'] as $j): ?>
        <tr>
          <td><?= $j['id'] ?></td>
          <td><?= $fecha($j['apertura']) ?></td>
          <td><?= $fecha($j['cierre']) ?></td>
          <td><?= piccolo_e(ucfirst($j['estado'])) ?></td>
          <td class="num"><?= $j['vendidas'] ?></td>
          <td class="num"><?= $pesos($j['monto']) ?></td>
          <td class="num"><?= $j['pendientes'] ?></td>
          <td class="num"><?= $j['jugadores'] ?></td>
          <td class="num"><?= piccolo_e($j['duracion']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</body>
</html>
