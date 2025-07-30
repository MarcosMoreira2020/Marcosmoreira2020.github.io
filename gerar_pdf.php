<?php
$conn = new mysqli('localhost', 'root', '', 'minicrm');
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

require_once 'fpdf/fpdf.php';
require_once 'conexao.php';

// Função para converter UTF-8 para ISO-8859-1 para o FPDF
function utf8_to_iso88591($str) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $str);
}

// Verifica se o ID foi passado na URL
if (!isset($_GET['id'])) {
    die('ID da venda não especificado.');
}

$id = intval($_GET['id']);

// Consulta os dados da venda
$sql = "SELECT v.*, c.nome AS cliente
        FROM vendas v
        JOIN clientes c ON v.id_cliente = c.id_cliente
        WHERE v.vendas_id = $id";

$result = $conn->query($sql);

if ($result->num_rows === 0) {
    die('Venda não encontrada.');
}

$venda = $result->fetch_assoc();

// Consulta os itens da venda
$sql_itens = "SELECT vi.*, p.nome_produto
              FROM venda_itens vi
              JOIN produtos p ON vi.produto_id = p.produto_id
              WHERE vi.vendas_id = $id";

$result_itens = $conn->query($sql_itens);

// Início do PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Resumo da Venda',0,1,'C');
$pdf->Ln(5);

// Dados da Venda
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,'ID da Venda: ' . $venda['vendas_id'], 0, 1);
$pdf->Cell(0,10,'Cliente: ' . utf8_to_iso88591($venda['cliente']), 0, 1);
$pdf->Cell(0,10,'Data: ' . date('d/m/Y H:i', strtotime($venda['data_venda'])), 0, 1);
$pdf->Cell(0,10,'Total: R$ ' . number_format($venda['total'], 2, ',', '.'), 0, 1);
$pdf->Ln(5);

// Tabela de produtos
$pdf->SetFont('Arial','B',12);
$pdf->Cell(80,10,'Produto',1);
$pdf->Cell(30,10,'Qtd',1);
$pdf->Cell(40,10,'Preço Unit.',1);
$pdf->Ln();

$pdf->SetFont('Arial','',12);

while ($item = $result_itens->fetch_assoc()) {
    $pdf->Cell(80,10, utf8_to_iso88591($item['nome_produto']),1);
    $pdf->Cell(30,10,$item['quantidade'],1);
    $pdf->Cell(40,10,'R$ ' . number_format($item['preco_unitario'], 2, ',', '.'),1);
    $pdf->Ln();
}

// Saída do PDF
$pdf->Output();
?>




   





