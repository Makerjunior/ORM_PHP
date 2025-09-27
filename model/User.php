<?php

// ============ MODELO ============
class User extends SimpleOrm {
    protected static $table = "users"; // tabela no Postgres
    public $id;
    public $name;
    public $email;

    // Exemplo de __toString para buildSelectBoxValues
    public function __toString() {
        return $this->name;
    }
}