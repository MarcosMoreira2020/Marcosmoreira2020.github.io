<?php
require_once 'fpdf/dompdf-3.1.0/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

include 'conexao.php';

// Filtros
$id_cliente = isset($_GET['id_cliente']) ? $_GET['id_cliente'] : '';
$cidade = isset($_GET['cidade']) ? $_GET['cidade'] : '';
$data_ini = isset($_GET['data_ini']) ? $_GET['data_ini'] : '';
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

$where = [];
$params = [];
$types = '';
if ($id_cliente !== '') {
    $where[] = "v.id_cliente = ?";
    $params[] = $id_cliente;
    $types .= 'i';
}
if ($cidade !== '') {
    $where[] = "c.cidade = ?";
    $params[] = $cidade;
    $types .= 's';
}
if ($data_ini !== '') {
    $where[] = "v.data_venda >= ?";
    $params[] = $data_ini . " 00:00:00";
    $types .= 's';
}
if ($data_fim !== '') {
    $where[] = "v.data_venda <= ?";
    $params[] = $data_fim . " 23:59:59";
    $types .= 's';
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT v.vendas_id, v.data_venda, v.total, c.nome AS cliente_nome, c.cidade 
        FROM vendas v
        JOIN clientes c ON v.id_cliente = c.id_cliente
        $where_sql
        ORDER BY v.data_venda DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();

// Monta HTML do PDF
$html = '
    <h2 style="text-align:center;">Relatório de Vendas</h2>
    <table width="100%" border="1" cellspacing="0" cellpadding="4" style="font-size:11px; border-collapse:collapse;">
        <thead>
            <tr style="background:#eee;">
                <th>ID Venda</th>
                <th>Data</th>
                <th>Cliente</th>
                <th>Cidade</th>
                <th>Total (R$)</th>
            </tr>
        </thead>
        <tbody>';
$total_geral = 0;
while ($venda = $resultado->fetch_assoc()) {
    $total_geral += $venda['total'];
    $html .= '
        <tr>
            <td>' . $venda['vendas_id'] . '</td>
            <td>' . date('d/m/Y H:i', strtotime($venda['data_venda'])) . '</td>
            <td>' . htmlspecialchars($venda['cliente_nome']) . '</td>
            <td>' . htmlspecialchars($venda['cidade']) . '</td>
            <td>R$ ' . number_format($venda['total'], 2, ',', '.') . '</td>
        </tr>';
}
$html .= '
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" style="text-align:right;">Total Geral:</th>
                <th>R$ ' . number_format($total_geral, 2, ',', '.') . '</th>
            </tr>
        </tfoot>
    </table>';

// Gera o PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("relatorio_vendas.pdf", ["Attachment" => false]);