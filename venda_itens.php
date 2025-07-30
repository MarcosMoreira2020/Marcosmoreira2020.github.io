<?php
session_start();
// Conexão com o banco de dados MySQL
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'minicrm';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {

    die('Falha na conexão: ' . $conn->connect_error);    
}               
$sql = 'SELECT * FROM vendas';    
$result = $conn->query($sql);
?>
