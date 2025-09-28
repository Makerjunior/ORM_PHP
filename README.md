No **SimpleOrm** que você está usando, existe um método chamado **`sql()`** que permite executar qualquer comando SQL diretamente. Ele é estático e tem a seguinte assinatura (geralmente):

```php
SimpleOrm::sql(string $sql, int $fetchMode = SimpleOrm::FETCH_ALL, array $params = [])
```

### Parâmetros

1. **$sql** → A query SQL que você quer executar. Pode ser `SELECT`, `INSERT`, `UPDATE`, `DELETE` ou até criação de tabela.
2. **$fetchMode** → Como você quer que o resultado seja retornado:

   * `SimpleOrm::FETCH_ALL` → retorna todos os resultados como array.
   * `SimpleOrm::FETCH_ONE` → retorna apenas um registro.
   * `SimpleOrm::FETCH_NONE` → não retorna nada (usado para `INSERT`, `UPDATE`, `DELETE` ou `CREATE TABLE`).
3. **$params** → Array de parâmetros para **prepared statements**, evitando SQL injection.

---

### Exemplos práticos

#### 1️⃣ Criar tabela

```php
SimpleOrm::sql("
    CREATE TABLE IF NOT EXISTS servicos (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        descricao VARCHAR(100) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
", SimpleOrm::FETCH_NONE);
```

#### 2️⃣ Inserir registro

```php
SimpleOrm::sql(
    "INSERT INTO servicos (name, descricao) VALUES (:name, :descricao)",
    SimpleOrm::FETCH_NONE,
    ['name' => 'Barba', 'descricao' => 'Barbo terapia']
);
```

#### 3️⃣ Buscar registros

```php
$servicos = SimpleOrm::sql(
    "SELECT * FROM servicos WHERE descricao = :descricao",
    SimpleOrm::FETCH_ALL,
    ['descricao' => 'Barbo terapia']
);

foreach ($servicos as $s) {
    echo "{$s['id']} - {$s['name']} ({$s['descricao']})<br>";
}
```

#### 4️⃣ Atualizar registro

```php
SimpleOrm::sql(
    "UPDATE servicos SET name = :name WHERE descricao = :descricao",
    SimpleOrm::FETCH_NONE,
    ['name' => 'Barba Atualizada', 'descricao' => 'Barbo terapia']
);
```

#### 5️⃣ Deletar registro

```php
SimpleOrm::sql(
    "DELETE FROM servicos WHERE descricao = :descricao",
    SimpleOrm::FETCH_NONE,
    ['descricao' => 'Barbo terapia']
);
```

---

💡 **Dica:** Usar `SimpleOrm::sql()` é útil quando você precisa de **consultas específicas ou batch**, mas para operações básicas de CRUD, o ORM já possui métodos como:

* `::all()`
* `::retrieveByPK($id)`
* `->save()`
* `->delete()`


