
<?php
$conn = new mysqli('localhost', 'root', '', 'minicrm');
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

$mensagem = '';
$id_editar = 0;
$nome = $email = $telefone = $endereco = $cidade = $estado = $nome_fantasia = '';




// Editar cliente: busca dados para preencher o formulário
if (isset($_GET['editar'])) {
    $id_editar = intval($_GET['editar']);
    $res = $conn->query("SELECT * FROM clientes WHERE id_cliente = $id_editar");
    if ($res && $res->num_rows == 1) {
        $cliente = $res->fetch_assoc();
        $nome = $cliente['nome'];
        $email = $cliente['email'];
        $telefone = $cliente['telefone'];
        $endereco = $cliente['endereco'];
        $cidade = $cliente['cidade'];
        $estado = $cliente['estado'];
        $nome_fantasia = $cliente['nome_fantasia'];
    }
}

// Salvar cliente (inserir ou atualizar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $nome = $conn->real_escape_string($_POST['nome']);
    $email = $conn->real_escape_string($_POST['email']);
    $telefone = $conn->real_escape_string($_POST['telefone']);
    $endereco = $conn->real_escape_string($_POST['endereco']);
    $cidade = $conn->real_escape_string($_POST['cidade']);
    $estado = $conn->real_escape_string($_POST['estado']);
    $nome_fantasia = $conn->real_escape_string($_POST['nome_fantasia']);

    if ($id > 0) {
        // Atualizar
        $sql = "UPDATE clientes SET 
                    nome='$nome', 
                    email='$email', 
                    telefone='$telefone', 
                    endereco='$endereco', 
                    cidade='$cidade', 
                    estado='$estado', 
                    nome_fantasia='$nome_fantasia' 
                WHERE id_cliente=$id";
        if ($conn->query($sql)) {
            $mensagem = "Cliente atualizado com sucesso!";
        } else {
            $mensagem = "Erro ao atualizar cliente: " . $conn->error;
        }
    } else {
        // Inserir
        $sql = "INSERT INTO clientes (nome, email, telefone, endereco, cidade, estado, nome_fantasia) VALUES 
                ('$nome', '$email', '$telefone', '$endereco', '$cidade', '$estado', '$nome_fantasia')";
        if ($conn->query($sql)) {
            $mensagem = "Cliente cadastrado com sucesso!";
        } else {
            $mensagem = "Erro ao cadastrar cliente: " . $conn->error;
        }
    }
}

// Listar clientes para mostrar na tabela
$clientes = $conn->query("SELECT * FROM clientes ORDER BY nome");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <title>Cadastro de Clientes</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f4f4f4; }
        .mensagem { padding: 10px; margin-bottom: 15px; background-color: #d4edda; color: #155724; border-radius: 5px; }
        form { background: #326ab8ff; padding: 15px; border-radius: 5px; max-width: 600px; text-align:"center";}
        label { display: block; margin-top: 10px; }
        input[type="text"], input[type="email"], input[type="tel"] {
            width: 100%; padding: 8px; box-sizing: border-box; margin-top: 5px;
        }
        button { margin-top: 15px; padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        a.editar { color: #007bff; text-decoration: none; }
        a.editar:hover { text-decoration: underline; }
        .mg-3{ padding: 10px 15px; margin-top: 15px; background-color: blue; color: white; border:none; border-radius: 4px; cursor: pointer;}
       
    </style>
</head>
<body>


<h1>Cadastro de Clientes</h1>

<?php if ($mensagem): ?>
    <div class="mensagem"><?= htmlspecialchars($mensagem) ?></div>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="id" value="<?= $id_editar ?>" />
    <label>Nome/Razão Social:
        <input type="text" name="nome" required value="<?= htmlspecialchars($nome) ?>" />
    </label>
    <label>Nome Fantasia:
        <input type="text" name="nome_fantasia" value="<?= htmlspecialchars($nome_fantasia) ?>" />
    </label>
    <label>Email:
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" />
    </label>
    <label>Telefone:
        <input type="tel" name="telefone" value="<?= htmlspecialchars($telefone) ?>" />
    </label>
    <label>Endereço:
        <input type="text" name="endereco" value="<?= htmlspecialchars($endereco) ?>" />
    </label>
    <label>Cidade:
        <input type="text" name="cidade" value="<?= htmlspecialchars($cidade) ?>" />
    </label>
    <label>Estado:
        <input type="text" name="estado" value="<?= htmlspecialchars($estado) ?>" />
    </label>
    <button type="submit"><?= $id_editar ? 'Atualizar Cliente' : 'Cadastrar Cliente' ?></button>
     <a href="index.php" class=" mg-3">Página Inicial</a>
</form>

<h2>Clientes Cadastrados</h2>

<table>
    <thead>
        <tr>
            <th>Nome/Razão Social</th>
            <th>Nome Fantasia</th>
            <th>Email</th>
            <th>Telefone</th>
            <th>Endereço</th>
            <th>Cidade</th>
            <th>Estado</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($clientes && $clientes->num_rows > 0): ?>
            <?php while($c = $clientes->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($c['nome']) ?></td>
                    <td><?= htmlspecialchars($c['nome_fantasia']) ?></td>
                    <td><?= htmlspecialchars($c['email']) ?></td>
                    <td><?= htmlspecialchars($c['telefone']) ?></td>
                    <td><?= htmlspecialchars($c['endereco']) ?></td>
                    <td><?= htmlspecialchars($c['cidade']) ?></td>
                    <td><?= htmlspecialchars($c['estado']) ?></td>
                    <td>
                        <a class="editar" href="?editar=<?= $c['id_cliente'] ?>">Editar</a>
                    </td>

                </tr>
                
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8">Nenhum cliente cadastrado.</td></tr>
        <?php endif; ?>
        
    </tbody>
</table>



</body>
</html>