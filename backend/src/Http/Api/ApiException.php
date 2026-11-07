<?php

namespace Piccolo\Http\Api;

use RuntimeException;

/**
 * Error esperado de la API: se responde al cliente con el mensaje y el código HTTP indicados.
 */
class ApiException extends RuntimeException
{
    /** @var array<string, string> */
    private array $errores;

    /**
     * @param array<string, string> $errores Errores por campo, por ejemplo ['email' => 'Ingresá un email válido.']
     */
    public function __construct(string $mensaje, int $status = 400, array $errores = [])
    {
        parent::__construct($mensaje, $status);
        $this->errores = $errores;
    }

    public function status(): int
    {
        return $this->getCode() ?: 400;
    }

    /** @return array<string, string> */
    public function errores(): array
    {
        return $this->errores;
    }

    /**
     * @param array<string, string> $errores
     */
    public static function validacion(array $errores, string $mensaje = 'Revisá los datos ingresados.'): self
    {
        return new self($mensaje, 422, $errores);
    }

    public static function noAutenticado(): self
    {
        return new self('Tenés que iniciar sesión.', 401);
    }

    public static function noEncontrado(string $mensaje = 'No encontramos lo que buscabas.'): self
    {
        return new self($mensaje, 404);
    }
}
