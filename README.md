# 📝 Documentação de Exemplo de Uso: SimpleOrm

[Documentação Completa](documents/SimpleOrm.md)
[SQL](documents/SQL.md)
[Configurações e erros](documents/erros.md)

Este documento detalha o uso da classe base **`SimpleOrm`** (adaptada para PostgreSQL/PDO) para interagir com uma tabela de `servicos`.

## 1\. Definição do Modelo (Model)

Para mapear a tabela `servicos` para um objeto PHP, estendemos a classe `SimpleOrm` e definimos as propriedades estáticas e públicas correspondentes às colunas.

### Estrutura do Arquivo: `model/Servico.php`

```php
class Servico extends SimpleOrm
{
    // Define o nome da tabela no PostgreSQL. Sobrescreve a convenção padrão.
    public static $table = 'servicos'; 
    
    // Define a Chave Primária. Sobrescreve o padrão 'id' (se fosse diferente).
    public static $pk = 'id'; 

    // Propriedades Públicas (Mapeamento de Colunas)
    // Os tipos são definidos conforme as colunas do DB.
    public ?int $id = null;
    public string $name = '';
    public string $descricao = '';
    public ?string $created_at = null; 
    // Nota: 'created_at' pode ser null no início, e o DB define o valor.
}
```

### Detalhes do Mapeamento

  * **`public static $table = 'servicos';`**: Garante que o ORM utilize a tabela `servicos` em suas *queries*.
  * **Propriedades Públicas:** O ORM mapeia **automaticamente** as propriedades públicas do objeto para as colunas do banco de dados (ex: `$this->name` é a coluna `"name"`).
  * **Tipagem (PHP 7.4+ / 8+):** O uso de *Nullable Types* (`?int`, `?string`) reflete que `id` e `created_at` são definidos pelo banco de dados na inserção e podem ser `null` antes de serem salvos.

-----

## 2\. Inicialização e Criação da Tabela

Antes de usar o modelo, precisamos garantir que a estrutura da tabela exista no banco de dados.

### Execução de SQL Direto

A classe `SimpleOrm` permite a execução de comandos DDL (Data Definition Language) via o método estático `SimpleOrm::sql()`.

```php
try {
    SimpleOrm::sql("
        CREATE TABLE IF NOT EXISTS \"servicos\" (
            \"id\" SERIAL PRIMARY KEY, // Chave primária gerada automaticamente pelo PostgreSQL
            \"name\" VARCHAR(100) NOT NULL,
            \"descricao\" VARCHAR(100) ,
            \"created_at\" TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ", SimpleOrm::FETCH_NONE); // FETCH_NONE: Indica que não há resultados para buscar.

    echo "✅ Tabela 'servicos' criada com sucesso!";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
```

  * **`SimpleOrm::sql()`**: É o método universal para *queries* que não se encaixam nas operações CRUD padrões.
  * **`FETCH_NONE`**: Usado para comandos que não retornam dados (como `CREATE TABLE`, `UPDATE` em massa, `DELETE` em massa, `TRUNCATE`).

-----

## 3\. Demonstração de Operações CRUD

Após a conexão ser estabelecida e a tabela criada, o modelo `Servico` está pronto para o uso.

### A. Criação de um Novo Registro (`LOAD_NEW`)

A criação de um registro e a persistência imediata no banco de dados são combinadas usando o método de carga **`SimpleOrm::LOAD_NEW`**.

```php
$servico = new Servico([
    'name' => 'Corte',
    'descricao' => 'Corte de cabelo simples'
], SimpleOrm::LOAD_NEW);
```

  * **Hidratação e Inserção:** O construtor **`__construct`** primeiro hidrata o objeto com o array fornecido e, em seguida, chama automaticamente o método **`insert()`**.
  * **Obtenção da PK:** O método `insert()` usa a cláusula `RETURNING id` do PostgreSQL. O `id` gerado é capturado e definido na propriedade `$servico->id`.
  * **Acesso ao ID:** O ID do novo registro é acessível imediatamente via `$servico->id()` ou `$servico->id`.

### B. Listagem de Todos os Registros (`all()`)

O método estático **`all()`** é um *wrapper* para buscar todos os registros da tabela mapeada.

```php
$servicos = Servico::all();
foreach ($servicos as $s) {
    echo "📌 {$s->id()} - {$s->name} ({$s->descricao})<br>";
}
```

  * **Funcionamento:** Internamente, chama `Servico::sql("SELECT * FROM :table")`.
  * **Retorno:** Retorna um *array* de objetos `Servico` totalmente hidratados.

### C. Busca por Chave Primária (`retrieveByPK()`)

Para carregar um único objeto pelo seu ID, utilizamos o método `retrieveByPK()`.

```php
$found = Servico::retrieveByPK($servico->id());
```

  * **Funcionamento:** Internamente, cria uma *query* `SELECT * FROM "servicos" WHERE "id" = ?` e carrega o objeto com o método de carga **`SimpleOrm::LOAD_BY_PK`**.
  * **Resultado:** Retorna um objeto `Servico`. Se não for encontrado, lança uma `\Exception`.

### D. Atualização e Persistência (`set()` e `save()`)

A modificação de um objeto existente deve ser feita usando o método **`set()`** para que o ORM rastreie as alterações, seguido pelo método **`save()`** para persistir a alteração.

```php
$found->set('descricao', 'Corte e acabamento profissional'); // 1. Rastreia a mudança
$found->save(); // 2. Executa o UPDATE
```

  * **`set()`:** Este método é crucial. Ele verifica se o novo valor é diferente do atual e, em caso afirmativo, marca o campo na lista `$modifiedFields`.
  * **`save()`:** Como o objeto `$found` **não é novo** (`isNew()` retorna `false`), ele chama o método **`update()`**, que gera a *query* `UPDATE` contendo apenas os campos que foram modificados via `set()`.

### E. Exclusão (`delete()`)

Para remover o registro do banco de dados, chamamos o método `delete()` no objeto carregado.

```php
$found->delete();
```

  * **Funcionamento:** Executa a *query* `DELETE FROM "servicos" WHERE "id" = ?`.
  * **Nota no Exemplo:** O exemplo mantém a linha comentada (`//$found->delete();`) para que o registro persista para futuras execuções do script de teste.

-----

## 💡 Resumo do Fluxo do Exemplo

1.  **DDL:** `SimpleOrm::sql(...)` cria a tabela `servicos`.
2.  **Create:** `new Servico(..., LOAD_NEW)`
      * $\rightarrow$ Objeto criado.
      * $\rightarrow$ `insert()` chamado.
      * $\rightarrow$ `INSERT INTO servicos (...) VALUES (...) RETURNING id`.
      * $\rightarrow$ `$servico->id` definido (e.g., 1).
3.  **Read (All):** `Servico::all()` $\rightarrow$ `SELECT * FROM servicos` $\rightarrow$ Retorna `[Servico object]`.
4.  **Read (PK):** `Servico::retrieveByPK(1)` $\rightarrow$ `SELECT * FROM servicos WHERE id = 1` $\rightarrow$ Retorna o objeto `$found`.
5.  **Update:** `$found->set(...)` + `$found->save()`
      * $\rightarrow$ `set()` registra que `descricao` mudou.
      * $\rightarrow$ `save()` chama `update()`.
      * $\rightarrow$ `UPDATE servicos SET "descricao" = '...' WHERE "id" = 1`.
6.  **Delete:** `$found->delete()` $\rightarrow$ `DELETE FROM servicos WHERE id = 1`.