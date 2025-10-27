<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "sistema_pollos_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
