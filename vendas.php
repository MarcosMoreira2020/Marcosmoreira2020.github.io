<?php
date_default_timezone_set('America/Sao_Paulo');
require_once 'conexao.php';


// Consulta todas as vendas com dados do cliente
$sql = "SELECT v.vendas_id, v.data_venda, v.total, c.nome AS cliente_nome, c.cidade 
        FROM vendas v
        JOIN clientes c ON v.id_cliente = c.id_cliente
        ORDER BY v.data_venda DESC";

$resultado = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Relatório de Vendas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="container mt-4">
    

    <h2>Relatório de Vendas</h2>
    <a href="index.php" class="btn btn-secondary">Página Inicial</a>
    
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID Venda</th>
                <th>Data</th>
                <th>Cliente</th>
                <th>Cidade</th>
                <th>Total (R$)</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_geral = 0;
            while ($venda = $resultado->fetch_assoc()):
                $total_geral += $venda['total'];
            ?>
                <tr>
                    <td><?= $venda['vendas_id'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($venda['data_venda'])) ?></td>
                    <td><?= htmlspecialchars($venda['cliente_nome']) ?></td>
                    <td><?= htmlspecialchars($venda['cidade']) ?></td>
                    <td>R$ <?= number_format($venda['total'], 2, ',', '.') ?></td>
                    <td>
                        <a href="gerar_pdf_venda.php?id=<?= $venda['vendas_id'] ?>" target="_blank" class="btn btn-sm btn-success">
                            Gerar PDF
                        </a>
                        <a href="https://wa.me/5599999999999?text=<?= urlencode('Pedido Nº: ' . $venda['vendas_id'] . '\\nCliente: ' . $venda['cliente_nome'] . '\\nCidade: ' . $venda['cidade'] . '\\nTotal: R$ ' . number_format($venda['total'], 2, ',', '.')) ?>" target="_blank" class="btn btn-sm btn-success" style="background-color:#25D366; border:none; margin-left:4px;">
                            <i class="fab fa-whatsapp"></i>  WhatsApp
                        </a>
                        <a href="excluir_venda.php?id=<?= $venda['vendas_id'] ?>" class="btn btn-sm btn-danger" style="margin-left:4px;" onclick="return confirm('Tem certeza que deseja excluir esta venda?');">
    <i class="fas fa-trash-alt"></i> Excluir
</a>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
        <tbody>
<?php
$total_geral = 0;
while ($venda = $resultado->fetch_assoc()):
    $total_geral += $venda['total'];
?>
    <tr>
        <td><?= $venda['vendas_id'] ?></td>
        <td><?= date('d/m/Y H:i', strtotime($venda['data_venda'])) ?></td>
        <td><?= htmlspecialchars($venda['cliente_nome']) ?></td>
        <td><?= htmlspecialchars($venda['cidade']) ?></td>
        <td>R$ <?= number_format($venda['total'], 2, ',', '.') ?></td>
        <!-- ... -->
    </tr>
<?php endwhile; ?>
</tbody>
        <tfoot>
            <style>
        .mg-3{ padding: 15px 10px; border-top: 10px; cursor: pointer; border-radius: 4px; background: blue; color: white; list-style-type:none}
        </style>
            <tr>
                <th colspan="4" class="text-end">Total Geral:</th>
                <th colspan="2">R$ <?= number_format($total_geral, 2, ',', '.') ?></th>
            </tr>
            
        </tfoot>
    </table>

</body>
</html>
       





