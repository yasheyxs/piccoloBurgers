<?php
require_once __DIR__ . '/../../bd.php';
require_once __DIR__ . '/../../../app/Services/pool_schema.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

verificarRol('admin');
asegurarTablaPoolTurnos($conexion);

function poolPdfValidarFecha(string $fecha): bool
{
    return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) && strtotime($fecha) !== false;
}

function poolPdfDuracion(?int $minutos): string
{
    if ($minutos === null) {
        return 'En curso';
    }

    $horas = intdiv($minutos, 60);
    $resto = $minutos % 60;
    return $horas > 0 ? $horas . ' h ' . $resto . ' min' : $resto . ' min';
}

function poolPdfBarChart(array $resumen): string
{
    $vendidasAzul = (int) $resumen['vendidas_azul'];
    $vendidasRojo = (int) $resumen['vendidas_rojo'];
    $montoAzul = (float) $resumen['monto_vendido_azul'];
    $montoRojo = (float) $resumen['monto_vendido_rojo'];
    $max = max(1, $vendidasAzul, $vendidasRojo, $montoAzul / 1000, $montoRojo / 1000);

    $bars = [
        ['x' => 155, 'label' => 'Pool Azul', 'value' => $vendidasAzul, 'color' => '#2563eb'],
        ['x' => 315, 'label' => 'Pool Rojo', 'value' => $vendidasRojo, 'color' => '#dc2626'],
    ];

    $svg = '<svg width="520" height="260" viewBox="0 0 520 260" xmlns="http://www.w3.org/2000/svg">';
    $svg .= '<rect x="0" y="0" width="520" height="260" fill="#ffffff"/>';
    $svg .= '<text x="260" y="24" text-anchor="middle" font-family="Arial" font-size="16" font-weight="bold" fill="#333">Comparativa Azul / Rojo</text>';
    $svg .= '<line x1="50" y1="210" x2="470" y2="210" stroke="#cbd5e1" stroke-width="1"/>';

    foreach ($bars as $bar) {
        $height = (int) round(($bar['value'] / $max) * 145);
        $y = 210 - $height;
        $svg .= '<rect x="' . $bar['x'] . '" y="' . $y . '" width="46" height="' . $height . '" fill="' . $bar['color'] . '"/>';
        $svg .= '<text x="' . ($bar['x'] + 23) . '" y="' . ($y - 6) . '" text-anchor="middle" font-family="Arial" font-size="11" fill="#111">' . $bar['value'] . '</text>';
        $svg .= '<text x="' . ($bar['x'] + 23) . '" y="230" text-anchor="middle" font-family="Arial" font-size="9" fill="#333">' . htmlspecialchars($bar['label']) . '</text>';
    }

    $svg .= '<circle cx="180" cy="248" r="5" fill="#2563eb"/><text x="190" y="252" font-family="Arial" font-size="10">Vendidas Azul</text>';
    $svg .= '<circle cx="315" cy="248" r="5" fill="#dc2626"/><text x="325" y="252" font-family="Arial" font-size="10">Vendidas Rojo</text>';
    $svg .= '</svg>';

    return $svg;
}

function poolPdfLineChart(array $jornadas): string
{
    $points = array_values(array_slice(array_reverse($jornadas), -10));
    $max = max(1, ...array_map(static fn(array $jornada): int => (int) $jornada['total_vendidas'], $points ?: [['total_vendidas' => 0]]));
    $count = max(1, count($points) - 1);
    $polyline = [];
    $labels = '';

    foreach ($points as $index => $jornada) {
        $x = 55 + (int) round(($index / $count) * 410);
        $y = 205 - (int) round(((int) $jornada['total_vendidas'] / $max) * 140);
        $polyline[] = $x . ',' . $y;
        $labels .= '<circle cx="' . $x . '" cy="' . $y . '" r="4" fill="#2563eb"/>';
        $labels .= '<text x="' . $x . '" y="' . ($y - 8) . '" text-anchor="middle" font-family="Arial" font-size="10" fill="#111">' . (int) $jornada['total_vendidas'] . '</text>';
        $labels .= '<text x="' . $x . '" y="225" text-anchor="middle" font-family="Arial" font-size="9" fill="#333">#' . (int) $jornada['id'] . '</text>';
    }

    $svg = '<svg width="520" height="250" viewBox="0 0 520 250" xmlns="http://www.w3.org/2000/svg">';
    $svg .= '<rect x="0" y="0" width="520" height="250" fill="#ffffff"/>';
    $svg .= '<text x="260" y="24" text-anchor="middle" font-family="Arial" font-size="16" font-weight="bold" fill="#333">Fichas vendidas por jornada</text>';
    $svg .= '<line x1="50" y1="205" x2="470" y2="205" stroke="#cbd5e1" stroke-width="1"/>';
    $svg .= '<line x1="50" y1="55" x2="50" y2="205" stroke="#cbd5e1" stroke-width="1"/>';
    $svg .= '<polyline points="' . implode(' ', $polyline) . '" fill="none" stroke="#2563eb" stroke-width="3"/>';
    $svg .= $labels;
    $svg .= '<circle cx="190" cy="242" r="5" fill="#2563eb"/><text x="202" y="246" font-family="Arial" font-size="10">Total de fichas vendidas</text>';
    $svg .= '</svg>';

    return $svg;
}

function poolPdfMoneyChart(array $resumen): string
{
    $montoAzul = (float) $resumen['monto_vendido_azul'];
    $montoRojo = (float) $resumen['monto_vendido_rojo'];
    $max = max(1, $montoAzul, $montoRojo);
    $bars = [
        ['x' => 155, 'label' => 'Pool Azul', 'value' => $montoAzul, 'color' => '#2563eb'],
        ['x' => 315, 'label' => 'Pool Rojo', 'value' => $montoRojo, 'color' => '#dc2626'],
    ];

    $svg = '<svg width="520" height="230" viewBox="0 0 520 230" xmlns="http://www.w3.org/2000/svg">';
    $svg .= '<rect x="0" y="0" width="520" height="230" fill="#ffffff"/>';
    $svg .= '<text x="260" y="24" text-anchor="middle" font-family="Arial" font-size="16" font-weight="bold" fill="#333">Valor monetario vendido por pool</text>';
    $svg .= '<line x1="80" y1="185" x2="450" y2="185" stroke="#cbd5e1" stroke-width="1"/>';

    foreach ($bars as $bar) {
        $height = (int) round(($bar['value'] / $max) * 125);
        $y = 185 - $height;
        $svg .= '<rect x="' . $bar['x'] . '" y="' . $y . '" width="70" height="' . $height . '" fill="' . $bar['color'] . '"/>';
        $svg .= '<text x="' . ($bar['x'] + 35) . '" y="' . ($y - 8) . '" text-anchor="middle" font-family="Arial" font-size="11" fill="#111">$' . number_format($bar['value'], 0, ',', '.') . '</text>';
        $svg .= '<text x="' . ($bar['x'] + 35) . '" y="205" text-anchor="middle" font-family="Arial" font-size="11" fill="#333">' . $bar['label'] . '</text>';
    }

    $svg .= '</svg>';
    return $svg;
}

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

if (!poolPdfValidarFecha($fecha_inicio)) {
    $fecha_inicio = date('Y-m-01');
}

if (!poolPdfValidarFecha($fecha_fin)) {
    $fecha_fin = date('Y-m-d');
}

$jornadas = poolListarJornadas($conexion, $fecha_inicio, $fecha_fin);
$resumen = poolResumenGeneral($jornadas);

$html = '
<style>
body { font-family: Arial, sans-serif; color: #333; font-size: 12px; }
h1, h2 { text-align: center; color: #444; }
.metrics { width: 100%; border-collapse: collapse; margin: 18px 0; }
.metrics td { width: 25%; background: #f7f7f7; border: 1px solid #ddd; padding: 10px; text-align: center; }
.metrics strong { display: block; font-size: 18px; color: #111; margin-top: 4px; }
.charts { width: 100%; margin: 18px 0; }
.charts td { width: 50%; border: 1px solid #ddd; padding: 8px; text-align: center; vertical-align: top; }
table { width: 100%; border-collapse: collapse; margin-top: 18px; }
th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
th { background-color: #4287f5; color: white; }
</style>

<h1>Estadisticas de Pool</h1>
<h2>Desde ' . htmlspecialchars($fecha_inicio) . ' hasta ' . htmlspecialchars($fecha_fin) . '</h2>

<table class="metrics">
<tr>
  <td>Jornadas<strong>' . (int) $resumen['total_jornadas'] . '</strong></td>
  <td>Vendidas<strong>' . (int) $resumen['total_vendidas'] . '</strong></td>
  <td>Valor vendido<strong>$' . number_format((float) $resumen['monto_vendido_total'], 2, ',', '.') . '</strong></td>
  <td>Pendientes<strong>' . (int) $resumen['total_pendientes'] . '</strong></td>
</tr>
</table>

<table class="charts">
<tr>
  <td>' . poolPdfBarChart($resumen) . '</td>
  <td>' . poolPdfLineChart($jornadas) . '</td>
</tr>
<tr>
  <td colspan="2">' . poolPdfMoneyChart($resumen) . '</td>
</tr>
</table>

<table>
<tr>
  <th>ID</th>
  <th>Apertura</th>
  <th>Cierre</th>
  <th>Estado</th>
  <th>Vendidas</th>
  <th>Valor vendido</th>
  <th>Pendientes</th>
  <th>Jugadores</th>
  <th>Completados</th>
  <th>Duracion</th>
</tr>';

foreach ($jornadas as $jornada) {
    $html .= '<tr>
      <td>' . (int) $jornada['id'] . '</td>
      <td>' . htmlspecialchars($jornada['fecha_apertura']) . '</td>
      <td>' . htmlspecialchars($jornada['fecha_cierre'] ?: 'En curso') . '</td>
      <td>' . htmlspecialchars($jornada['estado']) . '</td>
      <td>' . (int) $jornada['total_vendidas'] . '</td>
      <td>$' . number_format((float) $jornada['monto_vendido_total'], 2, ',', '.') . '</td>
      <td>' . (int) $jornada['total_pendientes'] . '</td>
      <td>' . (int) $jornada['jugadores_atendidos'] . '</td>
      <td>' . (int) $jornada['turnos_completados'] . '</td>
      <td>' . htmlspecialchars(poolPdfDuracion($jornada['duracion_minutos'])) . '</td>
    </tr>';
}

$html .= '</table>';

$options = new Options();
$options->set('isRemoteEnabled', false);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('estadisticas_pool.pdf', ['Attachment' => true]);
exit;
