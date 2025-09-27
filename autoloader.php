<?php
// autoloader.php

/**
 * Carregador automático simples para o namespace global (sem PSR-4).
 * Ele espera que o nome da classe corresponda ao nome do arquivo.
 */
spl_autoload_register(function ($className) {
    // 1. Converte o nome da classe para o nome do arquivo, adicionando .php
    $filename = $className . '.php';

    // 2. Verifica se o arquivo existe e o inclui
    if (file_exists($filename)) {
        require_once $filename;
    }
    // Opcionalmente, adicione um throw new Exception se a classe não for encontrada
});

// Nota: Não é necessário fechar a tag ?> 