<?php
// Configurações de conexão
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'minicrm';

// Tentar conexão
echo "<h2>Testando conexão com MySQL:</h2>";
$conn = mysqli_connect($host, $user, $pass);
if (!$conn) {
    die("FALHA NA CONEXÃO COM O SERVIDOR MYSQL: " . mysqli_connect_error());
}
echo "Conexão com servidor MySQL: <span style='color:green'>OK</span><br>";

// Verificar se o banco de dados existe
$db_check = mysqli_select_db($conn, $db);
if (!$db_check) {
    die("FALHA: Banco de dados '$db' não encontrado");
}
echo "Conexão com banco de dados '$db': <span style='color:green'>OK</span><br>";

// Verificar tabela produtos
$result = mysqli_query($conn, "SHOW TABLES LIKE 'produtos'");
if (mysqli_num_rows($result) == 0) {
    die("FALHA: Tabela 'produtos' não encontrada");
}
echo "Tabela 'produtos' existe: <span style='color:green'>OK</span><br>";

// Verificar estrutura da tabela
echo "<h3>Estrutura da tabela produtos:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th></tr>";

$result = mysqli_query($conn, "DESCRIBE produtos");
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

// Contar produtos
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM produtos");
$row = mysqli_fetch_assoc($result);
echo "<p>Total de produtos cadastrados: {$row['total']}</p>";

echo "<h3>Informações do PHP:</h3>";
echo "Versão do PHP: " . phpversion() . "<br>";
echo "Extensão mysqli: " . (extension_loaded('mysqli') ? 'Carregada' : 'Não carregada') . "<br>";
echo "Extensões carregadas: <pre>";
print_r(get_loaded_extensions());
echo "</pre>";

// Verificar permissões de escrita no diretório
echo "<h3>Permissões de diretório:</h3>";
$dir = __DIR__;
echo "Diretório atual: $dir<br>";
echo "Permissões: " . substr(sprintf('%o', fileperms($dir)), -4) . "<br>";
echo "Gravável: " . (is_writable($dir) ? 'Sim' : 'Não') . "<br>";

// Testar operação de escrita
$test_file = $dir . '/test_write.txt';
$write_test = @file_put_contents($test_file, 'Test write operation');
echo "Teste de escrita: " . ($write_test !== false ? 'Sucesso' : 'Falha') . "<br>";
if ($write_test !== false) {
    unlink($test_file);
}
?>