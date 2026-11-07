<?php

declare(strict_types=1);

/**
 * Aplica las migraciones pendientes de database/migraciones/, en orden.
 *
 * Uso:
 *   php bin/migrar.php               aplica lo que falte
 *   php bin/migrar.php --con-datos   además carga los datos de ejemplo si la base está vacía
 *   php bin/migrar.php --esperar     espera hasta 60 segundos a que MySQL esté listo (Docker)
 *
 * Cada migración se aplica una sola vez y queda anotada en tbl_migraciones. Pueden ser
 * archivos .sql o .php (que devuelven una función que recibe la conexión).
 * También se pueden pedir los datos de ejemplo con la variable PICCOLO_DATOS_EJEMPLO=1.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../src/Core/bootstrap.php';

$opciones = array_slice($argv, 1);
$conDatos = in_array('--con-datos', $opciones, true) || piccolo_env('PICCOLO_DATOS_EJEMPLO') === '1';
$dirMigraciones = __DIR__ . '/../database/migraciones';
$archivoDatos = __DIR__ . '/../database/datos_ejemplo.sql';

if (in_array('--esperar', $opciones, true)) {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        piccolo_env('MYSQL_HOST'),
        piccolo_env('MYSQL_PORT') ?: '3306',
        piccolo_env('MYSQL_DATABASE')
    );
    for ($intento = 1; ; $intento++) {
        try {
            new PDO($dsn, (string) piccolo_env('MYSQL_USER'), (string) piccolo_env('MYSQL_PASSWORD'));
            break;
        } catch (PDOException $e) {
            if ($intento >= 30) {
                fwrite(STDERR, "MySQL no respondió a tiempo: {$e->getMessage()}\n");
                exit(1);
            }
            sleep(2);
        }
    }
}

require_once __DIR__ . '/../src/Core/database.php'; // define $conexion

/** Ejecuta un archivo .sql con varias sentencias y avisa si falla cualquiera de ellas. */
function ejecutarSql(PDO $conexion, string $archivo): void
{
    $consulta = $conexion->query((string) file_get_contents($archivo));
    while ($consulta->nextRowset()) {
        // recorrer los resultados hace que salte el error de la sentencia que haya fallado
    }
    $consulta->closeCursor();
}

function tablaExiste(PDO $conexion, string $tabla): bool
{
    $consulta = $conexion->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $consulta->execute([$tabla]);
    return (bool) $consulta->fetchColumn();
}

$archivos = glob($dirMigraciones . '/[0-9][0-9][0-9]_*.{sql,php}', GLOB_BRACE) ?: [];
sort($archivos);

// Una base que ya existía antes de las migraciones tiene el esquema inicial aplicado
$baseExistente = !tablaExiste($conexion, 'tbl_migraciones') && tablaExiste($conexion, 'tbl_menu');

$conexion->exec(
    'CREATE TABLE IF NOT EXISTS tbl_migraciones (
        nombre VARCHAR(190) NOT NULL,
        aplicada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (nombre)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
);

$registrar = $conexion->prepare('INSERT INTO tbl_migraciones (nombre) VALUES (?)');
if ($baseExistente && $archivos !== []) {
    $registrar->execute([basename($archivos[0])]);
    echo "Base existente: se toma " . basename($archivos[0]) . " como ya aplicada.\n";
}

$aplicadas = $conexion->query('SELECT nombre FROM tbl_migraciones')->fetchAll(PDO::FETCH_COLUMN);
$nuevas = 0;

foreach ($archivos as $archivo) {
    $nombre = basename($archivo);
    if (in_array($nombre, $aplicadas, true)) {
        continue;
    }

    echo "Aplicando {$nombre}... ";
    try {
        if (str_ends_with($nombre, '.php')) {
            $migracion = require $archivo;
            $migracion($conexion);
        } else {
            ejecutarSql($conexion, $archivo);
        }
    } catch (Throwable $e) {
        echo "ERROR\n";
        fwrite(STDERR, $e->getMessage() . "\n");
        exit(1);
    }
    $registrar->execute([$nombre]);
    $nuevas++;
    echo "listo\n";
}

echo $nuevas === 0 ? "La base ya estaba al día.\n" : "{$nuevas} migración(es) aplicada(s).\n";

if ($conDatos) {
    $tieneDatos = (int) $conexion->query('SELECT COUNT(*) FROM tbl_menu')->fetchColumn() > 0;
    if ($tieneDatos) {
        echo "La base ya tiene datos: no se cargan los de ejemplo.\n";
    } else {
        ejecutarSql($conexion, $archivoDatos);
        echo "Datos de ejemplo cargados.\n";
    }
}
