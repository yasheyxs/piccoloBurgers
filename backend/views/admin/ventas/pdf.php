<?php
/**
 * HTML del reporte de ventas en PDF (lo dibuja Dompdf). Los gráficos son barras hechas con tablas,
 * así no hace falta descargar imágenes de ningún servicio externo.
 *
 * @var array<string, mixed> $resumen  lo que devuelve VentasController::resumen()
 */
$pesos = static fn(float $monto): string => '$' . number_format($monto, 2, ',', '.');
$fecha = static fn(string $iso): string => date('d/m/Y', strtotime($iso));
$estrella = $resumen['producto_estrella'];
$origen = $resumen['por_origen'];
$top = array_slice($resumen['productos'], 0, 10);
$maxProducto = max([1, ...array_column($top, 'cantidad')]);
$maxMes = max([1.0, ...array_column($resumen['mensual']['meses'], 'total')]);
/** Barra horizontal proporcional al máximo (nada si el valor es 0). */
$barra = static function (float $valor, float $maximo): string {
    $ancho = (int) round(100 * $valor / $maximo);
    return $ancho > 0 ? '<div class="barra" style="width: ' . $ancho . '%"></div>' : '';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: "DejaVu Sans", sans-serif; color: #333; font-size: 11px; }
  h1, h2 { text-align: center; color: #444; margin: 4px 0; }
  h2 { font-size: 13px; font-weight: normal; }
  h3 { color: #444; margin: 18px 0 6px; font-size: 13px; }
  .metricas { width: 100%; border-collapse: separate; border-spacing: 8px; margin-top: 10px; }
  .metricas td { background: #f4f6f8; border-radius: 6px; padding: 10px; text-align: center; vertical-align: top; }
  .metricas .valor { font-size: 18px; font-weight: bold; color: #111; margin: 4px 0; }
  .metricas small { color: #666; }
  table.datos { width: 100%; border-collapse: collapse; table-layout: fixed; }
  table.datos tr { page-break-inside: avoid; }
  table.datos th { background: #36A2EB; color: #fff; padding: 6px; text-align: left; }
  table.datos td { border-bottom: 1px solid #ddd; padding: 5px 6px; }
  table.datos .num { text-align: right; white-space: nowrap; }
  .pista { width: 45%; }
  .barra { height: 10px; background: #36A2EB; }
  .vacio { color: #888; font-style: italic; }
</style>
</head>
<body>
  <h1>Reporte de ventas</h1>
  <h2>Del <?= $fecha($resumen['desde']) ?> al <?= $fecha($resumen['hasta']) ?></h2>

  <table class="metricas">
    <tr>
      <td>
        Total vendido
        <div class="valor"><?= $pesos($resumen['total']) ?></div>
        <small>Pedidos <?= $pesos($origen['pedidos']) ?> · Fichas <?= $pesos($origen['fichas']) ?> · Bebidas <?= $pesos($origen['bebidas']) ?></small>
      </td>
      <td>
        Pedidos
        <div class="valor"><?= (int) $resumen['cantidad_pedidos'] ?></div>
        <small>Ticket promedio <?= $pesos($resumen['ticket_promedio']) ?> · sin contar cancelados</small>
      </td>
      <td>
        Producto estrella
        <div class="valor"><?= $estrella ? piccolo_e($estrella['nombre']) : '—' ?></div>
        <small><?= $estrella ? (int) $estrella['cantidad'] . ' vendidos' : 'Sin ventas en el período' ?></small>
      </td>
    </tr>
  </table>

  <table class="metricas">
    <tr>
      <?php foreach (['Métodos de pago' => $resumen['metodos_pago'], 'Tipos de entrega' => $resumen['tipos_entrega']] as $titulo => $grupos): ?>
        <td>
          <strong><?= piccolo_e($titulo) ?></strong><br>
          <?php foreach ($grupos as $grupo): ?>
            <?= piccolo_e(ucfirst($grupo['nombre'])) ?>: <?= (int) $grupo['pedidos'] ?> pedidos (<?= $pesos($grupo['monto']) ?>)<br>
          <?php endforeach; ?>
          <?php if (!$grupos): ?><span class="vacio">Sin registros</span><?php endif; ?>
        </td>
      <?php endforeach; ?>
    </tr>
  </table>

  <h3>Productos más vendidos</h3>
  <?php if ($top): ?>
    <table class="datos">
      <tr><th style="width: 40%">Producto</th><th class="num" style="width: 15%">Unidades</th><th class="pista"></th></tr>
      <?php foreach ($top as $producto): ?>
        <tr>
          <td><?= piccolo_e($producto['nombre']) ?></td>
          <td class="num"><?= (int) $producto['cantidad'] ?></td>
          <td class="pista"><?= $barra($producto['cantidad'], $maxProducto) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p class="vacio">Sin ventas en el período.</p>
  <?php endif; ?>

  <h3>Ventas por mes de <?= (int) $resumen['mensual']['anio'] ?></h3>
  <table class="datos">
    <tr><th style="width: 13%">Mes</th><th class="num" style="width: 15%">Pedidos</th><th class="num" style="width: 13%">Fichas</th><th class="num" style="width: 13%">Bebidas</th><th class="num" style="width: 15%">Total</th><th class="pista"></th></tr>
    <?php foreach ($resumen['mensual']['meses'] as $mes): ?>
      <tr>
        <td><?= piccolo_e($mes['nombre']) ?></td>
        <td class="num"><?= $pesos($mes['pedidos']) ?></td>
        <td class="num"><?= $pesos($mes['fichas']) ?></td>
        <td class="num"><?= $pesos($mes['bebidas']) ?></td>
        <td class="num"><strong><?= $pesos($mes['total']) ?></strong></td>
        <td class="pista"><?= $barra($mes['total'], $maxMes) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>

  <?php if (count($resumen['productos']) > count($top)): ?>
    <h3>Todos los productos vendidos</h3>
    <table class="datos">
      <tr><th style="width: 55%">Producto</th><th class="num">Unidades</th><th class="num">Monto (precio de lista)</th></tr>
      <?php foreach ($resumen['productos'] as $producto): ?>
        <tr><td><?= piccolo_e($producto['nombre']) ?></td><td class="num"><?= (int) $producto['cantidad'] ?></td><td class="num"><?= $pesos($producto['monto']) ?></td></tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</body>
</html>
