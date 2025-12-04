<?php
$servername = "XXX.Y.Z.Z";
$username = "USERNAME AQUI";
$password = "";
$dbname = "farmacia"; // Deixar este o nome, caso altere aqui tem que alterar no codigo do banco "USE".

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
$conn->query("SET time_zone = '-03:00'"); 

?>