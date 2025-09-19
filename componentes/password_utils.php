<?php

/**
 * Devuelve el mensaje estándar de requisitos de contraseña para usar en validaciones y formularios.
 */
function mensajeRequisitosPassword(): string
{
    return 'La contraseña debe tener al menos 8 caracteres, con mayúsculas, minúsculas, números y símbolos.';
}

/**
 * Verifica si una contraseña cumple con los requisitos de complejidad establecidos.
 */
function passwordCumpleRequisitos(string $password): bool
{
    return (bool) preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password);
}

/**
 * Determina si el hash almacenado utiliza algoritmos modernos compatibles con password_verify.
 */
function esHashPasswordModerno(string $hash): bool
{
    return str_starts_with($hash, '$2y$')
        || str_starts_with($hash, '$2a$')
        || str_starts_with($hash, '$2b$')
        || str_starts_with($hash, '$argon2');
}

/**
 * Compara una contraseña en texto plano con un hash almacenado. Soporta hashes heredados en MD5.
 */
function passwordCoincideConHash(string $passwordPlano, string $hashAlmacenado): bool
{
    if ($hashAlmacenado === '') {
        return false;
    }

    if (esHashPasswordModerno($hashAlmacenado)) {
        return password_verify($passwordPlano, $hashAlmacenado);
    }

    return md5($passwordPlano) === $hashAlmacenado;
}