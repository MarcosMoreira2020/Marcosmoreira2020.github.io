<?php
require_once 'conexao.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Exclui a venda pelo ID
    $stmt = $conn->prepare("DELETE FROM vendas WHERE vendas_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
}

// Redireciona de volta para o relatório de vendas
header('Location: vendas.php');
exit;
