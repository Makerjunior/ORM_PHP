<?php
/**
 * Configurações de conexão com o banco de dados PostgreSQL.
 * Usar .ENV em abiente de produção é altamente recomendado.
 * */
return [
    'host'     => "",
    'port'     => 5432,
    'dbname'   => "",
    'user'     => "",
    // Mantendo o formato original da senha com o parâmetro 'endpoint'
    'password' => "",
    'sslmode'  => 'require' 
];