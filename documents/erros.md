

# ⚙️ Tutorial: Ativando o Driver PDO PostgreSQL (`pdo_pgsql`)

O erro **`could not find driver`** para PostgreSQL indica que a extensão necessária (`pdo_pgsql`) não está instalada ou ativada no seu ambiente PHP. A solução depende do seu sistema operacional e de como o PHP está configurado.

## Passo 1: Localizar o Arquivo `php.ini`

Antes de tudo, você precisa editar o arquivo `php.ini` que o seu **servidor web** (Apache/Nginx) está realmente utilizando.

1.  Crie um arquivo chamado `info.php` no seu diretório de arquivos web (`htdocs`, `www`, etc.) com o conteúdo `<?php phpinfo(); ?>`.
2.  Acesse esse arquivo pelo navegador.
3.  Procure pela linha **"Loaded Configuration File"** ou **"Arquivo de Configuração Carregado"**. O caminho listado é o arquivo exato que você deve editar.

-----

## 🛠️ Cenário A: Windows (XAMPP / WAMP)

No Windows, o driver geralmente já está incluído na sua instalação (XAMPP/WAMP), mas está desativado por padrão.

### A.1. Edite o `php.ini`

1.  Abra o arquivo `php.ini` que você localizou no Passo 1.

2.  Use a função de busca (Ctrl+F) para encontrar as linhas que mencionam `pgsql`. Elas estarão comentadas com um ponto e vírgula (`;`) no início.

3.  **Remova o ponto e vírgula (`;`)** das seguintes linhas para ativar os drivers:

    ```ini
    ; Ative o driver nativo do PostgreSQL
    extension=pgsql

    ; Ative o driver PDO para PostgreSQL (OBRIGATÓRIO para o SimpleOrm)
    extension=pdo_pgsql
    ```

    > **Nota sobre o Diretório:** Garanta que a linha `extension_dir` aponte corretamente para a pasta de extensões (`C:\xampp\php\ext` ou similar).

### A.2. Reinicie os Serviços

1.  **Salve** o arquivo `php.ini`.
2.  Abra o Painel de Controle do XAMPP ou WAMP.
3.  **Reinicie o serviço do Apache** (ou Nginx) para que o PHP recarregue as configurações.

-----

## 💻 Cenário B: Linux (Debian / Ubuntu)

No Linux, você precisa instalar o driver como um pacote de sistema e então reiniciar o serviço de aplicação.

### B.1. Instale o Pacote do Driver

Use o gerenciador de pacotes `apt` para instalar o driver PDO para PostgreSQL. Você deve especificar a versão correta do PHP que está usando (ex: `8.3` ou `8.1`).

1.  Atualize a lista de pacotes e instale o driver:

    ```bash
    # Atualiza a lista de pacotes
    sudo apt update

    # Instala o driver PDO para PostgreSQL (Use sua versão do PHP, ex: php8.3)
    sudo apt install php8.3-pgsql
    ```

2.  O comando de instalação geralmente ativa a extensão automaticamente no arquivo de configuração do PHP-FPM.

### B.2. Reinicie os Serviços

É crucial reiniciar o serviço que gerencia o PHP (geralmente PHP-FPM) e, em alguns casos, o servidor web.

1.  **Reinicie o PHP-FPM:**

    ```bash
    # Use sua versão do PHP (e.g., php8.3-fpm)
    sudo service php8.3-fpm restart
    ```

2.  Se você estiver usando **Apache**, reinicie-o também:

    ```bash
    sudo service apache2 restart
    ```

-----

## Passo Final: Verificação

Para confirmar que a ativação foi bem-sucedida, recarregue o arquivo `info.php` no seu navegador e procure pela seção **"pdo\_drivers"**.

Se você vir as entradas **`pgsql`**, o driver está carregado e o erro `could not find driver` será resolvido, permitindo que a classe `SimpleOrm` se conecte ao PostgreSQL.