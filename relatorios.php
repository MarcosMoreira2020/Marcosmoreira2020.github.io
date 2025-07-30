<?php
$conn = new mysqli('localhost', 'root', '', 'minicrm');
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
require_once 'conexao.php';

// Verifica se está no modo de estatísticas
$modo_estatisticas = isset($_GET['estatisticas']) && $_GET['estatisticas'] == '1';

// Busca todos os clientes para o select
$clientes = [];
$res = $conn->query("SELECT id_cliente, nome FROM clientes ORDER BY nome");
while ($row = $res->fetch_assoc()) {
    $clientes[] = $row;
}

// Busca todas as cidades para o select
$cidades = [];
$res2 = $conn->query("SELECT DISTINCT cidade FROM clientes ORDER BY cidade");
while ($row = $res2->fetch_assoc()) {
    $cidades[] = $row['cidade'];
}

// Filtros
$id_cliente = isset($_GET['id_cliente']) ? $_GET['id_cliente'] : '';
$cidade = isset($_GET['cidade']) ? $_GET['cidade'] : '';
$data_ini = isset($_GET['data_ini']) ? $_GET['data_ini'] : '';
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

// Se não houver filtro de data, define para hoje
if ($data_ini === '' && $data_fim === '') {
    $hoje = date('Y-m-d');
    $data_ini = $hoje;
    $data_fim = $hoje;
}

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

// Dados para o relatório de vendas
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

// Dados para estatísticas (se o modo estatísticas estiver ativado)
$estatisticas = [];

if ($modo_estatisticas) {
    // 1. Total de vendas e valor total
    $sql_total = "SELECT COUNT(*) as total_vendas, SUM(v.total) as valor_total 
                 FROM vendas v 
                 JOIN clientes c ON v.id_cliente = c.id_cliente 
                 $where_sql";
    
    $stmt_total = $conn->prepare($sql_total);
    if ($params) {
        $stmt_total->bind_param($types, ...$params);
    }
    $stmt_total->execute();
    $resultado_total = $stmt_total->get_result()->fetch_assoc();
    $estatisticas['total_vendas'] = $resultado_total['total_vendas'];
    $estatisticas['valor_total'] = $resultado_total['valor_total'];
    
    // 2. Vendas por cidade
    $sql_cidades = "SELECT c.cidade, COUNT(*) as total, SUM(v.total) as valor_total 
                  FROM vendas v 
                  JOIN clientes c ON v.id_cliente = c.id_cliente ";
    
    if ($where) {
        $sql_cidades .= $where_sql . " AND c.cidade IS NOT NULL ";
    } else {
        $sql_cidades .= "WHERE c.cidade IS NOT NULL ";
    }
    
    $sql_cidades .= "GROUP BY c.cidade ORDER BY valor_total DESC";
    
    $stmt_cidades = $conn->prepare($sql_cidades);
    if ($params) {
        $stmt_cidades->bind_param($types, ...$params);
    }
    $stmt_cidades->execute();
    $resultado_cidades = $stmt_cidades->get_result();
    
    $estatisticas['por_cidade'] = [];
    $labels_cidades = [];
    $dados_cidades = [];
    
    while ($row = $resultado_cidades->fetch_assoc()) {
        $labels_cidades[] = $row['cidade'];
        $dados_cidades[] = $row['valor_total'];
        $estatisticas['por_cidade'][] = $row;
    }
    
    // 3. Vendas por cliente
    $sql_clientes = "SELECT c.nome, COUNT(*) as total, SUM(v.total) as valor_total 
                   FROM vendas v 
                   JOIN clientes c ON v.id_cliente = c.id_cliente 
                   $where_sql 
                   GROUP BY c.nome 
                   ORDER BY valor_total DESC 
                   LIMIT 10";
    
    $stmt_clientes = $conn->prepare($sql_clientes);
    if ($params) {
        $stmt_clientes->bind_param($types, ...$params);
    }
    $stmt_clientes->execute();
    $resultado_clientes = $stmt_clientes->get_result();
    
    $estatisticas['por_cliente'] = [];
    $labels_clientes = [];
    $dados_clientes = [];
    
    while ($row = $resultado_clientes->fetch_assoc()) {
        $labels_clientes[] = $row['nome'];
        $dados_clientes[] = $row['valor_total'];
        $estatisticas['por_cliente'][] = $row;
    }
    
    // 4. Vendas por data
    $sql_datas = "SELECT DATE(v.data_venda) as data, COUNT(*) as total, SUM(v.total) as valor_total 
                FROM vendas v 
                JOIN clientes c ON v.id_cliente = c.id_cliente 
                $where_sql 
                GROUP BY DATE(v.data_venda) 
                ORDER BY data";
    
    $stmt_datas = $conn->prepare($sql_datas);
    if ($params) {
        $stmt_datas->bind_param($types, ...$params);
    }
    $stmt_datas->execute();
    $resultado_datas = $stmt_datas->get_result();
    
    $estatisticas['por_data'] = [];
    $labels_datas = [];
    $dados_datas = [];
    
    while ($row = $resultado_datas->fetch_assoc()) {
        $data_formatada = date('d/m/Y', strtotime($row['data']));
        $labels_datas[] = $data_formatada;
        $dados_datas[] = $row['valor_total'];
        $row['data_formatada'] = $data_formatada;
        $estatisticas['por_data'][] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= $modo_estatisticas ? 'Estatísticas de Vendas' : 'Relatório de Vendas' ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <?php if ($modo_estatisticas): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
    <style>
        .select2-container .select2-selection--single { height: 38px; }
        .select2-selection__rendered { line-height: 38px !important; }
        .select2-selection__arrow { height: 38px !important; }
        .mg-3{padding: 10px 15px; border-radius:4px; background-color:blue; color: white; list-style: none; cursor: pointer;}
        .chart-container { 
            position: relative; 
            height: 300px; 
            margin-bottom: 30px; 
        }
    </style>
</head>
<body class="container mt-4">
    <h2><?= $modo_estatisticas ? 'Estatísticas de Vendas' : 'Relatório de Vendas' ?></h2>
    
    <div class="d-flex mb-3">
        
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-home"></i> Página Inicial
        </a>
    </div>
    
    <form class="row g-3 mb-4" method="get" id="filtrosForm">
        <?php if ($modo_estatisticas): ?>
        <input type="hidden" name="estatisticas" value="1">
        <?php endif; ?>
        
        <div class="col-md-4">
            <label for="id_cliente" class="form-label">Cliente</label>
            <select name="id_cliente" id="id_cliente" class="form-select">
                <option value="">Todos os clientes</option>
                <?php foreach ($clientes as $cli): ?>
                <option value="<?= $cli['id_cliente'] ?>" <?= ($id_cliente == $cli['id_cliente']) ? 'selected' : '' ?>><?= htmlspecialchars($cli['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-3">
            <label for="cidade" class="form-label">Cidade</label>
            <select name="cidade" id="cidade" class="form-select">
                <option value="">Todas as cidades</option>
                <?php foreach ($cidades as $cid): ?>
                <option value="<?= htmlspecialchars($cid) ?>" <?= ($cidade == $cid) ? 'selected' : '' ?>><?= htmlspecialchars($cid) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="col-md-2">
            <label for="data_ini" class="form-label">Data Inicial</label>
            <input type="date" name="data_ini" id="data_ini" class="form-control" value="<?= htmlspecialchars($data_ini) ?>">
        </div>
        
        <div class="col-md-2">
            <label for="data_fim" class="form-label">Data Final</label>
            <input type="date" name="data_fim" id="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>">
        </div>
        
        <div class="col-md-1 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
        </div>
        
        <div class="col-12 d-flex justify-content-end mt-2 gap-2">
            <?php if (!$modo_estatisticas): ?>
            <!-- Botão para gerar estatísticas com os mesmos filtros -->
            <button type="submit" name="estatisticas" value="1" class="btn btn-success">
                <i class="fas fa-chart-bar"></i> Ver Estatísticas
            </button>
            
            <a href="#" id="gerarPdfTodos" class="btn btn-danger" target="_blank">
                <i class="fa fa-file-pdf"></i> Gerar PDF
            </a>
            
            <a href="relatorios.php" class="btn btn-secondary">Limpar Filtros</a>
            <?php else: ?>
            <!-- Botão para voltar ao modo relatório -->
            <a href="relatorios.php<?= !empty($_SERVER['QUERY_STRING']) ? '?' . str_replace('&estatisticas=1', '', str_replace('estatisticas=1&', '', $_SERVER['QUERY_STRING'])) : '' ?>" class="btn btn-secondary">
                <i class="fas fa-list"></i> Voltar ao Relatório
            </a>
            
            <a href="relatorios.php" class="btn btn-outline-secondary">Limpar Filtros</a>
            <?php endif; ?>
        </div>
    </form>
    
    <?php if ($modo_estatisticas): ?>
    <!-- Visualização de estatísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <h5 class="card-title">Total de Vendas</h5>
                    <p class="card-text display-4"><?= number_format($estatisticas['total_vendas']) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <h5 class="card-title">Valor Total</h5>
                    <p class="card-text display-4">R$ <?= number_format($estatisticas['valor_total'], 2, ',', '.') ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info h-100">
                <div class="card-body">
                    <h5 class="card-title">Média por Venda</h5>
                    <p class="card-text display-4">
                        R$ <?= $estatisticas['total_vendas'] > 0 ? number_format($estatisticas['valor_total'] / $estatisticas['total_vendas'], 2, ',', '.') : '0,00' ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning h-100">
                <div class="card-body">
                    <h5 class="card-title">Período</h5>
                    <p class="card-text fs-5">
                        <?= date('d/m/Y', strtotime($data_ini)) ?> 
                        <?= ($data_ini != $data_fim) ? ' até ' . date('d/m/Y', strtotime($data_fim)) : '' ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Gráfico de vendas por data -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Vendas por Data</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="graficoVendasPorData"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de vendas por cidade -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Vendas por Cidade</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="graficoVendasPorCidade"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Gráfico de vendas por cliente (top 10) -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top 10 Clientes por Valor</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="graficoVendasPorCliente"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configuração de cores
        const cores = [
            'rgba(75, 192, 192, 0.7)',
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 99, 132, 0.7)',
            'rgba(255, 159, 64, 0.7)',
            'rgba(153, 102, 255, 0.7)',
            'rgba(255, 205, 86, 0.7)',
            'rgba(201, 203, 207, 0.7)',
            'rgba(255, 99, 71, 0.7)',
            'rgba(60, 179, 113, 0.7)',
            'rgba(106, 90, 205, 0.7)'
        ];
        
        // Gráfico de vendas por data
        const ctxData = document.getElementById('graficoVendasPorData').getContext('2d');
        new Chart(ctxData, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels_datas) ?>,
                datasets: [{
                    label: 'Valor Total (R$)',
                    data: <?= json_encode($dados_datas) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Evolução de Vendas por Data',
                        font: { size: 16 }
                    },
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });
        
        // Gráfico de vendas por cidade
        const ctxCidade = document.getElementById('graficoVendasPorCidade').getContext('2d');
        new Chart(ctxCidade, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels_cidades) ?>,
                datasets: [{
                    label: 'Valor Total (R$)',
                    data: <?= json_encode($dados_cidades) ?>,
                    backgroundColor: cores,
                    borderColor: cores.map(cor => cor.replace('0.7', '1')),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Vendas por Cidade',
                        font: { size: 16 }
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });
        
        // Gráfico de vendas por cliente
        const ctxCliente = document.getElementById('graficoVendasPorCliente').getContext('2d');
        new Chart(ctxCliente, {
            type: 'horizontalBar',
            data: {
                labels: <?= json_encode($labels_clientes) ?>,
                datasets: [{
                    label: 'Valor Total (R$)',
                    data: <?= json_encode($dados_clientes) ?>,
                    backgroundColor: cores,
                    borderColor: cores.map(cor => cor.replace('0.7', '1')),
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Top 10 Clientes por Valor',
                        font: { size: 16 }
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                }
            }
        });
    });
    </script>
    <?php else: ?>
    <!-- Tabela de relatório de vendas -->
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Nª Venda</th>
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
                    <a href="https://wa.me/5519993969960?text=<?= urlencode('Pedido Nº: ' . $venda['vendas_id'] . '\nCliente: ' . $venda['cliente_nome'] . '\nCidade: ' . $venda['cidade'] . '\nTotal: R$ ' . number_format($venda['total'], 2, ',', '.')) ?>" target="_blank" class="btn btn-sm btn-success" style="background-color:#25D366; border:none; margin-left:4px;">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                    <a href="excluir_venda.php?id=<?= $venda['vendas_id'] ?>" class="btn btn-sm btn-danger" style="margin-left:4px;" onclick="return confirm('Tem certeza que deseja excluir esta venda?');">
                        <i class="fas fa-trash-alt"></i> Excluir
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-end">Total Geral:</th>
                <th colspan="2">R$ <?= number_format($total_geral, 2, ',', '.') ?></th>
            </tr>
        </tfoot>
    </table>
    <?php endif; ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#id_cliente').select2({
            placeholder: 'Selecione um cliente',
            allowClear: true,
            width: '100%'
        });
        
        $('#cidade').select2({
            placeholder: 'Selecione uma cidade',
            allowClear: true,
            width: '100%'
        });
    });
    
    document.getElementById('gerarPdfTodos').addEventListener('click', function(e) {
        e.preventDefault();
        const params = new URLSearchParams(new FormData(document.getElementById('filtrosForm'))).toString();
        window.open('gerar_pdf_relatorio.php?' + params, '_blank');
    });
    </script>
</body>
</html>
