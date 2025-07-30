
<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'minicrm');
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
// Inicializa carrinho e desconto
if (!isset($_SESSION['carrinho'])) $_SESSION['carrinho'] = [];
if (!isset($_SESSION['desconto'])) $_SESSION['desconto'] = 0;
// Guarda cliente
if (isset($_POST['cliente_id']) && $_POST['cliente_id'] != '') {
    $_SESSION['cliente_id'] = $_POST['cliente_id'];
} elseif (!isset($_SESSION['cliente_id'])) {
    $_SESSION['cliente_id'] = null;
}
// Captura desconto enviado
if (isset($_POST['desconto'])) {
    $_SESSION['desconto'] = floatval($_POST['desconto']);
}
// Adiciona produto
if (isset($_POST['adicionar'])) {
    $produto_id = $_POST['produto_id'] ?? null;
    $quantidade = (int) ($_POST['quantidade'] ?? 1);
    if ($produto_id) {
        if (!isset($_SESSION['carrinho'][$produto_id])) {
            $_SESSION['carrinho'][$produto_id] = ['produto_id' => $produto_id, 'quantidade' => $quantidade];
        } else {
            $_SESSION['carrinho'][$produto_id]['quantidade'] += $quantidade;
        }
    }
}
// Limpar
if (isset($_POST['limpar_carrinho'])) {
    $_SESSION['carrinho'] = [];
    $_SESSION['cliente_id'] = null;
    $_SESSION['desconto'] = 0;
}
// Finalizar venda
if (isset($_POST['finalizar_venda'])) {
    $cliente_id = $_SESSION['cliente_id'];
    $data = date('Y-m-d');
    $desconto = $_SESSION['desconto'];
    $total = 0;
    if (empty($_SESSION['carrinho'])) {
        echo "<div class='alert alert-warning text-center'>O carrinho está vazio!</div>";
    } elseif (!$cliente_id) {
        echo "<div class='alert alert-danger text-center'>Selecione um cliente!</div>";
    } else {
        // Calcular total
        foreach ($_SESSION['carrinho'] as $item) {
            $produto_id = $item['produto_id'];
            $quantidade = $item['quantidade'];
            $res = $conn->query("SELECT preco FROM produtos WHERE produto_id = $produto_id");
            $preco = $res ? $res->fetch_assoc()['preco'] : 0;
            $total += $preco * $quantidade;
        }
        $total_com_desconto = $total * ((100 - $desconto) / 100);
        date_default_timezone_set('America/Sao_Paulo');
        $data = date('Y-m-d H:i:s'); // Data e hora atual
        
        // Registrar venda
        $stmt = $conn->prepare("INSERT INTO vendas (id_cliente, data_venda, total) VALUES (?, ?, ?)");
        $stmt->bind_param("isd", $cliente_id, $data, $total_com_desconto);
        if ($stmt->execute()) {
            $venda_id = $stmt->insert_id;
            // Itens da venda
            foreach ($_SESSION['carrinho'] as $item) {
                $produto_id = $item['produto_id'];
                $quantidade = $item['quantidade'];
                $res = $conn->query("SELECT preco FROM produtos WHERE produto_id = $produto_id");
                $preco = $res ? $res->fetch_assoc()['preco'] : 0;
                $stmt_item = $conn->prepare("INSERT INTO venda_itens (vendas_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
                $stmt_item->bind_param("iiid", $venda_id, $produto_id, $quantidade, $preco);
                $stmt_item->execute();
                $stmt_item->close();
                $conn->query("UPDATE produtos SET estoque = estoque - $quantidade WHERE produto_id = $produto_id");
            }
            $_SESSION['carrinho'] = [];
            $_SESSION['cliente_id'] = null;
            $_SESSION['desconto'] = 0;
            echo "<div class='alert alert-success text-center'>Venda registrada com sucesso!</div>";
        } else {
            echo "<div class='alert alert-danger text-center'>Erro ao registrar venda: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}
// Cliente selecionado
$clienteSelecionado = $_SESSION['cliente_id'];
$desconto = $_SESSION['desconto'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <title>Novo Pedido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="container py-5">
    <h2 class="text-center mb-4">Novo Pedido</h2>
    <form method="POST" class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label">Cliente</label>
            <select name="cliente_id" class="form-select" required onchange="this.form.submit()">
                <option value="">Selecione o cliente</option>
                <?php
                $res = $conn->query("SELECT * FROM clientes");
                while ($row = $res->fetch_assoc()) {
                    $selected = ($clienteSelecionado == $row['id_cliente']) ? 'selected' : '';
                    echo "<option value='{$row['id_cliente']}' $selected>{$row['nome']}</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Produto</label>
            <select name="produto_id" class="form-select" required>
                <option value="">Selecione o produto</option>
                <?php
                $res = $conn->query("SELECT * FROM produtos");
                while ($row = $res->fetch_assoc()) {
                    echo "<option value='{$row['produto_id']}'>{$row['nome_produto']} - R$ {$row['preco']}</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Quantidade</label>
            <input type="number" name="quantidade" class="form-control" value="1" min="1" required />
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" name="adicionar" class="btn btn-primary w-100">Adicionar Produto</button>
        </div>
    </form>
    <h4 class="mt-4">Carrinho</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Produto</th>
                <th>Quantidade</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total = 0;
            if (!empty($_SESSION['carrinho'])) {
                foreach ($_SESSION['carrinho'] as $item) {
                    $produto_id = $item['produto_id'];
                    $quantidade = $item['quantidade'];
                    $res = $conn->query("SELECT nome_produto, preco FROM produtos WHERE produto_id = $produto_id");
                    $row = $res ? $res->fetch_assoc() : ['nome_produto' => 'Desconhecido', 'preco' => 0];
                    $subtotal = $row['preco'] * $quantidade;
                    $total += $subtotal;
                    echo "<tr>
                        <td>{$row['nome_produto']}</td>
                        <td>$quantidade</td>
                        <td>R$ " . number_format($subtotal, 2, ',', '.') . "</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='3' class='text-center'>Carrinho vazio</td></tr>";
            }
            ?>
        </tbody>
        <?php if (!empty($_SESSION['carrinho'])): ?>
            <tfoot>
                <tr>
                    <th colspan="2" class="text-end">Total</th>
                    <th>R$ <?= number_format($total, 2, ',', '.') ?></th>
                </tr>
                <tr>
                    <th colspan="2" class="text-end">Desconto (<?= $desconto ?>%)</th>
                    <th>- R$ <?= number_format($total * ($desconto / 100), 2, ',', '.') ?></th>
                </tr>
                <tr>
                    <th colspan="2" class="text-end">Total com Desconto</th>
                    <th>R$ <?= number_format($total * ((100 - $desconto) / 100), 2, ',', '.') ?></th>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
    <form method="POST" class="d-flex gap-3 align-items-center mb-4">
        <label for="desconto">Desconto (%):</label>
        <input type="number" name="desconto" id="desconto" value="<?= htmlspecialchars($desconto) ?>" min="0" max="100" step="0.01" class="form-control w-auto" />
        <button type="submit" class="btn btn-outline-secondary">Aplicar Desconto</button>
    </form>
    <form method="POST" class="d-flex gap-3">
        <input type="hidden" name="cliente_id" value="<?= htmlspecialchars($clienteSelecionado) ?>" />
        <button type="submit" name="finalizar_venda" class="btn btn-success">Finalizar Venda</button>
        <button type="submit" name="limpar_carrinho" class="btn btn-danger">Limpar Carrinho</button>
        <button type="submit" name="finalizar_venda" class="btn btn-success">Finalizar Venda</button>
        <a href="vendas.php" class="btn btn-secondary">Ver Vendas</a>
        <a href="index.php" class="btn btn-secondary">Página Inicial</a>
        <a href="catalogo.php" class="btn btn btn-danger">Catálogo</a>
    </form>
</body>
</html>





    












                





