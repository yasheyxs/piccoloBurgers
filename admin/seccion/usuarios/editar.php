<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include("../../bd.php");

session_start();
if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
    header("Location: ../../login.php");
    exit();
}

function validarFuerza(string $pass): bool
{
    return (bool) preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $pass);
}

$rolesPermitidos = ["admin", "empleado", "delivery"];
$mensajeError = "";

$txtID = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($txtID <= 0) {
    $_SESSION['mensaje_usuarios'] = 'Usuario no encontrado.';
    header("Location:index.php");
    exit();
}

$stmt = $conexion->prepare("SELECT * FROM tbl_usuarios WHERE ID = :id");
$stmt->bindParam(":id", $txtID, PDO::PARAM_INT);
$stmt->execute();
$usuarioActual = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuarioActual) {
    $_SESSION['mensaje_usuarios'] = 'Usuario no encontrado.';
    header("Location:index.php");
    exit();
}

$usuario = $usuarioActual['usuario'] ?? '';
$correo = $usuarioActual['correo'] ?? '';
$rol = $usuarioActual['rol'] ?? '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario = trim($_POST["usuario"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $rol = $_POST["rol"] ?? "";


    if ($usuario === "" || $correo === "" || $rol === "") {
        $mensajeError = "Completá todos los campos obligatorios.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensajeError = "Ingresá un correo electrónico válido.";
    } elseif (!in_array($rol, $rolesPermitidos, true)) {
        $mensajeError = "Rol inválido.";
    } else {
        // Validar que el nombre de usuario sea único
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM tbl_usuarios WHERE usuario = :usuario AND ID <> :id");
        $stmt->bindParam(":usuario", $usuario);
        $stmt->bindParam(":id", $txtID, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->fetchColumn() > 0) {
            $mensajeError = "Ya existe un usuario con ese nombre.";
        } else {
            // Validar que el correo sea único
            $stmt = $conexion->prepare("SELECT COUNT(*) FROM tbl_usuarios WHERE correo = :correo AND ID <> :id");
            $stmt->bindParam(":correo", $correo);
            $stmt->bindParam(":id", $txtID, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->fetchColumn() > 0) {
                $mensajeError = "Ya existe un usuario con ese correo.";
            } else {
                // Evitar dejar el sistema sin administradores
                if ($usuarioActual['rol'] === 'admin' && $rol !== 'admin') {
                    $stmt = $conexion->prepare("SELECT COUNT(*) FROM tbl_usuarios WHERE rol = 'admin' AND ID <> :id");
                    $stmt->bindParam(":id", $txtID, PDO::PARAM_INT);
                    $stmt->execute();

                    if ($stmt->fetchColumn() === 0) {
                        $mensajeError = "No podés quitar el rol de administrador al último administrador.";
                    }
                }

                if ($mensajeError === "") {
                    $campos = [
                        'usuario' => $usuario,
                        'correo' => $correo,
                        'rol' => $rol,
                        'id' => $txtID,
                    ];

                    // Si se está editando un administrador, validar su contraseña actual
                    if ($usuarioActual['rol'] === 'admin') {
                        $stmt = $conexion->prepare("SELECT password FROM tbl_usuarios WHERE ID = :id");
                        $stmt->bindParam(":id", $txtID, PDO::PARAM_INT); // ID del usuario que se está editando
                        $stmt->execute();
                        $hash = $stmt->fetchColumn();

                        if (!$hash || !password_verify($_POST['password_admin'] ?? '', $hash)) {
                            $mensajeError = "Debés ingresar la contraseña actual de este administrador para confirmar los cambios.";
                        }
                    }


                    $sql = "UPDATE tbl_usuarios SET usuario = :usuario, correo = :correo, rol = :rol";


                    $sql .= " WHERE ID = :id";

                    $stmt = $conexion->prepare($sql);

                    foreach ($campos as $clave => $valor) {
                        $parametro = ":" . $clave;
                        if ($clave === 'id') {
                            $stmt->bindValue($parametro, $valor, PDO::PARAM_INT);
                        } else {
                            $stmt->bindValue($parametro, $valor);
                        }
                    }

                    $stmt->execute();

                    $_SESSION['mensaje_usuarios'] = 'Usuario actualizado correctamente.';
                    header("Location:index.php");
                    exit();
                }
            }
        }
    }
}

include("../../templates/header.php");
?>
<br />
<div class="card">
    <div class="card-header">Editar usuario</div>
    <div class="card-body">
        <?php if ($mensajeError) { ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php } ?>

        <form action="" method="post" novalidate>
            <div class="mb-3">
                <label for="usuario" class="form-label">Nombre de usuario</label>
                <input type="text" class="form-control" name="usuario" id="usuario" value="<?php echo htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="mb-3">
                <label for="correo" class="form-label">Correo</label>
                <input type="email" class="form-control" name="correo" id="correo" value="<?php echo htmlspecialchars($correo, ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="mb-3">
                <label for="rol" class="form-label">Rol</label>
                <select class="form-select" name="rol" id="rol" required>
                    <option value="">Seleccionar rol</option>
                    <option value="admin" <?php echo $rol === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                    <option value="empleado" <?php echo $rol === 'empleado' ? 'selected' : ''; ?>>Empleado</option>
                    <option value="delivery" <?php echo $rol === 'delivery' ? 'selected' : ''; ?>>Delivery</option>
                </select>
            </div>

            <hr>
            <p class="mb-3 text-muted">
                La contraseña no se puede modificar desde aquí.
                Si la olvidaste, podés
                <a href="../../password/recuperar_password_usuario.php">recuperarla desde este enlace</a>.
            </p>

            <?php if ($usuarioActual['rol'] === 'admin') { ?>
                <div class="mb-3">
                    <label for="password_admin" class="form-label">
                        Ingresá la contraseña actual de este administrador para guardar cambios
                    </label>
                    <input type="password" class="form-control" name="password_admin" id="password_admin" required>
                </div>
            <?php } ?>



            <button type="submit" class="btn btn-success">Guardar cambios</button>
            <a class="btn btn-secondary" href="index.php" role="button">Cancelar</a>
        </form>
    </div>
    <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>