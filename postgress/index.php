<?php
// index.php

require_once 'Connection.php';
require_once 'User.php';

// Inicializa a conexão com o banco de dados
Connection::init();

echo "========================================\n";
echo "1. CRIAÇÃO (INSERT) - USANDO LOAD_NEW\n";
echo "========================================\n";

try {
    // 1.1 Inserir um novo usuário
    $userData = [
        'name' => 'alice smith',
        'email' => 'alice@example.com',
        'password_hash' => 'senha123',
        'is_active' => true
    ];
    $alice = new User($userData, SimpleOrm::LOAD_NEW);
    echo "Usuário Alice criado com ID: " . $alice->id() . "\n";

    // 1.2 Inserir outro usuário e salvar explicitamente
    $bob = new User(null, SimpleOrm::LOAD_EMPTY);
    $bob->set('name', 'bob johnson');
    $bob->set('email', 'bob@example.com');
    $bob->set('password_hash', 'bobsecure');
    $bob->save();
    echo "Usuário Bob criado com ID: " . $bob->id() . "\n";
    
} catch (Exception $e) {
    echo "ERRO DURANTE A CRIAÇÃO: " . $e->getMessage() . "\n";
}


echo "\n========================================\n";
echo "2. RECUPERAÇÃO (READ)\n";
echo "========================================\n";

try {
    // 2.1 Recuperar por Primary Key
    $alice_reloaded = User::retrieveByPK($alice->id());
    echo "2.1 Alice (recarregada): ID {$alice_reloaded->id()}, Nome: {$alice_reloaded->name} (Com filterOutName aplicado)\n";

    // 2.2 Recuperar todos os registros
    $allUsers = User::all();
    echo "2.2 Total de Usuários no banco: " . count($allUsers) . "\n";

    // 2.3 Recuperar por campo (método mágico retrieveByField)
    // O ORM usa ILIKE para pesquisas com '%' no PostgreSQL
    $smithUsers = User::retrieveByName('alice smith', SimpleOrm::FETCH_ONE);
    if ($smithUsers) {
        echo "2.3 Recuperado por Nome (Alice): ID " . $smithUsers->id() . "\n";
    }
    
    // 2.4 Usando consulta SQL direta
    $recentUsers = User::sql("SELECT * FROM :table WHERE is_active = TRUE ORDER BY id DESC LIMIT 1");
    if (!empty($recentUsers)) {
        echo "2.4 Usuário mais recente via SQL: " . $recentUsers[0]->email . "\n";
    }
    
    // 2.5 Usando COUNT
    $count = User::count("SELECT COUNT(*) FROM :table WHERE is_active = TRUE");
    echo "2.5 Contagem de usuários ativos via COUNT: {$count}\n";

} catch (Exception $e) {
    echo "ERRO DURANTE A RECUPERAÇÃO: " . $e->getMessage() . "\n";
}


echo "\n========================================\n";
echo "3. ATUALIZAÇÃO (UPDATE)\n";
echo "========================================\n";

try {
    // 3.1 Carrega o objeto (se ainda não estiver carregado, use retrieveByPK)
    // Vamos carregar o Bob novamente para garantir que é o objeto do banco
    $bob_update = User::retrieveByPK($bob->id());

    // 3.2 Atualiza o campo e salva
    $bob_update->set('name', 'Bob J. Updated');
    $bob_update->set('is_active', false);
    
    // Verifica modificações antes de salvar
    if ($bob_update->isModified()) {
        echo "3.2 Bob tem campos modificados: " . implode(', ', array_keys($bob_update->isModified())) . "\n";
        $bob_update->save();
        echo "Bob atualizado e salvo.\n";
    }
    
    // 3.3 Tenta alterar a senha (o filterInPasswordHash fará o hash)
    $bob_update->set('password_hash', 'novasenha456');
    $bob_update->save();
    echo "Bob teve a senha atualizada (hashed).\n";

} catch (Exception $e) {
    echo "ERRO DURANTE A ATUALIZAÇÃO: " . $e->getMessage() . "\n";
}


echo "\n========================================\n";
echo "4. CONVENIÊNCIA e DELEÇÃO (DELETE)\n";
echo "========================================\n";

try {
    // 4.1 Exemplo de buildSelectBoxValues (útil para formulários)
    $selectOptions = User::buildSelectBoxValues('is_active = TRUE');
    echo "4.1 Opções de SelectBox (Usuários Ativos):\n";
    print_r($selectOptions);

    // 4.2 Deleção
    $bob_delete = User::retrieveByPK($bob->id());
    $bob_delete->delete();
    echo "4.2 Usuário Bob deletado (ID: {$bob_delete->id()}).\n";

    // 4.3 Tentativa de recuperar o registro deletado
    $bob_check = User::retrieveByPK($bob->id());
    if ($bob_check === null) {
        echo "4.3 Verificação: Usuário Bob não encontrado no banco (deleção bem-sucedida).\n";
    }

} catch (Exception $e) {
    // A tentativa de recuperar um PK inexistente lança exceção (código 2)
    if ($e->getCode() == 2) {
        echo "4.3 Verificação: Usuário Bob não encontrado no banco (deleção bem-sucedida).\n";
    } else {
        echo "ERRO DURANTE A DELEÇÃO: " . $e->getMessage() . "\n";
    }
}


echo "\n========================================\n";
echo "5. TRUNCATE (Limpeza - Use com Cuidado!)\n";
echo "========================================\n";

// Limpa a tabela para o próximo teste
// User::truncate();
// echo "A tabela 'users' foi truncada.\n";
?>