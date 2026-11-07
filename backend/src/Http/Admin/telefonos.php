<?php

declare(strict_types=1);

if (!function_exists('obtenerCatalogoTelefonos')) {
    /**
     * Devuelve el catálogo de códigos telefónicos disponibles para los formularios internos.
     *
     * @return array<int|string, array{bandera: string, pais: string, longitudes: int[]}> (PHP guarda los códigos numéricos como claves int)
     */
    function obtenerCatalogoTelefonos(): array
    {
        return [
            '54' => ['bandera' => '🇦🇷', 'pais' => '', 'longitudes' => [10]],
            '598' => ['bandera' => '🇺🇾', 'pais' => '', 'longitudes' => [8, 9]],
            '55' => ['bandera' => '🇧🇷', 'pais' => '', 'longitudes' => [10, 11]],
            '56' => ['bandera' => '🇨🇱', 'pais' => '', 'longitudes' => [9]],
            '595' => ['bandera' => '🇵🇾', 'pais' => '', 'longitudes' => [9]],
            '591' => ['bandera' => '🇧🇴', 'pais' => '', 'longitudes' => [8]],
            '51' => ['bandera' => '🇵🇪', 'pais' => '', 'longitudes' => [9]],
            '1' => ['bandera' => '🇺🇸', 'pais' => '', 'longitudes' => [10]],
            '34' => ['bandera' => '🇪🇸', 'pais' => '', 'longitudes' => [9]],
        ];
    }
}

if (!function_exists('normalizarCodigoTelefono')) {
    function normalizarCodigoTelefono(string $codigo): string
    {
        return preg_replace('/[^\d]/', '', $codigo) ?: '';
    }
}

if (!function_exists('dividirTelefonoEnCodigoYNumero')) {
    /**
     * @return array{codigo: string, numero: string}
     */
    function dividirTelefonoEnCodigoYNumero(?string $telefono): array
    {
        $catalogo = obtenerCatalogoTelefonos();
        $codigoDefecto = '54';

        if (!is_string($telefono) || $telefono === '') {
            return ['codigo' => $codigoDefecto, 'numero' => ''];
        }

        $soloDigitos = preg_replace('/[^\d]/', '', ltrim($telefono, '+')) ?: '';

        foreach ($catalogo as $codigo => $datos) {
            $codigoStr = (string) $codigo;
            if (strncmp($soloDigitos, $codigoStr, strlen($codigoStr)) === 0) {
                return [
                    'codigo' => $codigoStr,
                    'numero' => substr($soloDigitos, strlen($codigoStr)),
                ];
            }
        }


        return ['codigo' => $codigoDefecto, 'numero' => $soloDigitos];
    }
}
