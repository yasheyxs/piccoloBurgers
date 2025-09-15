<?php
if (!isset($_SESSION["cliente"])) {
    header("Location: login_cliente.php");
    exit;
}

$errores = [];
$mensaje_error = "";
$mensaje_exito = "";
$datos_guardados_exitosamente = false;

$cliente = $_SESSION["cliente"];
$cliente_id = $cliente["id"];

$stmt = $conexion->prepare("SELECT nombre, telefono, email, fecha_registro, puntos FROM tbl_clientes WHERE ID = ?");
$stmt->execute([$cliente_id]);
$datos = $stmt->fetch(PDO::FETCH_ASSOC);

if (isset($_POST["guardar_datos"])) {
    $nuevo_nombre = trim($_POST["nombre"]);
    $codigo = trim($_POST["codigo_pais"] ?? "");
    $numero = trim($_POST["telefono"] ?? "");
    $nuevo_email = trim($_POST["email"]);

    $nuevo_telefono = validarTelefono($codigo, $numero);

    if (!$nuevo_telefono) {
        $errores[] = "Número de teléfono inválido.";
    }

    if ($nuevo_email !== "") {
        if (!filter_var($nuevo_email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = "Email inválido.";
        }
    }

    if (empty($errores)) {
        $verificar = $conexion->prepare("SELECT COUNT(*) FROM tbl_clientes WHERE telefono = ? AND ID != ?");
        $verificar->execute([$nuevo_telefono, $cliente_id]);
        $existe = $verificar->fetchColumn();

        if ($existe > 0) {
            $errores[] = "El número de teléfono ya está registrado por otro cliente.";
        }
    }

    if (empty($errores)) {
        $valor_email = $nuevo_email === "" ? null : $nuevo_email;

        $actualizar = $conexion->prepare("UPDATE tbl_clientes SET nombre = ?, telefono = ?, email = ? WHERE ID = ?");
        $actualizar->execute([$nuevo_nombre, $nuevo_telefono, $valor_email, $cliente_id]);

        $_SESSION["cliente"]["nombre"] = $nuevo_nombre;
        $_SESSION["cliente"]["telefono"] = $nuevo_telefono;
        $_SESSION["cliente"]["email"] = $valor_email;

        $mensaje_exito = "Datos actualizados correctamente.";
        $datos_guardados_exitosamente = true;
    }
}

if (isset($_POST["guardar_password"])) {
    $actual = trim($_POST["password_actual"] ?? "");
    $nueva = trim($_POST["password_nueva"] ?? "");
    $confirmar = trim($_POST["password_confirmar"] ?? "");

    if ($actual === "") {
        $errores[] = "Ingresá tu contraseña actual.";
    }

    if ($confirmar === "") {
        $errores[] = "Confirmá la nueva contraseña.";
    }

    if ($nueva !== "" && $confirmar !== "" && $nueva !== $confirmar) {
        $errores[] = "Las contraseñas no coinciden.";
    }

    if ($nueva !== "" && strlen($nueva) < 8) {
        $errores[] = "La nueva contraseña debe tener al menos 8 caracteres.";
    }

    if (empty($errores)) {
        $consulta = $conexion->prepare("SELECT password FROM tbl_clientes WHERE ID = ?");
        $consulta->execute([$cliente_id]);
        $cliente = $consulta->fetch(PDO::FETCH_ASSOC);

        $hashAlmacenado = $cliente["password"];
        $esHashModerno = strlen($hashAlmacenado) > 30 && str_starts_with($hashAlmacenado, '$2y$');

        $valido = $esHashModerno
            ? password_verify($actual, $hashAlmacenado)
            : md5($actual) === $hashAlmacenado;

        if (!$valido) {
            $errores[] = "La contraseña actual es incorrecta.";
        } else {
            $nuevoHash = password_hash($nueva, PASSWORD_BCRYPT);
            $update = $conexion->prepare("UPDATE tbl_clientes SET password = ? WHERE ID = ?");
            $update->execute([$nuevoHash, $cliente_id]);

            $mensaje_exito = "Contraseña actualizada correctamente.";
        }
    }
}

if ($datos_guardados_exitosamente) {
    header("Location: perfil_cliente.php");
    exit;
}
