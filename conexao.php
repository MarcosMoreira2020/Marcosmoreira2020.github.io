<?php
$host = 'localhost';
$usuario = 'root';
$senha = '';
$banco = 'minicrm';

$conn = new mysqli($host, $usuario, $senha, $banco);

// Verifica conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>
