<?php
/**
 * Configurações de conexão com o banco de dados PostgreSQL.
 * Usar .ENV em abiente de produção é altamente recomendado.
 * */
return [
    'host'     => "ep-old-haze-ad11r3y5-pooler.c-2.us-east-1.aws.neon.tech",
    'port'     => 5432,
    'dbname'   => "neondb",
    'user'     => "neondb_owner",
    // Mantendo o formato original da senha com o parâmetro 'endpoint'
    'password' => "endpoint=ep-old-haze-ad11r3y5-pooler;npg_ArpTF6lym8iL",
    'sslmode'  => 'require' 
];