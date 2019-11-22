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

- __OCI::__construct__ - Cria a instancia OCI onde representa a conexão com o banco de dados Oracle.
- __OCI::prepare__ - recebe uma sql onde será executada posteriormente
- __OCI::execute__ - executa uma sql
- __OCI::commit__ - faz um commit da sessão atual
- __OCI::rollback__ - faz um rollback da sessão atual
- __OCI::fetch__ - retorna apenas o primeiro registro de uma consulta
- __OCI::fetchAll__ - retorna todos os registros de uma consulta
- __OCI::rowCount__ - informa quantas linhas retornou a ultima consulta
- __OCI::getVersionCliente__ - informa a versão do instantclient do cliente
- __OCI::getVersionServer__ - informa a versão do Oracle do servidor
- __OCI::changePassword__ - altera a senha do usuário informado
- __OCI::getError__ - informa se ocorreu algum erro
- __OCI::getHistory__ - mostra todas as queries que foram executadas na sessão
- __OCI::query__ - executa uma query retornando os registros da consulta
- __OCI::find__ - faz uma consulta rapida em uma tabela, mostra todos os registros
- __OCI::findOne__ - faz um consulta rapida em uma tabela, tras apenas um registro
- __OCI::delete__ - deleta informações de uma tabela
- __OCI::insert__ - adiciona uma informação em uma tabela
- __OCI::update__ - edita uma informação em uma tabela

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

Criar uma __instancia de conexão__ com o banco
```php
    require_once __DIR__ . '/OCI.php';

    $oci = new OCI("DBUSER", "DBPASS", "localhost:1521/xe");
```

Consultar os dados da tabela usuario com __find__
```php
    $dados = $oci->find("USUARIO");

    // OR

    $dados = $oci->find("USUARIO", "id", ['username' => 'foobar@gmail.com']);

    var_dump($dados);
```

Consultar os dados da tabela usuario com __findOne__
```php
    $dados = $oci->findOne("USUARIO");

    // OR

    $dados = $oci->findOne("USUARIO", "*", ['id' => 1]);

    var_dump($dados);
```

Consultar os dados da tabela usuario com __query__
```php
    $dados = $oci->query("SELECT * FROM USUARIO");
    echo "Olá {$dados['username']}";

    // OR

    $dados = $oci->query("SELECT * FROM USUARIO", OCI::FETCH_OBJ);
    echo "Olá {$dados->username}";
```

Consultar dados da tabela usuario com __prepare__
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