<?php

if (!function_exists('piccolo_normalizar_clave_pago')) {
    /**
     * Normaliza un valor recibido para comparaciones de estado de pago.
     */
    function piccolo_normalizar_clave_pago(string $valor): string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return '';
        }

        if (function_exists('mb_strtolower')) {
            $valor = mb_strtolower($valor, 'UTF-8');
        } else {
            $valor = strtolower($valor);
        }

        $valor = str_replace(
            [
                'á', 'à', 'ä', 'â', 'Á', 'À', 'Ä', 'Â',
                'é', 'è', 'ë', 'ê', 'É', 'È', 'Ë', 'Ê',
                'í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î',
                'ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô',
                'ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Ü', 'Û'
            ],
            [
                'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
                'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
                'i', 'i', 'i', 'i', 'i', 'i', 'i', 'i',
                'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
                'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u'
            ],
            $valor
        );

        $valor = preg_replace("/[\\s'\"`’]/u", '', $valor) ?? '';
        $valor = preg_replace('/[^a-z0-9]/', '', $valor) ?? '';

        return $valor;
    }

    /**
     * Obtiene las claves normalizadas admitidas para los estados de pago.
     *
     * @return array{positivos: array<string, bool>, negativos: array<string, bool>}
     */
    function piccolo_obtener_aliases_estado_pago(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $positivos = ['si', 'sí', 'yes', 'pagado', 'pago', 'abonado', '1', 'true'];
        $negativos = ['no', '0', '', 'false', 'pendiente'];

        $mapaPositivos = [];
        foreach ($positivos as $alias) {
            $clave = piccolo_normalizar_clave_pago($alias);
            if ($clave !== '') {
                $mapaPositivos[$clave] = true;
            }
        }

        $mapaNegativos = [];
        foreach ($negativos as $alias) {
            $clave = piccolo_normalizar_clave_pago($alias);
            if ($clave !== '') {
                $mapaNegativos[$clave] = true;
            }
        }

        $cache = [
            'positivos' => $mapaPositivos,
            'negativos' => $mapaNegativos,
        ];

        return $cache;
    }

    /**
     * Devuelve el mapeo de claves normalizadas hacia los valores admitidos en la base de datos.
     */
    function piccolo_obtener_mapa_valores_pago(PDO $conexion): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $aliases = piccolo_obtener_aliases_estado_pago();
        $mapa = [];
        $columna = null;

        try {
            $consulta = $conexion->query("SHOW COLUMNS FROM tbl_pedidos LIKE 'esta_pago'");
            if ($consulta !== false) {
                $columna = $consulta->fetch(PDO::FETCH_ASSOC) ?: null;
            }
        } catch (PDOException $e) {
            $columna = null;
        } catch (Exception $e) {
            $columna = null;
        }

        $tipo = $columna['Type'] ?? '';
        $tipoMin = strtolower((string)$tipo);

        $esEnum = strncmp($tipoMin, 'enum(', 5) === 0;
        $esNumerico = !$esEnum && preg_match('/(tinyint|smallint|int|bigint|bit|bool|boolean|decimal|numeric|float|double)/', $tipoMin);

        $valorPositivo = 'Si';
        $valorNegativo = 'No';
        $valoresEnumerados = [];

        if ($esEnum) {
            if (preg_match_all("/'((?:''|[^'])*)'/", (string)$tipo, $coincidencias)) {
                foreach ($coincidencias[1] as $valorCrudo) {
                    $valorPermitido = str_replace("''", "'", $valorCrudo);
                    $clave = piccolo_normalizar_clave_pago($valorPermitido);

                    if ($clave !== '') {
                        $valoresEnumerados[$clave] = $valorPermitido;
                    }
                }
            }

            foreach (array_keys($aliases['positivos']) as $aliasNormalizado) {
                if (isset($valoresEnumerados[$aliasNormalizado])) {
                    $valorPositivo = $valoresEnumerados[$aliasNormalizado];
                    break;
                }
            }

            foreach (array_keys($aliases['negativos']) as $aliasNormalizado) {
                if (isset($valoresEnumerados[$aliasNormalizado])) {
                    $valorNegativo = $valoresEnumerados[$aliasNormalizado];
                    break;
                }
            }
        } elseif ($esNumerico) {
            $valorPositivo = '1';
            $valorNegativo = '0';
        }

        foreach ($valoresEnumerados as $clave => $valor) {
            $mapa[$clave] = $valor;
        }

        foreach (array_keys($aliases['positivos']) as $aliasNormalizado) {
            $mapa[$aliasNormalizado] = $valorPositivo;
        }

        foreach (array_keys($aliases['negativos']) as $aliasNormalizado) {
            $mapa[$aliasNormalizado] = $valorNegativo;
        }

        if (!isset($mapa['si'])) {
            $mapa['si'] = $valorPositivo;
        }

        if (!isset($mapa['no'])) {
            $mapa['no'] = $valorNegativo;
        }

        $cache = $mapa;

        return $cache;
    }

    /**
     * Resuelve el valor adecuado para guardar en la base de datos según el alias recibido.
     */
    function piccolo_resolver_valor_pago(PDO $conexion, string $valorDeseado): ?string
    {
        $clave = piccolo_normalizar_clave_pago($valorDeseado);
        if ($clave === '') {
            return null;
        }

        $mapa = piccolo_obtener_mapa_valores_pago($conexion);

        return $mapa[$clave] ?? null;
    }

    /**
     * Interpreta un valor de estado de pago y devuelve información útil para mostrarlo.
     *
     * @param mixed       $valorCrudo     Valor recibido desde la base de datos.
     * @param string|null $valorPositivo  Valor canónico para "pagado" (por ejemplo, "Si" o "1").
     * @param string|null $valorNegativo  Valor canónico para "pendiente" (por ejemplo, "No" o "0").
     *
     * @return array{valor: string, texto: string, clase: string, es_pagado: bool}
     */
    function piccolo_interpretar_estado_pago_para_presentacion($valorCrudo, ?string $valorPositivo = null, ?string $valorNegativo = null): array
    {
        $valorTexto = trim((string)$valorCrudo);
        $clave = piccolo_normalizar_clave_pago($valorTexto);
        $aliases = piccolo_obtener_aliases_estado_pago();

        $reconocido = false;
        $esPositivo = false;

        if ($valorTexto !== '') {
            if ($valorPositivo !== null && $valorTexto === $valorPositivo) {
                $esPositivo = true;
                $reconocido = true;
            } elseif ($valorNegativo !== null && $valorTexto === $valorNegativo) {
                $esPositivo = false;
                $reconocido = true;
            }
        }

        if (!$reconocido && $clave !== '') {
            if (isset($aliases['positivos'][$clave])) {
                $esPositivo = true;
                $reconocido = true;
            } elseif (isset($aliases['negativos'][$clave])) {
                $esPositivo = false;
                $reconocido = true;
            }
        }

        if (!$reconocido && $valorTexto !== '') {
            $valorMinuscula = strtolower($valorTexto);
            if (in_array($valorMinuscula, ['1', 'true'], true)) {
                $esPositivo = true;
                $reconocido = true;
            } elseif (in_array($valorMinuscula, ['0', 'false'], true)) {
                $esPositivo = false;
                $reconocido = true;
            }
        }

        if (!$reconocido) {
            $esPositivo = false;
        }

        if ($valorPositivo === null || $valorNegativo === null) {
            $esFormatoNumerico = $valorTexto === '1' || $valorTexto === '0';
            $valorCanonico = $esPositivo
                ? ($esFormatoNumerico ? '1' : 'Si')
                : ($esFormatoNumerico ? '0' : 'No');
        } else {
            $valorCanonico = $esPositivo ? $valorPositivo : $valorNegativo;
        }

        $texto = $esPositivo ? 'Sí' : 'No';
        $clase = $esPositivo ? 'text-success fw-semibold' : 'text-warning fw-semibold';

        return [
            'valor' => $valorCanonico,
            'texto' => $texto,
            'clase' => $clase,
            'es_pagado' => $esPositivo,
        ];
    }
}