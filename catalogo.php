<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
$conn = new mysqli('localhost', 'root', '', 'minicrm');
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Inicializar a sessão do cliente se não existir
if (!isset($_SESSION['cliente_id'])) {
    $_SESSION['cliente_id'] = '';
}

// Guardar o cliente selecionado
if (isset($_POST['cliente_id']) && $_POST['cliente_id'] != '') {
    $_SESSION['cliente_id'] = $_POST['cliente_id'];
}

// Debug para verificar o que está no carrinho
// echo '<pre>Carrinho: '; print_r($_SESSION['carrinho'] ?? []); echo '</pre>';
// echo '<pre>Cliente ID: ' . $_SESSION['cliente_id'] . '</pre>';

// Adicionar ao carrinho
if (isset($_POST['add_carrinho'])) {
    $id = (int)$_POST['produto_id'];
    if (!isset($_SESSION['carrinho'])) $_SESSION['carrinho'] = [];
    if (!isset($_SESSION['carrinho'][$id])) {
        $_SESSION['carrinho'][$id] = 1;
    } else {
        $_SESSION['carrinho'][$id]++;
    }
    // Redirecionar para a página do carrinho
    header('Location: catalogo.php?carrinho=1');
    exit;
}

// Atualizar quantidades do carrinho
if (isset($_POST['atualizar_carrinho']) && isset($_POST['qtd']) && is_array($_POST['qtd'])) {
    // Também atualiza o cliente aqui
    if (isset($_POST['cliente_id']) && $_POST['cliente_id'] != '') {
        $_SESSION['cliente_id'] = $_POST['cliente_id'];
    }
    
    foreach ($_POST['qtd'] as $id => $qtd) {
        $id = (int)$id;
        $qtd = (int)$qtd;
        if ($qtd > 0) {
            $_SESSION['carrinho'][$id] = $qtd;
        } else {
            unset($_SESSION['carrinho'][$id]);
        }
    }
    header('Location: catalogo.php?carrinho=1');
    exit;
}

// Remover do carrinho
if (isset($_GET['remover'])) {
    $id = (int)$_GET['remover'];
    if (isset($_SESSION['carrinho'][$id])) {
        unset($_SESSION['carrinho'][$id]);
    }
    header('Location: catalogo.php?carrinho=1');
    exit;
}

// Limpar carrinho
if (isset($_GET['limpar_carrinho'])) {
    unset($_SESSION['carrinho']);
    // Opcionalmente, limpar o cliente também
    // $_SESSION['cliente_id'] = '';
    header('Location: catalogo.php?carrinho=1');
    exit;
}

// AJAX para adicionar cliente
if (isset($_POST['ajax_add_cliente'])) {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $result = ['success' => false, 'id' => null, 'nome' => '', 'email' => '', 'msg' => ''];
    if ($nome && $email) {
        $stmt = $conn->prepare("INSERT INTO clientes (nome, email) VALUES (?, ?)");
        $stmt->bind_param("ss", $nome, $email);
        if ($stmt->execute()) {
            $result['success'] = true;
            $result['id'] = $conn->insert_id;
            $result['nome'] = $nome;
            $result['email'] = $email;
            
            // Atualizar o cliente na sessão
            $_SESSION['cliente_id'] = $result['id'];
        } else {
            $result['msg'] = 'Erro ao cadastrar cliente.';
        }
        $stmt->close();
    } else {
        $result['msg'] = 'Preencha nome e e-mail.';
    }
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Buscar clientes
$clientes = [];
$resCli = $conn->query("SELECT * FROM clientes ORDER BY nome");
while ($row = $resCli->fetch_assoc()) {
    $clientes[] = $row;
}

// Cliente selecionado da sessão
$cliente_selecionado = $_SESSION['cliente_id'] ?? '';

// Finalizar compra
$mensagem = '';
if (isset($_POST['finalizar_compra'])) {
    // Pega o cliente do formulário ou mantém o da sessão
    $cliente_id = isset($_POST['cliente_id']) && !empty($_POST['cliente_id']) ? 
                  (int)$_POST['cliente_id'] : 
                  (int)$_SESSION['cliente_id'];
    
    // Atualiza o cliente na sessão
    $_SESSION['cliente_id'] = $cliente_id;
    
    if ($cliente_id > 0 && !empty($_SESSION['carrinho'])) {
        $total = 0;
        $itens = [];
        $res = $conn->query("SELECT * FROM produtos");
        $produtos = [];
        while ($row = $res->fetch_assoc()) $produtos[$row['produto_id']] = $row;
        foreach ($_SESSION['carrinho'] as $id => $qtd) {
            if (!isset($produtos[$id])) continue;
            $preco = floatval($produtos[$id]['preco']);  // Garantir que é um número
            $qtdInt = intval($qtd);  // Garantir que é um número
            $total += $preco * $qtdInt;
            $itens[] = ['produto_id'=>$id, 'quantidade'=>$qtdInt, 'preco'=>$preco];
        }
        // INSERIR NA TABELA VENDAS
        $stmt = $conn->prepare("INSERT INTO vendas (id_cliente, total, data_venda) VALUES (?, ?, NOW())");
        $stmt->bind_param("id", $cliente_id, $total);
        $stmt->execute();
        $vendas_id = $stmt->insert_id;
        $stmt->close();
        if (!$vendas_id) {
            die("ERRO: vendas_id não gerado! cliente_id: $cliente_id, total: $total, MySQL: " . $conn->error);
        }
        // INSERIR ITENS DA VENDA
        foreach ($itens as $item) {
            $stmt = $conn->prepare("INSERT INTO venda_itens (vendas_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $vendas_id, $item['produto_id'], $item['quantidade'], $item['preco']);
            $stmt->execute();
            $stmt->close();
        }
        unset($_SESSION['carrinho']);
        $_SESSION['cliente_id'] = ''; // Limpar cliente após finalizar
        header('Location: catalogo.php?carrinho=1&sucesso=1');
        exit;
    } else {
        $mensagem = '<div class="alert alert-danger">Selecione um cliente e tenha produtos no carrinho.</div>';
    }
}

// Listar produtos
$produtos = [];
$res = $conn->query("SELECT * FROM produtos ORDER BY nome_produto");
while ($row = $res->fetch_assoc()) {
    $produtos[] = $row;
}

// Função para buscar produto por id
function getProduto($id, $produtos) {
    foreach ($produtos as $p) {
        if ($p['produto_id'] == $id) return $p;
    }
    return null;
}

// Calcula o total de itens no carrinho
function totalItensCarrinho() {
    if (!isset($_SESSION['carrinho']) || empty($_SESSION['carrinho'])) {
        return 0;
    }
    
    $total = 0;
    foreach ($_SESSION['carrinho'] as $qtd) {
        $total += intval($qtd);
    }
    return $total;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Catálogo de Produtos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        .card-img-top { 
            object-fit:cover; 
            width:100%; 
            height:180px; 
            background:#f8f9fa; 
            cursor:pointer; 
        }
        .card { min-height: 370px; }

        /* Estilo para o modal de imagem em tela cheia */
        .modal-fullscreen .modal-dialog {
            width: 100%;
            max-width: none;
            height: 100%;
            margin: 0;
        }

        .modal-fullscreen .modal-content {
            height: 100%;
            border: 0;
            border-radius: 0;
            background-color: rgba(0, 0, 0, 0.9);
        }

        .modal-fullscreen .modal-body {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .modal-fullscreen img {
            max-width: 95%;
            max-height: 95vh;
            margin: auto;
        }

        .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
    </style>
</head>
<body class="container py-4">
    <h2 class="mb-4 text-center">Catálogo de Produtos</h2>
    <div class="text-end mb-3">
        <a href="catalogo.php?carrinho=1" class="btn btn-primary">Ver Carrinho (<?= totalItensCarrinho() ?>)</a>
        <a href="produtos.php" class="btn btn-secondary ms-2">Admin</a>
    </div>

    <?php if (isset($_GET['carrinho'])): ?>
    <h4>Carrinho de Compras</h4>
    <?php if (isset($_GET['sucesso'])): ?>
    <div class="alert alert-success">Compra finalizada com sucesso!</div>
    <?php endif; ?>
    <?= $mensagem ?>
    <?php if (empty($_SESSION['carrinho'])): ?>
    <div class="alert alert-info">Seu carrinho está vazio.</div>
    <?php else: ?>
    <form method="POST" class="mb-3" id="form-finalizar-compra">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Imagem</th>
                    <th>Preço</th>
                    <th>Quantidade</th>
                    <th>Subtotal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total = 0; 
                foreach ($_SESSION['carrinho'] as $id => $qtd): 
                    $p = getProduto($id, $produtos); 
                    if (!$p) continue; 
                    $preco = floatval($p['preco']);
                    $qtdInt = intval($qtd);
                    $sub = $preco * $qtdInt; 
                    $total += $sub; 
                ?>
                <tr>
                    <td><?= htmlspecialchars($p['nome_produto']) ?></td>
                    <td>
                        <?php if ($p['imagem']): ?>
                        <img src="uploads/<?= htmlspecialchars($p['imagem']) ?>" style="max-width:80px;max-height:80px;cursor:pointer;" 
                             onclick="showFullImage('uploads/<?= htmlspecialchars($p['imagem']) ?>')" alt="<?= htmlspecialchars($p['nome_produto']) ?>" />
                        <?php endif; ?>
                    </td>
                    <td>R$ <?= number_format($preco,2,',','.') ?></td>
                    <td>
                        <input type="number" name="qtd[<?= $p['produto_id'] ?>]" value="<?= $qtdInt ?>" min="1" class="form-control form-control-sm" style="width:70px;" />
                    </td>
                    <td>R$ <?= number_format($sub,2,',','.') ?></td>
                    <td><a href="catalogo.php?remover=<?= $id ?>&carrinho=1" class="btn btn-sm btn-danger">Remover</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end">Total:</th>
                    <th colspan="2">R$ <?= number_format($total,2,',','.') ?></th>
                </tr>
            </tfoot>
        </table>
        <div class="row g-2 align-items-end mb-2">
            <div class="col-md-5">
                <label class="form-label">Cliente</label>
                <div class="input-group">
                    <select name="cliente_id" id="cliente_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($clientes as $cli): ?>
                        <option value="<?= (int)$cli['id_cliente'] ?>" <?= $cliente_selecionado == $cli['id_cliente'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cli['nome']) ?> (<?= htmlspecialchars($cli['email']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-outline-primary" id="btn-add-cliente">Adicionar Cliente</button>
                </div>
                <div id="novo-cliente-form" class="mt-2" style="display:none;">
                    <div class="row g-2">
                        <div class="col">
                            <input type="text" id="novo_cliente_nome" class="form-control" placeholder="Nome" />
                        </div>
                        <div class="col">
                            <input type="email" id="novo_cliente_email" class="form-control" placeholder="E-mail" />
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-success" id="salvar-novo-cliente">Salvar</button>
                        </div>
                    </div>
                    <div id="novo-cliente-msg" class="small mt-1"></div>
                </div>
            </div>
            <div class="col-md-5 d-flex align-items-end">
                <button type="submit" name="atualizar_carrinho" class="btn btn-warning me-2">Atualizar Carrinho</button>
                <button type="submit" name="finalizar_compra" class="btn btn-success me-2">Finalizar Compra</button>
                <a href="catalogo.php?limpar_carrinho=1" class="btn btn-danger">Limpar Carrinho</a>
            </div>
        </div>
    </form>
    <?php endif; ?>
    <a href="catalogo.php" class="btn btn-primary mt-3">&larr; Voltar ao Catálogo</a>

    <?php else: ?>
    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php foreach ($produtos as $p): ?>
        <div class="col">
            <div class="card h-100">
                <?php if ($p['imagem']): ?>
                <img src="uploads/<?= htmlspecialchars($p['imagem']) ?>" 
                     class="card-img-top" 
                     alt="<?= htmlspecialchars($p['nome_produto']) ?>" 
                     onclick="showFullImage('uploads/<?= htmlspecialchars($p['imagem']) ?>')" />
                <?php else: ?>
                <div class="card-img-top d-flex align-items-center justify-content-center text-muted" style="height:180px;">Sem Imagem</div>
                <?php endif; ?>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><?= htmlspecialchars($p['nome_produto']) ?></h5>
                    <p class="card-text mb-2">R$ <?= number_format(floatval($p['preco']),2,',','.') ?></p>
                    <form method="POST" class="mt-auto">
                        <input type="hidden" name="produto_id" value="<?= $p['produto_id'] ?>" />
                        <button type="submit" name="add_carrinho" class="btn btn-success w-100">Comprar</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Modal para imagem em tela cheia -->
    <div class="modal modal-fullscreen fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header border-0">
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <img id="fullScreenImage" src="" alt="Imagem em tela cheia">
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('btn-add-cliente').onclick = function() {
        var f = document.getElementById('novo-cliente-form');
        f.style.display = (f.style.display === 'none') ? 'block' : 'none';
    };

    // Atualizar carrinho e cliente quando o cliente é alterado
    document.getElementById('cliente_id').onchange = function() {
        if (document.getElementById('form-finalizar-compra')) {
            document.querySelector('button[name="atualizar_carrinho"]').click();
        }
    };

    document.getElementById('salvar-novo-cliente').onclick = function() {
        var nome = document.getElementById('novo_cliente_nome').value.trim();
        var email = document.getElementById('novo_cliente_email').value.trim();
        var msg = document.getElementById('novo-cliente-msg');
        msg.textContent = '';
        if (!nome || !email) {
            msg.textContent = 'Preencha nome e e-mail.';
            return;
        }
        var btn = this;
        btn.disabled = true;
        fetch('catalogo.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'ajax_add_cliente=1&nome=' + encodeURIComponent(nome) + '&email=' + encodeURIComponent(email)
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            if (data.success) {
                var select = document.getElementById('cliente_id');
                var opt = document.createElement('option');
                opt.value = data.id;
                opt.textContent = data.nome + ' (' + data.email + ')';
                opt.selected = true;
                select.appendChild(opt);
                document.getElementById('novo_cliente_nome').value = '';
                document.getElementById('novo_cliente_email').value = '';
                msg.textContent = 'Cliente adicionado!';
                
                // Atualizar o carrinho para salvar o cliente
                if (document.getElementById('form-finalizar-compra')) {
                    document.querySelector('button[name="atualizar_carrinho"]').click();
                }
            } else {
                msg.textContent = data.msg || 'Erro ao cadastrar cliente.';
            }
        })
        .catch(() => { 
            btn.disabled = false; 
            msg.textContent = 'Erro ao cadastrar cliente.'; 
        });
    };

    // Função simplificada para exibir imagem em tela cheia
    function showFullImage(imageSrc) {
      // Configurar a imagem no modal
      document.getElementById('fullScreenImage').src = imageSrc;
      
      // Exibir o modal
      var myModal = new bootstrap.Modal(document.getElementById('imageModal'));
      myModal.show();
    }
    </script>
</body>
</html>