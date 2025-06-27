<?php
session_start();

unset($_SESSION["cliente"]);

header("Location: index.php");
exit;
