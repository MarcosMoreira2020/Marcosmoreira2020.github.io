<?php
// detalhes_venda.php

// Conexão com banco
$conn = new mysqli("localhost", "root", "", "minicrm");
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Verifica se o ID da venda foi passado via GET
if (!isset($_GET['vendas_id']) || empty($_GET['vendas_id'])) {
    die("ID da venda não informado.");
}

$vendas_id = (int)$_GET['vendas_id'];

// Consulta os itens da venda com nome do produto
$sql = "
SELECT 
    vi.quantidade, 
    vi.preco_unitario, 
    p.nome_produto 
FROM venda_itens vi
JOIN produtos p ON vi.produto_id = p.produto_id
WHERE vi.vendas_id = $vendas_id
";

$result = $conn->query($sql);

if (!$result) {
    die("Erro na consulta: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Detalhes da Venda #<?= $vendas_id ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f7f7f7;
        }
        h2 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px 15px;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f9ff;
        }
        .btn-voltar {
            margin-top: 20px;
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .btn-voltar:hover {
            background-color: #0056b3;
        }
        @media(max-width: 600px) {
            table, thead, tbody, th, td, tr { 
                display: block; 
            }
            th {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            tr {
                margin-bottom: 15px;
                background: #fff;
                box-shadow: 0 0 5px rgba(0,0,0,0.1);
                padding: 10px;
                border-radius: 5px;
            }
            td {
                border: none;
                position: relative;
                padding-left: 50%;
                text-align: right;
            }
            td:before {
                position: absolute;
                top: 12px;
                left: 15px;
                width: 45%;
                white-space: nowrap;
                font-weight: bold;
                text-align: left;
                content: attr(data-label);
            }
        }
    </style>
</head>
<body>

<h2>Detalhes da Venda #<?= $vendas_id ?></h2>

<?php if ($result->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Produto</th>
                <th>Quantidade</th>
                <th>Preço Unitário (R$)</th>
                <th>Subtotal (R$)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_geral = 0;
            while ($item = $result->fetch_assoc()):
                $subtotal = $item['quantidade'] * $item['preco_unitario'];
                $total_geral += $subtotal;
            ?>
                <tr>
                    <td data-label="Produto"><?= htmlspecialchars($item['nome_produto']) ?></td>
                    <td data-label="Quantidade"><?= $item['quantidade'] ?></td>
                    <td data-label="Preço Unitário"><?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                    <td data-label="Subtotal"><?= number_format($subtotal, 2, ',', '.') ?></td>
                </tr>
            <?php endwhile; ?>
            <tr>
                <th colspan="3" style="text-align: right;">Total Geral:</th>
                <th><?= number_format($total_geral, 2, ',', '.') ?></th>
            </tr>
        </tbody>
    </table>
<?php else: ?>
    <p>Nenhum item encontrado para esta venda.</p>
<?php endif; ?>

<a href="vendas.php" class="btn-voltar">Voltar para Vendas</a>

</body>
</html>






