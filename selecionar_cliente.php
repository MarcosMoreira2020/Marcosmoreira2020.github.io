// selecionar_cliente.php
<?php
// Conexão com banco
$conn = new mysqli("localhost", "root", "", "minicrm");
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Busca todos os clientes
$result = $conn->query("SELECT id_cliente, nome FROM clientes");
if (!$result) {
    die("Erro ao buscar clientes: " . $conn->error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Selecionar Cliente</title>
</head>
<body>
    <h2>Selecionar Cliente</h2>
    <form action="pedido.php" method="GET">
        <label for="id_cliente">Cliente:</label>
        <select name="id_cliente" id="id_cliente" required>
            <option value="">-- Selecione --</option>
            <?php while ($row = $result->fetch_assoc()): ?>
                <option value="<?= $row['id_cliente'] ?>"><?= htmlspecialchars($row['nome']) ?></option>
            <?php endwhile; ?>
        </select>
        <button type="submit">Iniciar Pedido</button>
    </form>
</body>
</html>