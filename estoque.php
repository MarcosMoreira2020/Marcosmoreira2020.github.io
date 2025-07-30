<?php
// Conexão simples
$conn = new mysqli('localhost', 'root', '', 'minicrm');
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Mensagem inicial vazia
$mensagem = '';

// PROCESSAR EXCLUSÃO VIA POST (não via GET)
if (isset($_POST['excluir_produto'])) {
    $id = (int)$_POST['id_excluir'];
    
    // Exclusão simples
    $conn->query("DELETE FROM produtos WHERE produto_id = $id");
    $mensagem = "Produto excluído.";
}

// PROCESSAR INCLUSÃO DE PRODUTO
if (isset($_POST['incluir_produto'])) {
    $nome = $_POST['nome'];
    $preco = str_replace(',', '.', $_POST['preco']);
    $estoque = (int)$_POST['estoque'];
    
    // Query para inserir produto
    $sql = "INSERT INTO produtos (nome, preco, estoque) VALUES ('$nome', $preco, $estoque)";
    
    // Executar e verificar resultado
    if ($conn->query($sql)) {
        $mensagem = "Produto cadastrado com sucesso!";
    } else {
        $mensagem = "Erro ao cadastrar produto: " . $conn->error;
    }
}

// PROCESSAR ATUALIZAÇÃO DE ESTOQUE
if (isset($_POST['atualizar'])) {
    $id = (int)$_POST['id'];
    $novo_estoque = (int)$_POST['novo_estoque'];
    
    // Query simples para atualizar apenas o estoque
    $sql = "UPDATE produtos SET estoque = $novo_estoque WHERE produto_id = $id";
    
    // Executar e verificar resultado
    if ($conn->query($sql)) {
        $mensagem = "Estoque atualizado com sucesso!";
    } else {
        $mensagem = "Erro ao atualizar: " . $conn->error;
    }
}

// Obter lista de produtos
$produtos = [];
$result = $conn->query("SELECT produto_id, nome, preco, estoque FROM produtos ORDER BY nome");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $produtos[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gerenciamento de Produtos</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; }
        .mensagem { padding: 10px; margin-bottom: 15px; background-color: #f2f2f2; }
        .form-cadastro { margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; }
        .btn-excluir { 
            background-color: #f44336;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>Gerenciamento de Produtos</h1>
    
    <?php if ($mensagem): ?>
        <div class="mensagem"><?= $mensagem ?></div>
    <?php endif; ?>
    
    <!-- Formulário de cadastro -->
    <div class="form-cadastro">
        <h2>Incluir Novo Produto</h2>
        <form method="post">
            <p>
                <label>Nome:</label>
                <input type="text" name="nome" required>
            </p>
            <p>
                <label>Preço (R$):</label>
                <input type="text" name="preco" required placeholder="0,00">
            </p>
            <p>
                <label>Estoque:</label>
                <input type="number" name="estoque" required min="0" value="0">
            </p>
            <p>
                <button type="submit" name="incluir_produto">Cadastrar Produto</button>
            </p>
        </form>
    </div>
    
    <!-- Tabela de produtos -->
    <h2>Produtos Cadastrados</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Preço</th>
            <th>Estoque Atual</th>
            <th>Novo Estoque</th>
            <th>Ações</th>
        </tr>
        <?php foreach ($produtos as $produto): ?>
            <tr>
                <td><?= $produto['produto_id'] ?></td>
                <td><?= htmlspecialchars($produto['nome']) ?></td>
                <td>R$ <?= number_format((float)$produto['preco'], 2, ',', '.') ?></td>
                <td><?= $produto['estoque'] ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="id" value="<?= $produto['produto_id'] ?>">
                        <input type="number" name="novo_estoque" value="<?= $produto['estoque'] ?>" min="0" style="width: 60px;">
                        <button type="submit" name="atualizar">Atualizar</button>
                    </form>
                </td>
                <td>
                    <!-- Exclusão via formulário POST em vez de link GET -->
                    <form method="post" onsubmit="return confirm('Tem certeza que deseja excluir este produto?');">
                        <input type="hidden" name="id_excluir" value="<?= $produto['produto_id'] ?>">
                        <button type="submit" name="excluir_produto" class="btn-excluir">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    
    <p><a href="index.php">Voltar para Página Principal</a></p>
</body>
</html>