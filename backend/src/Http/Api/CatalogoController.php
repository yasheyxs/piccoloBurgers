<?php

namespace Piccolo\Http\Api;

use PDO;

/**
 * Información pública del sitio: menú, inicio, puntos y formulario de contacto.
 */
final class CatalogoController
{
    private const LIMITE_MAXIMO = 50;

    public function __construct(private PDO $conexion)
    {
    }

    /** GET /menu?categoria=&busqueda=&offset=0&limit=8 */
    public function menu(Peticion $peticion): void
    {
        $limite = min(self::LIMITE_MAXIMO, max(1, (int) $peticion->query('limit', 8)));
        $resultado = \buscarMenu(
            $this->conexion,
            (string) $peticion->query('categoria', ''),
            (string) $peticion->query('busqueda', ''),
            (int) $peticion->query('offset', 0),
            $limite
        );

        $ids = array_map(static fn($fila) => (int) $fila['ID'], $resultado['items']);
        $disponibilidad = \obtenerDisponibilidadMenu($this->conexion, $ids);

        Respuesta::ok([
            'productos' => array_map(
                static fn($fila) => Formato::producto($fila, $disponibilidad[(string) $fila['ID']] ?? null),
                $resultado['items']
            ),
            'total' => $resultado['total'],
            'categoria' => $resultado['categoria'] ?: null,
            'categorias' => \menuCategoriasDisponibles(),
        ]);
    }

    /** GET /menu/disponibilidad?ids=1,2,3 */
    public function disponibilidad(Peticion $peticion): void
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $peticion->query('ids', ''))));
        if (!$ids) {
            throw ApiException::validacion(['ids' => 'Indicá los productos separados por coma, por ejemplo ?ids=1,2,3.']);
        }

        Respuesta::ok(Formato::disponibilidad(\obtenerDisponibilidadMenu($this->conexion, array_values($ids))));
    }

    /** GET /inicio: banner principal y testimonios. */
    public function inicio(Peticion $peticion): void
    {
        $banner = $this->conexion->query('SELECT * FROM tbl_banners ORDER BY ID DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        $testimonios = $this->conexion->query('SELECT * FROM tbl_testimonios ORDER BY ID DESC')->fetchAll(PDO::FETCH_ASSOC);

        Respuesta::ok([
            'banner' => $banner ? [
                'titulo' => (string) $banner['titulo'],
                'descripcion' => (string) $banner['descripcion'],
                'imagen' => Formato::urlPublica((string) ($banner['imagen'] ?: 'img/BannerBG.jpg')),
            ] : null,
            'testimonios' => array_map(static fn($fila) => [
                'id' => (int) $fila['ID'],
                'nombre' => (string) $fila['nombre'],
                'opinion' => (string) $fila['opinion'],
            ], $testimonios),
        ]);
    }

    /** GET /negocio: datos del local (dirección, horarios, WhatsApp, redes, datos de pago) */
    public function negocio(Peticion $peticion): void
    {
        $n = \piccolo_negocio();
        Respuesta::ok([
            'nombre' => $n['nombre'],
            'nombreCorto' => $n['nombre_corto'],
            'eslogan' => $n['eslogan'],
            'servicio' => $n['servicio'],
            'direccion' => [
                'calle' => $n['direccion']['calle'],
                'localidad' => $n['direccion']['localidad'],
                'provincia' => $n['direccion']['provincia'],
                'referencia' => $n['direccion']['referencia'],
                'completa' => $n['direccion']['completa'],
            ],
            'mapa' => $n['mapa'],
            'horarios' => $n['horarios'],
            'cerrado' => $n['cerrado'],
            'whatsapp' => ['numero' => $n['whatsapp'], 'visible' => $n['whatsapp_visible'], 'url' => $n['whatsapp_url']],
            'redes' => $n['redes'],
            'pago' => $n['pago'],
            'costoDelivery' => $n['costo_delivery'],
        ]);
    }

    /** GET /puntos/configuracion */
    public function configuracionPuntos(Peticion $peticion): void
    {
        $configuracion = \obtenerConfiguracionPuntos($this->conexion);

        Respuesta::ok([
            'minimoPuntos' => (int) $configuracion['minimo_puntos'],
            'valorPunto' => (float) $configuracion['valor_punto'],
            'maximoPorcentaje' => (float) $configuracion['maximo_porcentaje'],
            'puntosPorCada' => 1500,
        ]);
    }

    /** POST /comentarios {nombre, correo, mensaje} */
    public function comentar(Peticion $peticion): void
    {
        $nombre = $peticion->texto('nombre', 120);
        $correo = $peticion->texto('correo', 160);
        $mensaje = $peticion->texto('mensaje', 600);

        $errores = [];
        if ($nombre === '') {
            $errores['nombre'] = 'Ingresá tu nombre.';
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errores['correo'] = 'Ingresá un email válido.';
        }
        if ($mensaje === '') {
            $errores['mensaje'] = 'Escribí tu mensaje.';
        }
        if ($errores) {
            throw ApiException::validacion($errores);
        }

        $stmt = $this->conexion->prepare('INSERT INTO tbl_comentarios (nombre, correo, mensaje) VALUES (?, ?, ?)');
        $stmt->execute([$nombre, $correo, $mensaje]);

        Respuesta::creado(['mensaje' => '¡Gracias por tu comentario!']);
    }
}
