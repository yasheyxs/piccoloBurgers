<?php

namespace Piccolo\Http\Api;

use PDO;
use PDOException;
use Throwable;

/**
 * Cuenta del cliente: registro, inicio y cierre de sesión, perfil y contraseña.
 */
final class CuentaController
{
    public function __construct(private PDO $conexion)
    {
    }

    /** POST /cuenta/registro {nombre, codigoPais, telefono, email, password, confirmar} */
    public function registrar(Peticion $peticion): void
    {
        $nombre = $peticion->texto('nombre', 120);
        $email = $peticion->texto('email', 160);
        $password = $peticion->crudo('password');
        $telefono = \validarTelefono($peticion->texto('codigoPais', 5), $peticion->texto('telefono', 30));

        $errores = [];
        if ($nombre === '') {
            $errores['nombre'] = 'Ingresá tu nombre.';
        }
        if (!$telefono) {
            $errores['telefono'] = 'El número ingresado no parece válido. Verificá que esté completo y sin espacios.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores['email'] = 'Ingresá un email válido.';
        }
        if (!\passwordCumpleRequisitos($password)) {
            $errores['password'] = \mensajeRequisitosPassword();
        } elseif ($password !== $peticion->crudo('confirmar')) {
            $errores['confirmar'] = 'Las contraseñas no coinciden.';
        }
        if ($errores) {
            throw ApiException::validacion($errores);
        }

        $this->verificarDatosLibres($telefono, $email);

        try {
            $stmt = $this->conexion->prepare('INSERT INTO tbl_clientes (nombre, telefono, email, password) VALUES (?, ?, ?, ?)');
            $stmt->execute([$nombre, $telefono, $email, \generarHashPassword($password)]);
        } catch (PDOException $error) {
            if ($error->getCode() === '23000') {
                throw new ApiException('Ya existe una cuenta registrada con los datos proporcionados.', 409);
            }
            throw $error;
        }

        $cliente = $this->buscarCliente((int) $this->conexion->lastInsertId());
        Sesion::guardarCliente($cliente);

        Respuesta::creado(Formato::cliente($cliente));
    }

    /** POST /cuenta/login {codigoPais, telefono, password} */
    public function iniciarSesion(Peticion $peticion): void
    {
        $telefono = \validarTelefono($peticion->texto('codigoPais', 5), $peticion->texto('telefono', 30));
        $password = $peticion->crudo('password');

        if (!$telefono || $password === '') {
            throw ApiException::validacion([], 'Completá teléfono y contraseña.');
        }

        $stmt = $this->conexion->prepare('SELECT * FROM tbl_clientes WHERE telefono = ?');
        $stmt->execute([$telefono]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        // Mismo mensaje si el teléfono no existe o la contraseña no coincide
        if (!$cliente || !\passwordCoincideConHash($password, (string) $cliente['password'])) {
            throw new ApiException('Teléfono o contraseña incorrectos.', 401);
        }

        if (\passwordDebeRehash((string) $cliente['password'])) {
            try {
                $stmt = $this->conexion->prepare('UPDATE tbl_clientes SET password = ? WHERE ID = ?');
                $stmt->execute([\generarHashPassword($password), $cliente['ID']]);
            } catch (Throwable $error) {
                error_log('No se pudo actualizar el hash del cliente ' . $cliente['ID'] . ': ' . $error->getMessage());
            }
        }

        Sesion::guardarCliente($cliente);
        Respuesta::ok(Formato::cliente($cliente));
    }

    /** POST /cuenta/logout */
    public function cerrarSesion(Peticion $peticion): void
    {
        Sesion::cerrar();
        Respuesta::ok(null);
    }

    /** GET /cuenta/sesion — el cliente con sesión iniciada, o null (sin error) si no hay sesión. */
    public function sesion(Peticion $peticion): void
    {
        $sesion = Sesion::cliente();
        if ($sesion === null) {
            Respuesta::ok(null);
            return;
        }

        $this->ver($peticion);
    }

    /** GET /cuenta */
    public function ver(Peticion $peticion): void
    {
        $sesion = Sesion::clienteObligatorio();
        Respuesta::ok(Formato::cliente($this->buscarCliente((int) $sesion['id'])));
    }

    /** PUT /cuenta {nombre, codigoPais, telefono, email} */
    public function actualizar(Peticion $peticion): void
    {
        $sesion = Sesion::clienteObligatorio();
        $id = (int) $sesion['id'];

        $nombre = $peticion->texto('nombre', 120);
        $email = $peticion->texto('email', 160);
        $telefono = \validarTelefono($peticion->texto('codigoPais', 5), $peticion->texto('telefono', 30));

        $errores = [];
        if ($nombre === '') {
            $errores['nombre'] = 'Ingresá tu nombre.';
        }
        if (!$telefono) {
            $errores['telefono'] = 'Número de teléfono inválido.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores['email'] = 'Email inválido.';
        }
        if ($errores) {
            throw ApiException::validacion($errores);
        }

        $this->verificarDatosLibres($telefono, $email, $id);

        $stmt = $this->conexion->prepare('UPDATE tbl_clientes SET nombre = ?, telefono = ?, email = ? WHERE ID = ?');
        $stmt->execute([$nombre, $telefono, $email, $id]);

        $cliente = $this->buscarCliente($id);
        $_SESSION['cliente'] = ['id' => $id, 'nombre' => $nombre, 'telefono' => $telefono, 'email' => $email];
        unset($_SESSION['email_obligatorio_mensaje']);

        Respuesta::ok(Formato::cliente($cliente));
    }

    /** PUT /cuenta/password {actual, nueva, confirmar} */
    public function cambiarPassword(Peticion $peticion): void
    {
        $sesion = Sesion::clienteObligatorio();
        $actual = $peticion->crudo('actual');
        $nueva = $peticion->crudo('nueva');

        $errores = [];
        if ($actual === '') {
            $errores['actual'] = 'Ingresá tu contraseña actual.';
        }
        if (!\passwordCumpleRequisitos($nueva)) {
            $errores['nueva'] = \mensajeRequisitosPassword();
        } elseif ($nueva !== $peticion->crudo('confirmar')) {
            $errores['confirmar'] = 'Las contraseñas no coinciden.';
        }
        if ($errores) {
            throw ApiException::validacion($errores);
        }

        $hash = (string) $this->buscarCliente((int) $sesion['id'])['password'];
        if (!\passwordCoincideConHash($actual, $hash)) {
            throw ApiException::validacion(['actual' => 'La contraseña actual es incorrecta.']);
        }
        if (\passwordCoincideConHash($nueva, $hash)) {
            throw ApiException::validacion(['nueva' => 'La nueva contraseña debe ser distinta de la actual.']);
        }

        $stmt = $this->conexion->prepare('UPDATE tbl_clientes SET password = ? WHERE ID = ?');
        $stmt->execute([\generarHashPassword($nueva), $sesion['id']]);

        Respuesta::ok(['mensaje' => 'Contraseña actualizada correctamente.']);
    }

    /** POST /cuenta/recuperar {email} — envía un enlace para crear una nueva contraseña. */
    public function recuperar(Peticion $peticion): void
    {
        $email = $peticion->texto('email', 160);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ApiException::validacion(['email' => 'Ingresá un email válido.']);
        }

        $stmt = $this->conexion->prepare('SELECT ID, nombre FROM tbl_clientes WHERE email = ?');
        $stmt->execute([$email]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cliente) {
            $token = bin2hex(random_bytes(32));
            $stmt = $this->conexion->prepare('UPDATE tbl_clientes SET reset_token = ?, token_expira = ? WHERE ID = ?');
            $stmt->execute([$token, date('Y-m-d H:i:s', strtotime('+30 minutes')), $cliente['ID']]);

            $this->enviarCorreoRecuperacion($email, (string) $cliente['nombre'], $token);
        }

        // La respuesta es la misma exista o no la cuenta, para no revelar qué emails están registrados.
        Respuesta::ok(['mensaje' => 'Si el email está registrado, te enviamos un enlace para crear una nueva contraseña. Vence en 30 minutos.']);
    }

    /** POST /cuenta/restablecer {token, password, confirmar} */
    public function restablecer(Peticion $peticion): void
    {
        $token = $peticion->texto('token', 100);
        $password = $peticion->crudo('password');

        if (!\passwordCumpleRequisitos($password)) {
            throw ApiException::validacion(['password' => \mensajeRequisitosPassword()]);
        }
        if ($password !== $peticion->crudo('confirmar')) {
            throw ApiException::validacion(['confirmar' => 'Las contraseñas no coinciden.']);
        }

        $stmt = $this->conexion->prepare('SELECT ID FROM tbl_clientes WHERE reset_token = ? AND token_expira > NOW()');
        $stmt->execute([$token]);
        $clienteId = $token !== '' ? $stmt->fetchColumn() : false;
        if (!$clienteId) {
            throw new ApiException('El enlace no es válido o ya venció. Pedí uno nuevo.', 410);
        }

        $stmt = $this->conexion->prepare('UPDATE tbl_clientes SET password = ?, reset_token = NULL, token_expira = NULL WHERE ID = ?');
        $stmt->execute([\generarHashPassword($password), $clienteId]);

        Respuesta::ok(['mensaje' => 'Listo, ya podés iniciar sesión con tu nueva contraseña.']);
    }

    private function buscarCliente(int $id): array
    {
        $stmt = $this->conexion->prepare('SELECT * FROM tbl_clientes WHERE ID = ?');
        $stmt->execute([$id]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cliente) {
            Sesion::cerrar();
            throw ApiException::noAutenticado();
        }

        return $cliente;
    }

    private function verificarDatosLibres(string $telefono, string $email, int $exceptoId = 0): void
    {
        $stmt = $this->conexion->prepare('SELECT COUNT(*) FROM tbl_clientes WHERE telefono = ? AND ID != ?');
        $stmt->execute([$telefono, $exceptoId]);
        if ($stmt->fetchColumn() > 0) {
            throw new ApiException('Ya existe una cuenta registrada con ese teléfono.', 409, ['telefono' => 'Ese teléfono ya está registrado.']);
        }

        $stmt = $this->conexion->prepare('SELECT COUNT(*) FROM tbl_clientes WHERE email = ? AND ID != ?');
        $stmt->execute([$email, $exceptoId]);
        if ($stmt->fetchColumn() > 0) {
            throw new ApiException('Ya existe una cuenta registrada con ese email.', 409, ['email' => 'Ese email ya está registrado.']);
        }
    }

    private function enviarCorreoRecuperacion(string $email, string $nombre, string $token): void
    {
        // Mientras no exista la pantalla en el frontend, el enlace usa la del sitio actual.
        $base = (string) (piccolo_env('RESET_PASSWORD_URL') ?: \piccolo_admin_base_url() . 'password/reset_password.php');
        $enlace = $base . (str_contains($base, '?') ? '&' : '?') . http_build_query(['token' => $token, 'tipo' => 'cliente']);

        try {
            require_once \piccolo_path('config/mailer.php');
            $mailer = \crearMailer();
            $mailer->addAddress($email, $nombre);
            $mailer->isHTML(true);
            $mailer->Subject = 'Recuperación de contraseña - Piccolo Burgers';
            $texto = "Hola $nombre,\n\nRecibimos un pedido para restablecer tu contraseña de Piccolo Burgers.\n"
                . "Podés crear una nueva ingresando a este enlace dentro de los próximos 30 minutos:\n$enlace\n\n"
                . "Si no fuiste vos, podés ignorar este mensaje.";
            $mailer->Body = nl2br(htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'));
            $mailer->AltBody = $texto;
            $mailer->send();
        } catch (Throwable $error) {
            error_log('No se pudo enviar el correo de recuperación (API): ' . $error->getMessage());
        }
    }
}
