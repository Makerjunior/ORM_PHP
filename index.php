<?php
require_once "orm/SimpleOrm.php"; // ORM completo que você colou
require_once "database/db.php";      // importa $pdo e inicializa ORM
require_once "model/User.php";

/*

// ============ TESTES ============
try {
    echo "✅ Conexão funcionando e ORM carregado!<br>";
     
    // Criar usuário novo
    echo "Inserindo usuario";
    $user = new User(['name' => 'João ORM', 'email' => 'joao.orm@example.com'], SimpleOrm::LOAD_NEW);
    echo "🙋 Criado ID: {$user->id()}<br>";

    // Listar todos
    echo "Lista todos os usuarios";
    $users = User::all();
    foreach ($users as $u) {
        echo "📌 {$u->id()} - {$u->name} ({$u->email})<br>";
    }

    // Buscar por ID
    echo "Busca usuário por (ID)";
    $found = User::retrieveByPK($user->id());
    echo "🔍 Encontrado: {$found->name} - {$found->email}<br>";

    // Atualizar
    echo "Atualiza Usuário";
    $found->set('email', 'novoemail@example.com');
    $found->save();
    echo "✏️ Email atualizado: {$found->email}<br>";

    // Deletar
    echo "deleta Usuário";
    $found->delete();
    echo "🗑️ Usuário deletado!<br>";
    



} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
*/
// Criar tabela 
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
// model/Servico.php


class Servico extends SimpleOrm
{
    public static $table = 'servicos';
    public static $pk = 'id';

    public ?int $id = null;
    public string $name = '';
    public string $descricao = '';
    public ?string $created_at = null;
}




try {
    echo "✅ Conexão funcionando e ORM carregado!<br>";

    // ==========================
    // Criar serviço
    // ==========================
    $servico = new Servico([
        'name' => 'Barba1',
        'descricao' => 'Barbo terapia1'
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