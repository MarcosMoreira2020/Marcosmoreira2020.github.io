


<?php
require_once 'conexao.php';

// Exclusão de fornecedor
if (isset($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];
    $stmt = $conn->prepare("DELETE FROM fornecedores WHERE id_fornecedor = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    header('Location: fornecedores.php');
    exit;
}

// Buscar fornecedor para edição
$fornecedor_edicao = null;
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $stmt = $conn->prepare("SELECT * FROM fornecedores WHERE id_fornecedor = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result_edicao = $stmt->get_result();
    if ($result_edicao->num_rows > 0) {
        $fornecedor_edicao = $result_edicao->fetch_assoc();
    }
    $stmt->close();
}

// Cadastro ou atualização de fornecedor
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $cnpj = $_POST['cnpj'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    
    if (isset($_POST['id_fornecedor']) && $_POST['id_fornecedor'] > 0) {
        // Atualização
        $id = (int)$_POST['id_fornecedor'];
        $stmt = $conn->prepare("UPDATE fornecedores SET nome = ?, cnpj = ?, telefone = ?, email = ?, endereco = ? WHERE id_fornecedor = ?");
        $stmt->bind_param('sssssi', $nome, $cnpj, $telefone, $email, $endereco, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Novo cadastro
        $stmt = $conn->prepare("INSERT INTO fornecedores (nome, cnpj, telefone, email, endereco) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssss', $nome, $cnpj, $telefone, $email, $endereco);
        $stmt->execute();
        $stmt->close();
    }
    
    header('Location: fornecedores.php');
    exit;
}

// Listar fornecedores
$result = $conn->query("SELECT * FROM fornecedores ORDER BY nome");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Fornecedores</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-4">
    <h2><?= isset($fornecedor_edicao) ? 'Editar Fornecedor' : 'Cadastro de Fornecedor' ?></h2>
    <form method="post" class="mb-4">
        <?php if (isset($fornecedor_edicao)): ?>
            <input type="hidden" name="id_fornecedor" value="<?= $fornecedor_edicao['id_fornecedor'] ?>">
        <?php endif; ?>
        
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Nome</label>
                <input type="text" name="nome" class="form-control" required 
                       value="<?= isset($fornecedor_edicao) ? htmlspecialchars($fornecedor_edicao['nome']) : '' ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">CNPJ</label>
                <input type="text" name="cnpj" class="form-control"
                       value="<?= isset($fornecedor_edicao) ? htmlspecialchars($fornecedor_edicao['cnpj']) : '' ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Telefone</label>
                <input type="text" name="telefone" class="form-control"
                       value="<?= isset($fornecedor_edicao) ? htmlspecialchars($fornecedor_edicao['telefone']) : '' ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">E-mail</label>
                <input type="email" name="email" class="form-control"
                       value="<?= isset($fornecedor_edicao) ? htmlspecialchars($fornecedor_edicao['email']) : '' ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Endereço</label>
                <input type="text" name="endereco" class="form-control"
                       value="<?= isset($fornecedor_edicao) ? htmlspecialchars($fornecedor_edicao['endereco']) : '' ?>">
            </div>
        </div>
        
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">
                <?= isset($fornecedor_edicao) ? 'Atualizar' : 'Cadastrar' ?>
            </button>
            <?php if (isset($fornecedor_edicao)): ?>
                <a href="fornecedores.php" class="btn btn-secondary">Cancelar</a>
            <?php else: ?>
                <a href="index.php" class="btn btn-secondary">Página Inicial</a>
            <?php endif; ?>
        </div>
    </form>

    <h3>Lista de Fornecedores</h3>
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>CNPJ</th>
                <th>Telefone</th>
                <th>E-mail</th>
                <th>Endereço</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($fornecedor = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $fornecedor['id_fornecedor'] ?></td>
                    <td><?= htmlspecialchars($fornecedor['nome']) ?></td>
                    <td><?= htmlspecialchars($fornecedor['cnpj']) ?></td>
                    <td><?= htmlspecialchars($fornecedor['telefone']) ?></td>
                    <td><?= htmlspecialchars($fornecedor['email']) ?></td>
                    <td><?= htmlspecialchars($fornecedor['endereco']) ?></td>
                    <td>
                        <a href="fornecedores.php?editar=<?= $fornecedor['id_fornecedor'] ?>" class="btn btn-sm btn-warning">Editar</a>
                        <a href="javascript:if(confirm('Tem certeza que deseja excluir este fornecedor?')) location.href='fornecedores.php?excluir=<?= $fornecedor['id_fornecedor'] ?>'" class="btn btn-sm btn-danger">Excluir</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
