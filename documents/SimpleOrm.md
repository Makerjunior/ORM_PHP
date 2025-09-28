# 📚 Documentação Completa: SimpleOrm (PostgreSQL/PDO)

A classe **`SimpleOrm`** é uma camada de Mapeamento Objeto-Relacional (ORM) minimalista e abstrata, refatorada para operar especificamente com o banco de dados **PostgreSQL** utilizando a extensão **PDO (PHP Data Objects)**. Ela fornece a base para a criação de modelos de dados, permitindo a interação com tabelas de forma orientada a objetos (Entity-Relationship - ER).

## 💡 Princípios Arquitetônicos e Design

### 1\. Modelo Abstrato e Extensível

Como uma classe **`abstract`**, `SimpleOrm` não pode ser instanciada diretamente. Você deve estendê-la para criar seus modelos de domínio (por exemplo, `class User extends SimpleOrm`). Os métodos estáticos (como `retrieveByPK`, `all`, `sql`) utilizam `get_called_class()` para operar no contexto da subclasse, garantindo a correta descoberta da tabela.

### 2\. Convenção e Configuração (CoC)

O ORM segue o princípio de **Convenção sobre Configuração**:

  * **Nome da Tabela:** A convenção padrão é que o nome da tabela no PostgreSQL seja o nome da subclasse em minúsculas (ex: `class Produto` $\rightarrow$ tabela `"produto"`).
  * **Chave Primária (PK):** O padrão é a coluna **`id`**.

Ambas as convenções podem ser sobrescritas nas subclasses definindo as propriedades estáticas:

```php
class Produto extends SimpleOrm
{
    // Sobrescreve o nome da tabela
    protected static $table = 'produtos_do_catalogo'; 
    // Sobrescreve a PK
    protected static $pk = 'produto_id'; 
    
    // Outras propriedades públicas (colunas da tabela)
    public $nome;
    public $preco;
}
```

### 3\. Foco em PostgreSQL e PDO

A refatoração prioriza a sintaxe e os recursos do PostgreSQL e PDO:

  * **Delimitadores:** Uso de aspas duplas (`"`) para identificadores de tabelas e colunas, essencial no PostgreSQL.
  * **Prepared Statements:** Todos os métodos CRUD e de busca usam *prepared statements* (placeholders `?`) para segurança contra injeção de SQL.
  * **Obtenção de PK:** O método `insert()` utiliza a cláusula PostgreSQL **`RETURNING :pk`** para buscar o ID gerado (por exemplo, por uma sequência `serial`) em uma única operação.

-----

## 🛠️ Configuração de Conexão Estática

A conexão é gerenciada estaticamente, garantindo que todos os modelos utilizem a mesma instância de PDO.

### `SimpleOrm::useConnection(\PDO $conn, $database)`

Define a conexão global. **Este método deve ser chamado uma única vez antes de qualquer operação de banco de dados.**

| Parâmetro | Tipo | Descrição |
| :--- | :--- | :--- |
| `conn` | `\PDO` | A instância ativa do PDO, configurada para o PostgreSQL. |
| `database` | `string` | O nome do banco de dados/schema. Embora o PDO gerencie isso, é mantido para compatibilidade e pode ser usado em atalhos SQL. |

> ⚠️ **Importante:** Este método configura o PDO para **lançar exceções** (`\PDO::ERRMODE_EXCEPTION`) em caso de erros SQL, facilitando a depuração e o tratamento de erros.

### `SimpleOrm::getConnection()`

Retorna a instância de PDO. Lança uma `\Exception` se `useConnection()` não tiver sido chamada.

-----

## 🔄 Ciclo de Vida do Objeto (Construtor e Carregamento)

O construtor **`__construct`** é **`final public`** para controlar rigorosamente como os objetos são criados.

```php
final public function __construct ($data = null, $method = self::LOAD_EMPTY)
```

| Método de Carga (`$method`) | Descrição | Processo Interno |
| :--- | :--- | :--- |
| **`LOAD_BY_PK` (1)** | Carrega um objeto usando o `$data` como o valor da PK. | Popula a PK, chama `hydrateFromDatabase()`. |
| **`LOAD_BY_ARRAY` (2)** | Hidrata o objeto diretamente a partir de um array de dados (registro do DB). | Chama `loadByArray()` e `executeOutputFilters()`. Usado em buscas. |
| **`LOAD_NEW` (3)** | Cria um novo registro a partir do array `$data` e o **salva imediatamente** no DB. | Chama `loadByArray()`, define `$isNew = true`, e então chama `insert()`. |
| **`LOAD_EMPTY` (4)** | Cria um objeto vazio, buscando e definindo todas as colunas como `null`. | Chama `hydrateEmpty()` e define `$isNew = true`. |

### Métodos de Inicialização e Hidratação

  * **`private function hydrateFromDatabase()`:** Responsável por executar o `SELECT *` para o registro atual (usando a PK), buscar os dados e popular as propriedades do objeto. Lança uma `\Exception` se o registro não for encontrado.
  * **`public function initialise()`:** **Hook de vida** chamado no final de todos os processos do construtor. Deve ser sobrescrito em subclasses para lógica de inicialização pós-carregamento (ex: criação de objetos relacionados).

-----

## ⚙️ Operações de Consulta (Reading)

### Métodos Estáticos de Busca

| Método | Assinatura | Descrição |
| :--- | :--- | :--- |
| **`retrieveByPK`** | `static function retrieveByPK ($pk)` | Busca um único registro pela Chave Primária. |
| **`all`** | `static function all ()` | Retorna um array com **todos** os objetos da tabela. |
| **`hydrate`** | `static function hydrate ($data)` | Cria e retorna um objeto a partir de um array (`LOAD_BY_ARRAY`). |
| **`retrieveByField`**| `static function retrieveByField ($field, $value, $return = self::FETCH_MANY)` | Busca registros onde a coluna `$field` é igual a `$value`. Suporta `ILIKE` se `$value` contiver `%`. |
| **`__callStatic`** | `static function retrieveBy...($value)` | *Magic method* que permite buscas por nome de campo (ex: `User::retrieveByEmail('a@b.com')`). |

### Execução de SQL Customizado

O método **`sql()`** é a porta de entrada para *queries* complexas.

```php
SimpleOrm::sql (string $sql, integer $return = SimpleOrm::FETCH_MANY);
```

| Parâmetro | Valores Possíveis | Descrição |
| :--- | :--- | :--- |
| `$sql` | `string` | A *query* SQL. Suporta atalhos (shortcuts) para nomes de identificadores. |
| `$return` | `FETCH_ONE, FETCH_MANY, FETCH_NONE` | Controla o tipo de retorno. |

**Atalhos SQL:**
| Atalho | Substituição |
| :--- | :--- |
| `:database` | `self::getDatabaseName()` |
| `:table` | `"` . `self::getTableName()` . `"` |
| `:pk` | `"` . `self::getTablePk()` . `"` |

**Exemplo:**

```php
// Retorna um array de objetos User
$recentUsers = User::sql('SELECT * FROM :table WHERE created_at > \'2024-01-01\' ORDER BY :pk DESC'); 

// Executa uma query que não retorna dados (DDL/DML)
User::sql('UPDATE :table SET status = 1 WHERE :pk = 10', SimpleOrm::FETCH_NONE);
```

-----

## ✍️ Operações de Persistência (CRUD)

### 1\. Inserção (`insert()` e `save()`)

  * **`public function save()`:** O método principal. Decide se deve chamar `insert()` ou `update()` com base em `$this->isNew()`.
  * **`private function insert()`:**
    1.  Chama o **`preInsert()` hook**.
    2.  Executa os **Filtros de Entrada (`filterIn...`)**.
    3.  Cria a *query* `INSERT INTO :table (...) VALUES (...) RETURNING :pk`.
    4.  Executa o *prepared statement*.
    5.  Define o ID retornado na propriedade PK do objeto.
    6.  Chama `hydrateFromDatabase()` para carregar quaisquer *defaults* ou valores de *triggers*.
    7.  Chama o **`postInsert()` hook**.

### 2\. Atualização (`update()`)

  * **`public function update()`:**
    1.  Lança uma exceção se `$this->isNew()` for `true`.
    2.  Executa os **Filtros de Entrada (`filterIn...`)**.
    3.  Cria a *query* `UPDATE :table SET ... WHERE :pk = ?`.
    4.  Exclui a PK do array de campos atualizados (devido a `$ignoreKeyOnUpdate = true`).
    5.  Executa o *prepared statement* e **limpa a lista de campos modificados** (`$modifiedFields`).

### 3\. Exclusão (`delete()`)

  * **`public function delete()`:**
    1.  Cria a *query* `DELETE FROM :table WHERE :pk = ?`.
    2.  Executa o *prepared statement*.

-----

## 🎣 Ganchos de Dados e Filtros (ER Fine Tuning)

O mecanismo de filtros permite transformar dados durante a entrada (salvamento) e a saída (carregamento), desacoplando o formato de dados do banco de dados do formato de dados do objeto.

### A. Filtros de Saída (Output Filters)

Métodos que começam com **`filterOut`** são chamados após a hidratação dos dados do banco de dados (em `loadByArray()` e `hydrateFromDatabase()`). Úteis para conversão do formato DB para o objeto.

| Cenário de Uso | Exemplo de Implementação na Subclasse |
| :--- | :--- |
| Conversão de string (DB) para objeto **`DateTime`** (Objeto). | `protected function filterOutCreatedAt() { $this->created_at = new \DateTime($this->created_at); }` |
| Desserialização de dados JSON. | `protected function filterOutConfig() { $this->config = json_decode($this->config, true); }` |

### B. Filtros de Entrada (Input Filters)

Métodos que começam com **`filterIn`** são chamados antes de `insert()` ou `update()`. Eles recebem e devem retornar o array de dados a ser salvo no DB.

| Cenário de Uso | Exemplo de Implementação na Subclasse |
| :--- | :--- |
| Criptografia de senhas. | `protected function filterInPassword(array $data) { if (isset($data['password'])) { $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT); } return $data; }` |
| Serialização de objetos ou arrays em JSON. | `protected function filterInConfig(array $data) { if (isset($data['config'])) { $data['config'] = json_encode($data['config']); } return $data; }` |

### **Exemplo de Funcionamento:**

1.  **`$user->set('config', ['theme' => 'dark']);`**
2.  **`$user->save();`**
3.  `update()` chama `executeInputFilters()`.
4.  O método `filterInConfig()` serializa `['theme' => 'dark']` para `'{"theme":"dark"}'`.
5.  O valor `'{"theme":"dark"}'` é salvo na coluna DB.
6.  **`$user2 = User::retrieveByPK(1);`**
7.  `hydrateFromDatabase()` chama `executeOutputFilters()`.
8.  O método `filterOutConfig()` desserializa `'{"theme":"dark"}'` para `['theme' => 'dark']`.
9.  O campo `$user2->config` é um array PHP.

-----

## 🔍 Gerenciamento de Estado e Modificações

O ORM oferece um mecanismo básico para rastrear alterações, essencialmente útil para determinar quais campos precisam ser incluídos em uma *query* `UPDATE`.

### `public function set ($fieldName, $newValue)`

Este método não apenas define o valor, mas também chama **`private function modifiedFields()`** se o `$newValue` for diferente do valor atual.

### `public function isModified ()`

Retorna um array associativo dos campos modificados desde o carregamento/criação do objeto (usando `set()`).

```php
// Saída: ['email' => 'novo.email@teste.com'] 
// (O valor é o último valor definido, não o valor original)
```

> **Atenção:** Se você modificar propriedades públicas diretamente (ex: `$user->email = 'novo';`) em vez de usar `$user->set('email', 'novo');`, o ORM **não** rastreará a alteração. O uso de `set()` é obrigatório para rastrear modificações.