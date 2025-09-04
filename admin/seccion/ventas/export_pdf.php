<?php
require("../../bd.php");
require_once __DIR__ . '/../../../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Validación de fechas
function validarFecha($fecha) {
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) && strtotime($fecha);
}

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin_input = $_GET['fecha_fin'] ?? date('Y-m-d');

if (!validarFecha($fecha_inicio)) $fecha_inicio = date('Y-m-01');
if (!validarFecha($fecha_fin_input)) $fecha_fin_input = date('Y-m-d');

$fecha_fin = $fecha_fin_input . ' 23:59:59';

// Total de ventas
$stmt = $conexion->prepare("SELECT SUM(pd.precio * pd.cantidad) AS total_ventas
    FROM tbl_pedidos_detalle pd
    JOIN tbl_pedidos p ON pd.pedido_id = p.ID
    WHERE p.fecha BETWEEN :inicio AND :fin");
$stmt->bindParam(':inicio', $fecha_inicio);
$stmt->bindParam(':fin', $fecha_fin);
$stmt->execute();
$total_ventas = $stmt->fetch(PDO::FETCH_ASSOC)['total_ventas'] ?? 0;

// Total de pedidos
$stmt = $conexion->prepare("SELECT COUNT(*) AS total_pedidos
    FROM tbl_pedidos
    WHERE fecha BETWEEN :inicio AND :fin");
$stmt->bindParam(':inicio', $fecha_inicio);
$stmt->bindParam(':fin', $fecha_fin);
$stmt->execute();
$total_pedidos = $stmt->fetch(PDO::FETCH_ASSOC)['total_pedidos'] ?? 0;

// Producto más vendido
$stmt = $conexion->prepare("SELECT pd.nombre, SUM(pd.cantidad) AS total_vendido
    FROM tbl_pedidos_detalle pd
    JOIN tbl_pedidos p ON pd.pedido_id = p.ID
    WHERE p.fecha BETWEEN :inicio AND :fin
    GROUP BY pd.nombre
    ORDER BY total_vendido DESC
    LIMIT 1");
$stmt->bindParam(':inicio', $fecha_inicio);
$stmt->bindParam(':fin', $fecha_fin);
$stmt->execute();
$producto_mas_vendido = $stmt->fetch(PDO::FETCH_ASSOC);

// Tabla de productos
$stmt = $conexion->prepare("SELECT pd.nombre, SUM(pd.cantidad) AS total_vendido
    FROM tbl_pedidos_detalle pd
    JOIN tbl_pedidos p ON pd.pedido_id = p.ID
    WHERE p.fecha BETWEEN :inicio AND :fin
    GROUP BY pd.nombre
    ORDER BY total_vendido DESC");
$stmt->bindParam(':inicio', $fecha_inicio);
$stmt->bindParam(':fin', $fecha_fin);
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Métodos de pago
$stmt = $conexion->prepare("SELECT metodo_pago, COUNT(*) AS total
    FROM tbl_pedidos
    WHERE fecha BETWEEN :inicio AND :fin
    GROUP BY metodo_pago");
$stmt->bindParam(':inicio', $fecha_inicio);
$stmt->bindParam(':fin', $fecha_fin);
$stmt->execute();
$metodos_pago = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tipos de entrega
$stmt = $conexion->prepare("SELECT tipo_entrega, COUNT(*) AS total
    FROM tbl_pedidos
    WHERE fecha BETWEEN :inicio AND :fin
    GROUP BY tipo_entrega");
$stmt->bindParam(':inicio', $fecha_inicio);
$stmt->bindParam(':fin', $fecha_fin);
$stmt->execute();
$tipos_entrega = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ventas mensuales
$anio_actual = date('Y');
$stmt = $conexion->prepare("SELECT MONTH(p.fecha) AS mes, SUM(pd.precio * pd.cantidad) AS total_ventas
    FROM tbl_pedidos_detalle pd
    JOIN tbl_pedidos p ON pd.pedido_id = p.ID
    WHERE YEAR(p.fecha) = :anio
    GROUP BY MONTH(p.fecha)");
$stmt->bindParam(':anio', $anio_actual);
$stmt->execute();
$ventas_mensuales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gráficos con QuickChart
$productos_labels = array_column($productos, 'nombre');
$productos_data = array_column($productos, 'total_vendido');
$productos_chart_url = "https://quickchart.io/chart?c=" . urlencode(json_encode([
    "type" => "pie",
    "data" => [
        "labels" => $productos_labels,
        "datasets" => [[ "data" => $productos_data ]]
    ],
    "options" => ["plugins" => ["legend" => ["position" => "bottom"]]]
]));

$mes_nombres = [1=>"Enero",2=>"Febrero",3=>"Marzo",4=>"Abril",5=>"Mayo",6=>"Junio",7=>"Julio",8=>"Agosto",9=>"Septiembre",10=>"Octubre",11=>"Noviembre",12=>"Diciembre"];
$meses = array_map(fn($v) => $mes_nombres[(int)$v['mes']], $ventas_mensuales);

$ventas_data = array_column($ventas_mensuales, 'total_ventas');
$ventas_chart_url = "https://quickchart.io/chart?c=" . urlencode(json_encode([
    "type" => "bar",
    "data" => [
        "labels" => $meses,
        "datasets" => [[
            "label" => "Ventas Mensuales",
            "data" => $ventas_data,
            "backgroundColor" => "#36A2EB"
        ]]
    ],
    "options" => ["plugins" => ["legend" => ["display" => false]]]
]));

// PDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// HTML
$html = '
<style>
body { font-family: Arial, sans-serif; color: #333; }
h1,h2 { text-align: center; color: #444; }
.metrics { display: flex; justify-content: space-around; margin-bottom: 20px; flex-wrap: wrap; }
.metric { background-color: #f7f7f7; padding: 10px 20px; border-radius: 8px; width: 30%; text-align: center; margin-bottom: 10px; }
.metric h3 { margin: 0; font-size: 18px; color: #555; }
.metric p { margin: 5px 0 0; font-size: 22px; font-weight: bold; color: #111; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
table, th, td { border: 1px solid #ccc; }
th { background-color: #36A2EB; color: white; padding: 8px; text-align: left; }
td { padding: 6px; text-align: left; }
.chart { text-align:center; margin: 20px 0; }
</style>

<h1>Reporte de Ventas</h1>
<h2>Desde '.htmlspecialchars($fecha_inicio).' hasta '.htmlspecialchars($fecha_fin_input).'</h2>

<div class="metrics">
  <div class="metric" style="width:48%;">
    <h3>Total Ventas</h3>
    <p>$'.number_format($total_ventas,2).'</p>
  </div>
  <div class="metric" style="width:48%;">
    <h3>Total Pedidos</h3>
    <p>'.$total_pedidos.'</p>
  </div>
</div>

<div class="metrics">
  <div class="metric" style="width:100%;">
    <h3>Producto más vendido</h3>
    <p>'.htmlspecialchars($producto_mas_vendido['nombre'] ?? 'N/A').' ('.htmlspecialchars($producto_mas_vendido['total_vendido'] ?? 0).')</p>
  </div>
</div>


<div class="metrics">
  <div class="metric" style="width:45%;">
    <h3>Métodos de Pago</h3>';
if (!empty($metodos_pago)) {
  foreach ($metodos_pago as $m) {
    $html .= '<p>'.ucfirst(htmlspecialchars($m['metodo_pago'])).': '.htmlspecialchars($m['total']).'</p>';
  }
} else {
  $html .= '<p class="text-muted">Sin registros</p>';
}
$html .= '</div>

  <div class="metric" style="width:45%;">
    <h3>Tipos de Entrega</h3>';
if (!empty($tipos_entrega)) {
  foreach ($tipos_entrega as $t) {
    $html .= '<p>'.ucfirst(htmlspecialchars($t['tipo_entrega'])).': '.htmlspecialchars($t['total']).'</p>';
  }
} else {
  $html .= '<p class="text-muted">Sin registros</p>';
}
$html .= '</div>
</div>

<div class="chart">
<h3>Productos más vendidos</h3>
<img src="'.$productos_chart_url.'" width="400">
</div>

<div class="chart">
<h3>Comparación mensual de ventas</h3>
<img src="'.$ventas_chart_url.'" width="400">
</div>

<table>
<tr>
<th>Producto</th>
<th>Cantidad Vendida</th>
</tr>';
foreach($productos as $producto){
    $html .= "<tr><td>".htmlspecialchars($producto['nombre'])."</td><td>".htmlspecialchars($producto['total_vendido'])."</td></tr>";
}

$html .= '</table>';

// Renderizar y enviar PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("reporte_ventas.pdf", ["Attachment" => true]);
?>
