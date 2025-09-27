<?php
// Connection.php

// Inclua a classe SimpleOrm (certifique-se de que o SimpleOrm.php refatorado esteja no mesmo diretório)
require_once 'ormpostgres.php';

class Connection
{
    private static $host = 'localhost';
    private static $db = 'mydatabase'; // Seu nome de banco de dados
    private static $user = 'myuser';     // Seu usuário do Postgres
    private static $pass = 'mypassword'; // Sua senha do Postgres
    private static $charset = 'utf8';

    /**
     * Estabelece e configura a conexão PDO para o SimpleOrm.
     */
    public static function init()
    {
        $dsn = "pgsql:host=" . self::$host . ";dbname=" . self::$db;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, self::$user, self::$pass, $options);
            // Passa a conexão PDO para a classe base SimpleOrm
            SimpleOrm::useConnection($pdo, self::$db);
            echo "Conexão PostgreSQL estabelecida com sucesso.\n";
            return $pdo;
        } catch (\PDOException $e) {
            // Em um sistema real, você registraria isso em vez de exibir na tela.
            die("Erro de Conexão com o Banco de Dados: " . $e->getMessage());
        }
    }
}