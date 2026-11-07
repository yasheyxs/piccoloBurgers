<?php

namespace Piccolo\Http\Api;

/**
 * Datos de la petición actual: método, ruta, parámetros y cuerpo JSON.
 */
final class Peticion
{
    private string $metodo;
    private string $ruta;
    /** @var array<string, string> */
    private array $parametrosRuta = [];
    private ?array $cuerpo = null;

    public function __construct(string $metodo, string $ruta)
    {
        $this->metodo = strtoupper($metodo);
        $this->ruta = '/' . trim($ruta, '/');
    }

    public static function desdeServidor(string $prefijo): self
    {
        $uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        $posicion = strpos($uri, $prefijo);
        $ruta = $posicion === false ? '/' : substr($uri, $posicion + strlen($prefijo));

        return new self((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), rawurldecode($ruta));
    }

    public function metodo(): string
    {
        return $this->metodo;
    }

    public function ruta(): string
    {
        return $this->ruta;
    }

    /** @param array<string, string> $parametros */
    public function conParametrosRuta(array $parametros): self
    {
        $copia = clone $this;
        $copia->parametrosRuta = $parametros;
        return $copia;
    }

    public function parametroRuta(string $nombre): string
    {
        return $this->parametrosRuta[$nombre] ?? '';
    }

    /** Parámetro de la URL (?clave=valor). */
    public function query(string $nombre, $porDefecto = null)
    {
        return $_GET[$nombre] ?? $porDefecto;
    }

    /**
     * Cuerpo de la petición. Las acciones que modifican datos solo aceptan JSON:
     * un formulario de otro sitio no puede enviar JSON, así que esto también protege contra CSRF.
     */
    public function cuerpo(): array
    {
        if ($this->cuerpo !== null) {
            return $this->cuerpo;
        }

        $tipo = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if (!str_contains($tipo, 'application/json')) {
            throw new ApiException('Enviá los datos en formato JSON (Content-Type: application/json).', 415);
        }

        $crudo = file_get_contents('php://input');
        $datos = json_decode($crudo === false || $crudo === '' ? '{}' : $crudo, true);
        if (!is_array($datos)) {
            throw new ApiException('El cuerpo de la petición no es un JSON válido.', 400);
        }

        return $this->cuerpo = $datos;
    }

    public function texto(string $campo, int $largoMaximo = 255): string
    {
        $valor = $this->cuerpo()[$campo] ?? '';
        $valor = is_scalar($valor) ? trim(strip_tags((string) $valor)) : '';

        return function_exists('mb_substr') ? mb_substr($valor, 0, $largoMaximo) : substr($valor, 0, $largoMaximo);
    }

    /** Texto tal cual (sin quitar etiquetas ni espacios), útil para contraseñas. */
    public function crudo(string $campo): string
    {
        $valor = $this->cuerpo()[$campo] ?? '';
        return is_scalar($valor) ? (string) $valor : '';
    }
}
