# PHP OCI
Requer (PHP 7, OCI8, instantclient)

## Introdução
Uma simples classe para facilitar a manipulação das funcionalidades do php oci8 com o banco de dados Oracle.

# Sinopse da Classe

```php
protected function prepareColumns($fields);
protected function prepareFields($data, $sql = null);
protected function prepareConditions($conditions, $force = false);
protected function setHistory();

public function prepare($sql);
public function execute();

public function commit(void);
public function rollback(void);

public function fetch($fetch_mode);
public function fetchAll($fetch_mode);
public function rowCount();

public function getVersionCliente(void);
public function getVersionServer(void);

public function changePassword($username, $password, $new_password, $database);

public function getError();
public function getHistory();

public function query($sql, $fetch_mode);
public function find($table, $fields, $where);
public function findOne($table, $fields, $where);
public function delete($table, $conditions = null);
public function insert($table, $data);
public function update($table, $data, $where = null);
```

# Detalhes

- **OCI::__construct** - Cria a instancia OCI onde representa a conexão com o banco de dados Oracle.
- **OCI::prepare** - recebe uma sql onde será executada posteriormente
- **OCI::execute** - executa uma sql
- **OCI::commit** - faz um commit da sessão atual
- **OCI::rollback** - faz um rollback da sessão atual
- **OCI::fetch** - retorna apenas o primeiro registro de uma consulta
- **OCI::fetchAll** - retorna todos os registros de uma consulta
- **OCI::rowCount** - informa quantas linhas retornou a ultima consulta
- **OCI::getVersionCliente** - informa a versão do instantclient do cliente
- **OCI::getVersionServer** - informa a versão do Oracle do servidor
- **OCI::changePassword** - altera a senha do usuário informado
- **OCI::getError** - informa se ocorreu algum erro
- **OCI::getHistory** - mostra todas as queries que foram executadas na sessão
- **OCI::query** - executa uma query retornando os registros da consulta
- **OCI::find** - faz uma consulta rapida em uma tabela, mostra todos os registros
- **OCI::findOne** - faz um consulta rapida em uma tabela, tras apenas um registro
- **OCI::delete** - deleta informações de uma tabela
- **OCI::insert** - adiciona uma informação em uma tabela
- **OCI::update** - edita uma informação em uma tabela
- **OCI::call** - chama uma procedure ou função

## Tipos de Fetch

- **OCI::FETCH_BOTH** - retorno numerico (indice) com array
- **OCI::FETCH_ASSOC** - apenas retorno em array
- **OCI::FETCH_NUM** - apenas retorno numerico (indice)
- **OCI::FETCH_LOBS** - retorno em array com lobs (caso exista)
- **OCI::FETCH_OBJ** - retorno em objeto

# Exemplos

tabela de exemplo:
```sql
...
    CREATE TABLE USUARIO (
        "id" NUMBER(5,0) NOT NULL ENABLE,
        "username" VARCHAR2(80) NOT NULL ENABLE,
        "password" VARCHAR2(100) NOT NULL ENABLE
    )
....
```

Criar uma **instancia de conexão** com o banco
```php
    require_once __DIR__ . '/OCI.php';

    $oci = new \Lz\PHP\OCI("DBUSER", "DBPASS", "localhost:1521/xe");
```

Consultar os dados da tabela usuario com **find**
```php
    $dados = $oci->find("USUARIO");

    // OR

    $dados = $oci->find("USUARIO", "id", ['username' => 'foobar@gmail.com']);

    var_dump($dados);
```

Consultar os dados da tabela usuario com **findOne**
```php
    $dados = $oci->findOne("USUARIO");

    // OR

    $dados = $oci->findOne("USUARIO", "*", ['id' => 1]);

    var_dump($dados);
```

Consultar os dados da tabela usuario com **query**
```php
    $dados = $oci->query("SELECT * FROM USUARIO");
    echo "Olá {$dados['username']}";

    // OR

    $dados = $oci->query("SELECT * FROM USUARIO", OCI::FETCH_OBJ);
    echo "Olá {$dados->username}";
```

Consultar dados da tabela usuario com **prepare**
```php
    $sth = $oci->prepare("SELECT * FROM USUARIO");
    $sth->execute();
    $dados = $sth->fetchAll();

    // OR

    $sth = $oci->prepare("SELECT * FROM USUARIO");
    $sth->execute();
    $dados = $sth->fetch();
    
    // OR

    $sth = $oci->prepare("SELECT * FROM USUARIO WHERE id = :id");
    $sth->bindValue(":id", 1);
    $sth->execute();
    $dados = $sth->fetchAll();

    var_dump($dados);
```

Deletar dados da tabela usuario com **prepare** e **delete**
```php
    $sth = $oci->prepare("DELETE FROM USUARIO WHERE id = :id");
    $sth->bindValue(":id", 1);
    $sth->execute();

    // OR

    $oci->delete("USUARIO", ['id' => 1]);
```

Inserir dados na tabela usuario com **prepare** e **insert**
```php
    $sth = $oci->prepare("INSERT INTO USUARIO (id, username, password) VALUES (:id, :username, :password)");
    $sth->bindValue(":id", 2);
    $sth->bindValue(":username", "usuario2");
    $sth->bindValue(":password", "password2");
    $sth->execute();

    // OR

    $oci->insert("USUARIO", [
        "id" => 2,
        "username" => "usuario2",
        "password" => "password2"
    ]);
```

Atualizar dados na tabela usuario com **prepare** e **update**
```php
    $sth = $oci->prepare("UPDATE USUARIO SET password=:password WHERE id=:id");
    $sth->bindValue(":password", "outraSenha");
    $sth->bindValue(":id", 1);
    $sth->execute();

    // OR

    $oci->update("USUARIO", 
    [
        "password" => "outraSenha"
    ], 
    [
        "id" => 1
    ]);
```

Verificando Erros com **getError()**
```php
    $sth = $oci->prepare("DELETE FROM USUARIO WHERE id = :id");
    $sth->bindValue(":id", 1);
    $sth->execute();

    if ($error = $sth->getError()) {
        var_dump($error);
    }

    // OR

    $oci->delete("USUARIO", ['id' => 1]);

    if ($error = $oci->getError()) {
        var_dump($error);
    }    
```