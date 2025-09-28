<?php
require_once "orm/SimpleOrm.php"; // ORM completo que você colou
require_once "database/db.php";      // importa $pdo e inicializa ORM
require_once "model/User.php";

// Cria a tabela 'servicos' se não existir
try {
    SimpleOrm::sql("
        CREATE TABLE IF NOT EXISTS \"servicos\" (
            \"id\" SERIAL PRIMARY KEY,
            \"name\" VARCHAR(100) NOT NULL,
            \"descricao\" VARCHAR(100) ,
            \"created_at\" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ", SimpleOrm::FETCH_NONE);

    echo "✅ Tabela 'servicos' criada com sucesso!";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}

// Cria a classe Servico
class Servico extends SimpleOrm
{
    public static $table = 'servicos';
    public static $pk = 'id';

    public ?int $id = null;
    public string $name = '';
    public string $descricao = '';
    public ?string $created_at = null;
}



// TestE a conexão e operações básicas
try {
    echo "✅ Conexão funcionando e ORM carregado!<br>";

    // ==========================
    // Criar serviço
    // ==========================
    $servico = new Servico([
        'name' => 'Corte de cabelo',
        'descricao' => 'Corte e acabamento simples'
    ], SimpleOrm::LOAD_NEW);

    
      
    echo "🙋 Criado serviço ID: {$servico->id()} - {$servico->name} - {$servico->descricao}<br>";
    echo "<hr>";
    // ==========================
    // Listar todos os serviços
    // ==========================
    echo "📋 Listando todos os serviços:<br>";
    $servicos = Servico::all();
    foreach ($servicos as $s) {
        echo "📌 {$s->id()} - {$s->name} ({$s->descricao})<br>";
    }
    echo "<hr>";
    // ==========================
    // Buscar por ID
    // ==========================
    $found = Servico::retrieveByPK($servico->id());
    echo "🔍 Encontrado serviço: {$found->name} - {$found->descricao}<br>";
    echo "<hr>";
    // ==========================
    // Atualizar
    // ==========================
    $found->set('descricao', 'Corte e acabamento profissional');
    $found->save();
    echo "✏️ Serviço atualizado: {$found->name} - {$found->descricao}<br>";

    // ==========================
    // Deletar
    // ==========================
    //$found->delete();
    echo "🗑️ Serviço deletado!<br>";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}