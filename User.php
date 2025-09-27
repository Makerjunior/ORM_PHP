<?php
// User.php

// A classe User herda do nosso SimpleOrm refatorado
require_once 'SimpleOrm.php';

class User extends SimpleOrm
{
    // O SimpleOrm detectará 'users' automaticamente, mas podemos especificar
    // protected static $table = 'users';

    // O SimpleOrm detectará 'id' automaticamente, mas podemos especificar
    // protected static $pk = 'id';

    // ----------------------------------------------------
    // Propriedades da Tabela (Podem ser públicas ou acessadas via __get)
    // ----------------------------------------------------
    public $id;
    public $name;
    public $email;
    public $password_hash;
    public $is_active;
    public $created_at;

    // ----------------------------------------------------
    // Métodos de Filtro (Para Manipulação de Dados na Entrada/Saída)
    // ----------------------------------------------------

    /**
     * filtroOut para formatar a saída do nome (apenas um exemplo)
     */
    public function filterOutName()
    {
        $this->name = ucwords($this->name);
    }

    /**
     * filtroIn para hashear a senha antes de inserir/atualizar
     * Nota: A password_hash precisa estar no array $data para ser processada
     */
    public function filterInPasswordHash(array $data)
    {
        if (isset($data['password_hash']) && !empty($data['password_hash'])) {
            // Simulação de hash de senha
            $data['password_hash'] = password_hash($data['password_hash'], PASSWORD_DEFAULT);
        }
        return $data;
    }

    // ----------------------------------------------------
    // Métodos de Evento
    // ----------------------------------------------------

    /**
     * Executado antes de uma inserção.
     */
    public function preInsert()
    {
        // Garante que o timestamp de criação está definido se o banco não o fizer
        if (empty($this->created_at)) {
             $this->created_at = date('Y-m-d H:i:s');
        }
    }
    
    /**
     * Executado após a inserção (útil para logs).
     */
    public function postInsert()
    {
        echo "LOG: Novo usuário com ID #{$this->id()} criado.\n";
    }

    // ----------------------------------------------------
    // Implementação de __toString para buildSelectBoxValues
    // ----------------------------------------------------
    public function __toString()
    {
        return "{$this->name} ({$this->email})";
    }
}