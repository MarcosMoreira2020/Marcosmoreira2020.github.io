<?php
require_once 'fpdf/dompdf-3.1.0/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

include 'conexao.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID da venda não especificado.");
}

// Buscar a venda com cliente
$sql = "SELECT v.*, c.nome AS cliente 
        FROM vendas v 
        JOIN clientes c ON v.id_cliente = c.id_cliente 
        WHERE v.vendas_id = $id";

$result = $conn->query($sql);

if ($result->num_rows === 0) {
    die("Venda não encontrada.");
}

$venda = $result->fetch_assoc();

// Buscar os itens da venda
$sql_itens = "SELECT vi.*, p.nome_produto, p.preco 
              FROM venda_itens vi 
              JOIN produtos p ON vi.produto_id = p.produto_id 
              WHERE vi.vendas_id = $id";

$result_itens = $conn->query($sql_itens);

// Monta HTML do PDF
$html = '
    <h1 style="font-size: 9px; text-align:center;">Resumo da Venda</h1>
    <hr>
    <p style="font-size: 9px;">ID da Venda: ' . $venda['vendas_id'] . '</p>
    <p><strong>Cliente:</strong> ' . $venda['cliente'] . '</p>
    <p><strong>Data:</strong> ' . date('d/m/Y H:i', strtotime($venda['data_venda'])) . '</p>
    <p><strong>Total:</strong> R$ ' . number_format($venda['total'], 2, ',', '.') . '</p>
    <br>
    <table style="font-size: 11px; width: 100%;" border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th>Produto</th>
                <th>Quantidade</th>
                <th>Preço Unit.</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>';

while ($item = $result_itens->fetch_assoc()) {
    $total_item = $item['quantidade'] * $item['preco'];
    $html .= '
        <tr>
            <td>' . $item['nome_produto'] . '</td>
            <td>' . $item['quantidade'] . '</td>
            <td>R$ ' . number_format($item['preco'], 2, ',', '.') . '</td>
            <td>R$ ' . number_format($total_item, 2, ',', '.') . '</td>
        </tr>';
}

$html .= '
        </tbody>
    </table>';

// Gera o PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("resumo_venda_$id.pdf", ["Attachment" => false]);



