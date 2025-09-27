<?php
require_once "orm/SimpleOrm.php"; // ✅ Precisa estar definido antes

$host = "ep-old-haze-ad11r3y5-pooler.c-2.us-east-1.aws.neon.tech";
$port = 5432;
$dbname = "neondb";
$user = "neondb_owner";
$password = "endpoint=ep-old-haze-ad11r3y5-pooler;npg_ArpTF6lym8iL";

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    // Inicializa ORM
    SimpleOrm::useConnection($pdo, $dbname);
} catch (PDOException $e) {
    die("❌ Erro de conexão: " . $e->getMessage());
}
