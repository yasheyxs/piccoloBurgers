<?php

function requireConnection(PDO $conn = null): PDO
{
    if ($conn === null) {
        throw new RuntimeException('No se encontró la conexión a la base de datos.');
    }

    return $conn;
}