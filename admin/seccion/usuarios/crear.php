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

if ($_POST) {
    $usuario = $_POST["usuario"] ?? "";
    $password = $_POST["password"] ?? "";
    $correo = $_POST["correo"] ?? "";
    $rol = $_POST["rol"] ?? "";

    // Validar rol
    $rolesPermitidos = ["admin", "empleado", "delivery"];
    if (!in_array($rol, $rolesPermitidos)) {
        die("Rol inválido.");
    }

    $password = md5($password);

    $sentencia = $conexion->prepare("INSERT INTO tbl_usuarios (usuario, password, correo, rol) 
                                     VALUES (:usuario, :password, :correo, :rol)");

    $sentencia->bindParam(":usuario", $usuario);
    $sentencia->bindParam(":password", $password);
    $sentencia->bindParam(":correo", $correo);
    $sentencia->bindParam(":rol", $rol);

    $sentencia->execute();
    header("Location:index.php");
    exit();
}

include("../../templates/header.php");
?>
<br />
<div class="card">
    <div class="card-header">Agregar usuario</div>
    <div class="card-body">

        <form action="" method="post">
            <div class="mb-3">
                <label for="usuario" class="form-label">Nombre de usuario</label>
                <input type="text" class="form-control" name="usuario" id="usuario" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" name="password" id="password" required>
            </div>

            <div class="mb-3">
                <label for="correo" class="form-label">Correo</label>
                <input type="email" class="form-control" name="correo" id="correo" required>
            </div>

            <div class="mb-3">
                <label for="rol" class="form-label">Rol</label>
                <select class="form-select" name="rol" id="rol" required>
                    <option value="">Seleccionar rol</option>
                    <option value="admin">Administrador</option>
                    <option value="empleado">Empleado</option>
                    <option value="delivery">Delivery</option>
                </select>
            </div>

            <button type="submit" class="btn btn-success">Agregar usuario</button>
            <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>
        </form>

    </div>
    <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>
