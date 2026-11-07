<?php

namespace Piccolo\Http\Api;

use Throwable;

/**
 * Enrutador mínimo: asocia "MÉTODO /ruta/{parametro}" con una función.
 */
final class Router
{
    /** @var array<int, array{metodo: string, patron: string, nombres: string[], accion: callable}> */
    private array $rutas = [];

    public function get(string $ruta, callable $accion): void
    {
        $this->agregar('GET', $ruta, $accion);
    }

    public function post(string $ruta, callable $accion): void
    {
        $this->agregar('POST', $ruta, $accion);
    }

    public function put(string $ruta, callable $accion): void
    {
        $this->agregar('PUT', $ruta, $accion);
    }

    public function delete(string $ruta, callable $accion): void
    {
        $this->agregar('DELETE', $ruta, $accion);
    }

    private function agregar(string $metodo, string $ruta, callable $accion): void
    {
        $nombres = [];
        $patron = preg_replace_callback('#\{(\w+)\}#', function ($m) use (&$nombres) {
            $nombres[] = $m[1];
            return '([^/]+)';
        }, '/' . trim($ruta, '/'));

        $this->rutas[] = [
            'metodo' => $metodo,
            'patron' => '#^' . $patron . '$#',
            'nombres' => $nombres,
            'accion' => $accion,
        ];
    }

    public function despachar(Peticion $peticion): void
    {
        $metodosPermitidos = [];

        foreach ($this->rutas as $ruta) {
            if (!preg_match($ruta['patron'], $peticion->ruta(), $coincidencias)) {
                continue;
            }

            if ($ruta['metodo'] !== $peticion->metodo()) {
                $metodosPermitidos[] = $ruta['metodo'];
                continue;
            }

            $parametros = array_combine($ruta['nombres'], array_slice($coincidencias, 1)) ?: [];

            try {
                ($ruta['accion'])($peticion->conParametrosRuta($parametros));
            } catch (ApiException $error) {
                Respuesta::error($error->getMessage(), $error->status(), $error->errores());
            } catch (Throwable $error) {
                error_log(sprintf('API %s %s: %s en %s:%d', $peticion->metodo(), $peticion->ruta(), $error->getMessage(), $error->getFile(), $error->getLine()));
                Respuesta::error('Ocurrió un error inesperado. Intentá nuevamente en unos minutos.', 500);
            }
            return;
        }

        if ($metodosPermitidos) {
            header('Allow: ' . implode(', ', array_unique($metodosPermitidos)));
            Respuesta::error('Método no permitido para esta ruta.', 405);
            return;
        }

        Respuesta::error('La ruta ' . $peticion->ruta() . ' no existe.', 404);
    }
}
