<?php 
$servidor="localhost";
$baseDatos="piccolodb";
$usuario="root";
$contrasenia="";
try{
    $conexion= new PDO("mysql:host=$servidor;dbname=$baseDatos", $usuario, $contrasenia);
}catch(Exception $error){
    echo $error->getMessage();
}
?>