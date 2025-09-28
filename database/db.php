
<?php
require_once "orm/SimpleOrm.php"; // Ajuste o caminho conforme necessário
// 1. Inclui o arquivo de configuração (de preferência fora da raiz web)
$dbConfig = require 'config/database.php'; 

// Extrai as variáveis
$host     = $dbConfig['host'];
$port     = $dbConfig['port'];
$dbname   = $dbConfig['dbname'];
$user     = $dbConfig['user'];
$password = $dbConfig['password'];
$sslmode  = $dbConfig['sslmode'];

// 2. Monta o DSN
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=$sslmode";

try {
    // 3. Estabelece a conexão PDO
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // Padrão útil para todos
    ]);

    // 4. Inicializa o ORM com a conexão
    SimpleOrm::useConnection($pdo, $dbname);

} catch (PDOException $e) {
    // Para ambientes de produção, use um log em vez de 'die'
    die("❌ Erro de conexão com o Banco de Dados. Verifique as configurações. Detalhes: " . $e->getMessage());
}