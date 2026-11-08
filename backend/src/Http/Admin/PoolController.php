<?php

declare(strict_types=1);

namespace Piccolo\Http\Admin;

use PDO;
use PDOException;
use Piccolo\Http\Api\ApiException;
use Piccolo\Repositories\PoolRepositorio;
use RuntimeException;

/**
 * Reglas del pool: la jornada (abrir y cerrar), la fila de cada mesa y los parámetros
 * (máximo de fichas por turno y valor de cada ficha).
 * La usan la pantalla del pool (por seccion/pool/api.php) y la API (/api/v1/admin/pool).
 *
 * Los montos y las fichas vendidas no se muestran hasta que quien usa el panel confirma con su
 * contraseña ("información sensible"), porque la pantalla suele estar a la vista del público.
 */
final class PoolController
{
    public const MESAS = ['azul', 'rojo'];
    private const SENSIBLES = ['vendidas', 'consumidas', 'montoVendido'];
    private const NO_EXISTE = 'No encontramos ese turno en la jornada activa.';

    public function __construct(private PoolRepositorio $repositorio, private PDO $conexion)
    {
        require_once \piccolo_path('src/Services/pool_schema.php');
        require_once \piccolo_path('src/Services/bebidas_schema.php');
        require_once \piccolo_path('src/Services/Security/password_utils.php');
        require_once \piccolo_path('src/Services/Security/limite_intentos.php');
    }

    /** @return array<string, mixed> jornada activa y fila de cada mesa; sin los datos sensibles salvo que se pida */
    public function estado(bool $conSensibles = false): array
    {
        $estado = \poolObtenerEstado($this->conexion);
        if (!$conSensibles) {
            foreach (self::MESAS as $mesa) {
                foreach (self::SENSIBLES as $dato) {
                    $estado['pools'][$mesa]['stats'][$dato] = null;
                }
            }
        }

        return $estado;
    }

    /**
     * Confirma la contraseña de quien usa el panel para mostrar los datos sensibles.
     * Después de varios intentos fallidos se bloquea unos minutos, igual que el ingreso al panel.
     *
     * @return array<string, mixed> el estado completo
     */
    public function mostrarSensibles(int $usuarioId, string $password): array
    {
        $clave = (string) $usuarioId;
        $minutos = \piccoloLoginMinutosBloqueado($this->conexion, 'privacidad', $clave);
        if ($minutos > 0) {
            throw new ApiException("Demasiados intentos. Probá de nuevo en $minutos minuto" . ($minutos === 1 ? '' : 's') . '.', 429);
        }
        if ($password === '') {
            throw ApiException::validacion(['password' => 'Ingresá tu contraseña para continuar.']);
        }

        $stmt = $this->conexion->prepare('SELECT password FROM tbl_usuarios WHERE ID = ?');
        $stmt->execute([$usuarioId]);
        $hash = (string) ($stmt->fetchColumn() ?: '');
        if ($hash === '' || !\passwordCoincideConHash($password, $hash)) {
            \piccoloLoginRegistrarFallo($this->conexion, 'privacidad', $clave);
            throw ApiException::validacion(['password' => 'La contraseña no es correcta.']);
        }
        \piccoloLoginRegistrarExito($this->conexion, 'privacidad', $clave);

        return $this->estado(true);
    }

    public function abrirJornada(): void
    {
        $this->conReglas(fn() => \poolAbrirJornada($this->conexion));
    }

    /** @return array{fichasVendidas: int, bebidasVendidas: int} lo que se vendió en la jornada, para revisar antes de cerrar */
    public function totalesCierre(): array
    {
        return \poolTotalesCierre($this->conexion, $this->jornadaId());
    }

    /** @param array<string, mixed> $totales fichasVendidas / bebidasVendidas corregidos a mano (opcional) */
    public function cerrarJornada(array $totales = []): void
    {
        foreach (['fichasVendidas', 'bebidasVendidas'] as $campo) {
            $valor = $totales[$campo] ?? null;
            if ($valor !== null && $valor !== '' && filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
                throw ApiException::validacion([$campo => 'Tiene que ser un número entero, 0 o más.']);
            }
        }
        $this->conReglas(fn() => \poolCerrarJornada($this->conexion, $totales));
    }

    /** @param array<string, mixed> $datos pool (azul o rojo), nombre, fichas */
    public function agregar(array $datos): void
    {
        $jornada = $this->jornadaId();
        $mesa = $this->mesa($datos['pool'] ?? '');
        $nombre = $this->nombre($datos['nombre'] ?? '');
        $fichas = filter_var(Datos::texto($datos['fichas'] ?? ''), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]]);
        if ($fichas === false) {
            throw ApiException::validacion(['fichas' => 'Ingresá una cantidad de fichas entre 1 y 99.']);
        }

        $config = \poolObtenerConfiguracion($this->conexion);
        $this->repositorio->agregar($jornada, $mesa, $nombre, $fichas, (int) $config['max_fichas_por_registro'], (float) $config['valor_ficha']);
    }

    public function renombrar(int $id, array $datos): void
    {
        $turno = $this->turno($id);
        $this->repositorio->renombrar((int) $turno['id'], $this->nombre($datos['nombre'] ?? ''));
    }

    /** Usa una ficha del turno (cuando la persona juega). */
    public function consumir(int $id): void
    {
        $turno = $this->turno($id);
        if ((int) $turno['fichas_consumidas'] >= (int) $turno['fichas_total']) {
            throw new ApiException('Este turno ya usó todas sus fichas.', 409);
        }
        $this->repositorio->cambiarFichasUsadas($id, +1);
    }

    /** Devuelve una ficha usada por error. */
    public function revertir(int $id): void
    {
        $turno = $this->turno($id);
        if ((int) $turno['fichas_consumidas'] <= 0) {
            throw new ApiException('Este turno todavía no usó fichas.', 409);
        }
        $this->repositorio->cambiarFichasUsadas($id, -1);
    }

    public function transferir(int $id, array $datos): void
    {
        $turno = $this->turno($id);
        $destino = $this->mesa($datos['pool'] ?? ($datos['poolDestino'] ?? ''));
        if ($destino !== $turno['pool']) {
            $this->repositorio->transferir($id, (int) $turno['jornada_id'], $destino);
        }
    }

    public function mover(int $id, array $datos): void
    {
        $direccion = Datos::texto($datos['direccion'] ?? '');
        if (!in_array($direccion, ['arriba', 'abajo'], true)) {
            throw ApiException::validacion(['direccion' => 'La dirección tiene que ser "arriba" o "abajo".']);
        }
        $this->repositorio->mover($this->turno($id), $direccion);
    }

    /** @param array<string, mixed> $datos pool e ids (los turnos de esa mesa en el orden nuevo) */
    public function reordenar(array $datos): void
    {
        $ids = $datos['ids'] ?? null;
        if (!is_array($ids) || array_filter($ids, static fn($id): bool => Datos::id($id) <= 0)) {
            throw ApiException::validacion(['ids' => 'El orden enviado no es válido.']);
        }
        $this->repositorio->reordenar($this->jornadaId(), $this->mesa($datos['pool'] ?? ''), array_map([Datos::class, 'id'], $ids));
    }

    /** @return array{max_fichas_por_registro: int, valor_ficha: float, actualizado: ?string} */
    public function configuracion(): array
    {
        $config = \poolObtenerConfiguracion($this->conexion);

        return [
            'max_fichas_por_registro' => (int) $config['max_fichas_por_registro'],
            'valor_ficha' => (float) $config['valor_ficha'],
            'actualizado' => $config['actualizado_en'] !== null ? (string) $config['actualizado_en'] : null,
        ];
    }

    /**
     * @param array<string, mixed> $datos max_fichas_por_registro, valor_ficha (acepta coma decimal)
     * @return array{max_fichas_por_registro: int, valor_ficha: float, actualizado: ?string}
     */
    public function guardarConfiguracion(array $datos): array
    {
        $maximo = filter_var(Datos::texto($datos['max_fichas_por_registro'] ?? ''), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 99]]);
        $valor = Datos::decimal($datos['valor_ficha'] ?? null);
        $errores = [];
        if ($maximo === false) {
            $errores['max_fichas_por_registro'] = 'El máximo de fichas por turno tiene que estar entre 1 y 99.';
        }
        if ($valor === null || $valor <= 0 || $valor > 10000000) {
            $errores['valor_ficha'] = 'El valor de la ficha tiene que ser mayor a 0.';
        }
        if ($errores) {
            throw ApiException::validacion($errores);
        }

        \poolActualizarConfiguracion($this->conexion, (int) $maximo, round((float) $valor, 2));

        return $this->configuracion();
    }

    private function jornadaId(): int
    {
        $jornada = \poolJornadaActiva($this->conexion);
        if (!$jornada) {
            throw new ApiException('Abrí una jornada antes de operar.', 409);
        }

        return (int) $jornada['id'];
    }

    /** @return array<string, mixed> */
    private function turno(int $id): array
    {
        $turno = $id > 0 ? $this->repositorio->turno($id, $this->jornadaId()) : null;
        if ($turno === null) {
            throw ApiException::noEncontrado(self::NO_EXISTE);
        }

        return $turno;
    }

    private function mesa(mixed $valor): string
    {
        $mesa = Datos::texto($valor);
        if (!in_array($mesa, self::MESAS, true)) {
            throw ApiException::validacion(['pool' => 'La mesa tiene que ser "azul" o "rojo".']);
        }

        return $mesa;
    }

    private function nombre(mixed $valor): string
    {
        $nombre = Datos::texto($valor);
        if ($nombre === '' || mb_strlen($nombre) > 120) {
            throw ApiException::validacion(['nombre' => 'Ingresá un nombre o identificación (hasta 120 caracteres).']);
        }

        return $nombre;
    }

    /**
     * Las funciones de jornada avisan con RuntimeException cuando algo no se puede hacer
     * ("ya hay una jornada abierta"); eso se responde como 409. Un error de la base no se muestra.
     */
    private function conReglas(callable $accion): void
    {
        try {
            $accion();
        } catch (PDOException $error) {
            throw $error;
        } catch (RuntimeException $error) {
            throw new ApiException($error->getMessage(), 409);
        }
    }
}
