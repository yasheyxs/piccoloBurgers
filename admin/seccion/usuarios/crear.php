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

$mensajeError = "";
$usuario = trim($_POST["usuario"] ?? "");
$correo = trim($_POST["correo"] ?? "");
$rol = $_POST["rol"] ?? "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = $_POST["password"] ?? "";
        $confirmar = $_POST["confirmar"] ?? "";
    $rolesPermitidos = ["admin", "empleado", "delivery"];

if ($usuario === "" || $correo === "" || $rol === "" || $password === "" || $confirmar === "") {
        $mensajeError = "Todos los campos son obligatorios.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensajeError = "Ingresá un correo electrónico válido.";
    } elseif (!in_array($rol, $rolesPermitidos, true)) {
        $mensajeError = "Rol inválido.";
    } elseif ($password !== $confirmar) {
        $mensajeError = "Las contraseñas no coinciden.";
    } elseif (!validarFuerza($password)) {
        $mensajeError = "La contraseña debe tener al menos 8 caracteres, con mayúsculas, minúsculas, números y símbolos.";
    } else {
        // Verificar si el nombre de usuario ya existe
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM tbl_usuarios WHERE usuario = :usuario");
        $stmt->bindParam(":usuario", $usuario);
        $stmt->execute();

    if ($stmt->fetchColumn() > 0) {
            $mensajeError = "Ya existe un usuario con ese nombre.";
        } else {
            $stmt = $conexion->prepare("SELECT COUNT(*) FROM tbl_usuarios WHERE correo = :correo");
            $stmt->bindParam(":correo", $correo);
            $stmt->execute();

    if ($stmt->fetchColumn() > 0) {
                $mensajeError = "Ya existe un usuario con ese correo.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);

     $sentencia = $conexion->prepare("INSERT INTO tbl_usuarios (usuario, password, correo, rol)
                                                 VALUES (:usuario, :password, :correo, :rol)");

                $sentencia->bindParam(":usuario", $usuario);
                $sentencia->bindParam(":password", $hash);
                $sentencia->bindParam(":correo", $correo);
                $sentencia->bindParam(":rol", $rol);

                $sentencia->execute();

                $_SESSION['mensaje_usuarios'] = 'Usuario creado correctamente.';
                header("Location:index.php");
                exit();
            }
        }
    }
}

include("../../templates/header.php");
?>
<br />
<div class="card">
    <div class="card-header">Agregar usuario</div>
    <div class="card-body">

<?php if ($mensajeError) { ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php } ?>

        <form action="" method="post" novalidate>            <div class="mb-3">
                <label for="usuario" class="form-label">Nombre de usuario</label>
<input type="text" class="form-control" name="usuario" id="usuario" value="<?php echo htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8'); ?>" required>            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
<input
                    type="password"
                    class="form-control"
                    name="password"
                    id="password"
                    required
                    pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}"
                    title="Al menos 8 caracteres, con mayúsculas, minúsculas, números y símbolos">
                <div class="form-text">Debe incluir mayúsculas, minúsculas, números y símbolos.</div>
            </div>

            <div class="mb-3">
                <label for="confirmar" class="form-label">Confirmar contraseña</label>
                <input type="password" class="form-control" name="confirmar" id="confirmar" required>            </div>

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

            <button type="submit" class="btn btn-success">Agregar usuario</button>
            <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>
        </form>

    </div>
    <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>
