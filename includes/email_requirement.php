<?php

function clienteDebeRegistrarEmail(): bool
{
    if (!isset($_SESSION['cliente'])) {
        return false;
    }

    $email = $_SESSION['cliente']['email'] ?? '';

    return trim((string) $email) === '';
}

function registrarAvisoEmailObligatorio(): string
{
    $mensaje = 'Necesitamos que asocies un email a tu cuenta para continuar. Si preferís no hacerlo, podés cerrar sesión.';
    $_SESSION['email_obligatorio_mensaje'] = $mensaje;

    return $mensaje;
}

function limpiarAvisoEmailObligatorio(): void
{
    unset($_SESSION['email_obligatorio_mensaje']);
}

function enforceEmailRequirement(string $redirectPath = 'cliente/perfil_cliente.php', bool $allowCurrentPage = false): void
{
    if (!clienteDebeRegistrarEmail()) {
        limpiarAvisoEmailObligatorio();
        return;
    }

    registrarAvisoEmailObligatorio();

    if ($allowCurrentPage) {
        return;
    }

    $currentScript = basename($_SERVER['PHP_SELF'] ?? '');
    $redirectScript = basename($redirectPath);

    if ($currentScript === $redirectScript) {
        return;
    }

    header('Location: ' . $redirectPath);
    exit;
}