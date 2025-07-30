<?php

// Conexão com banco
$conn = new mysqli('localhost', 'root', '', 'minicrm');
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor Fácil - Sistema de Gestão</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Ícones Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <style>
        .menu-card {
            transition: transform 0.2s;
        }
        .menu-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <header class="text-center mb-5">
            <h1 class="display-4">Gestor Fácil</h1>
            <p class="lead">Sistema de Gestão de Clientes e Vendas</p>
        </header>

        <div class="row g-4 justify-content-center">
            <!-- Card Clientes -->
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card menu-card h-100 shadow-sm">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-people display-4 text-primary mb-3"></i>
                        <h5 class="card-title">Clientes</h5>
                        <p class="card-text">Gerenciar cadastro de clientes</p>
                        <a href="clientes.php" class="btn btn-primary w-100">
                            <i class="bi bi-person-plus-fill me-2"></i>Acessar
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card Produtos -->
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card menu-card h-100 shadow-sm">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-box-seam display-4 text-success mb-3"></i>
                        <h5 class="card-title">Produtos</h5>
                        <p class="card-text">Gerenciar cadastro de produtos</p>
                        <a href="produtos.php" class="btn btn-success w-100">
                            <i class="bi bi-plus-circle-fill me-2"></i>Acessar
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card Fornecedores -->
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card menu-card h-100 shadow-sm">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-truck display-4 mb-3" style="color: #8B4513;"></i>
                        <h5 class="card-title">Fornecedores</h5>
                        <p class="card-text">Gerenciar cadastro de fornecedores</p>
                        <a href="fornecedores.php" class="btn w-100" style="background-color: #8B4513; color: white;">
                            <i class="bi bi-truck me-2"></i>Acessar
                        </a>
                    </div>
                </div>
            </div>

         <?php   
         ?>


            <!-- Card Vendas -->
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card menu-card h-100 shadow-sm">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-cart-check display-4 text-danger mb-3"></i>
                        <h5 class="card-title">Vendas</h5>
                        <p class="card-text">Registrar e consultar vendas</p>
                        <a href="vendas.php" class="btn btn-danger w-100">
                            <i class="bi bi-receipt me-2"></i>Acessar
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card Pedido -->
<div class="col-12 col-md-6 col-lg-4">
    <div class="card menu-card h-100 shadow-sm">
        <div class="card-body text-center p-4">
            <i class="bi bi-bag-check display-4 text-warning mb-3"></i>
            <h5 class="card-title">Pedido</h5>
            <p class="card-text">Adicionar produtos ao pedido</p>
            <a href="pedido.php" class="btn btn-warning w-100">
                <i class="bi bi-bag-plus-fill me-2"></i>Acessar
            </a>
        </div>
    </div>
</div>

            <!-- Card Relatórios -->
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card menu-card h-100 shadow-sm">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-graph-up display-4 text-info mb-3"></i>
                        <h5 class="card-title">Relatórios</h5>
                        <p class="card-text">Visualizar relatórios e estatísticas</p>
                        <a href="relatorios.php" class="btn btn-info w-100">
                            <i class="bi bi-file-earmark-bar-graph me-2"></i>Acessar
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Bootstrap JS -->
// ...existing code...
    

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


