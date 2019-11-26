<?php
/* Classe OCI
 *
 * Uma classe OCI para manipulação das funções OCI8 do PHP.
 *
 * Author Luiz Schmitt <lzschmitt@gmail.com>
 *
 * Baseado na documentação do OCI no PHP.net
 *
 * Documentação: <https://www.php.net/manual/en/ref.oci8.php>
 *
 */

namespace Lz\PHP;

class OCI
{
    protected $oci       = null;
    protected $statement = null;
    protected $errors    = [];
    protected $history   = [];

    protected $database = null;

    const FETCH_BOTH  = OCI_BOTH+OCI_RETURN_NULLS;
    const FETCH_ASSOC = OCI_ASSOC+OCI_RETURN_NULLS;
    const FETCH_NUM   = OCI_NUM+OCI_RETURN_NULLS;
    const FETCH_LOBS  = OCI_ASSOC+OCI_RETURN_LOBS;

    const TYPE_VARCHAR  = SQLT_CHR;
    const TYPE_INT      = SQLT_INT;
    const TYPE_LONG     = SQLT_LNG;
    const TYPE_LONG2    = SQLT_LBI;
    const TYPE_BOOL     = SQLT_BOL;

    public function __construct($username, $password, $connection_string, $options = [])
    {
        $defaultOptions = [
            'character_set' => 'utf8',
            'session_mode'  => OCI_DEFAULT
        ];

        $options = array_merge($defaultOptions, $options);

        $this->database = $connection_string;

        if (!$this->oci = oci_connect($username, $password, $connection_string, $options['character_set'], $options['session_mode'])) {
            $this->errors[] = oci_error();
        }
    }

    public function __destruct()
    {
        oci_close($this->oci);
    }

    public function commit()
    {
        return oci_commit($this->oci);
    }

    public function rollback()
    {
        return oci_rollback($this->oci);
    }

    public function prepare($sql)
    {
        $this->statement = oci_parse($this->oci, $sql);

        if (!$this->statement) {
            $this->errors[] = oci_error($this->oci);
        }

        return $this;
    }

    public function getVersionClient()
    {
        return oci_client_version();
    }

    public function getVersionServer()
    {
        return oci_server_version($this->oci);
    }

    public function changePassword($username, $password, $new_password, $database = null)
    {
        if (is_null($database)) {
            $database = $this->database;
        }

        $oci = oci_pconnect($username, $password, $database);
        if (!$oci) {
            $m = oci_error();
            $this->errors[] = oci_error();

            // "ORA-28001: the password has expired"
            if ($m['code'] == 28001) { 
                $oci = oci_password_change($database, $username, $password, $new_password);
                if ($oci) {
                    return true;
                }
            }
        }

        // The original error wasn't 28001, or the password change failed
        if (!$oci) {
            $this->errors[] = oci_error();
        }

        return false;
    }

    public function bindValue($key, $value, $size = -1, $type = self::TYPE_VARCHAR)
    {
        switch(true) {
            case is_bool($value):
                $type = self::TYPE_BOOL;
            break;

            case is_numeric($value):
                $type = self::TYPE_INT;
            break;

            case is_string($value):
                $size = strlen($value);
                $type = self::TYPE_VARCHAR;
            break;

            default:
                $type = self::TYPE_VARCHAR;
        }

        return oci_bind_by_name($this->statement, $key, $value, $size, $type);
    }

    public function getError()
    {
        return $this->errors;
    }

    public function getHistory()
    {
        return $this->history;
    }

    protected function setHistory($data)
    {
        $this->history[] = $data;
    }

    public function rowCount()
    {
        return $this->rows ? $this->rows : 0;
    }

    public function execute()
    {
        if (!oci_execute($this->statement)) {
            $this->errors[] = oci_error($this->statement);
            return false;
        }

        return true;
    }

    public function fetch($fetch_mode = self::FETCH_ASSOC)
    {
        $data[] = oci_fetch_array($this->statement, $fetch_mode);
        oci_free_statement($this->statement);
        $this->rows = count($data);

        return $data[0];
    }

    public function fetchAll($fetch_mode = self::FETCH_ASSOC)
    {
        while( ($data[] = oci_fetch_array($this->statement, $fetch_mode)) != false);
        oci_free_statement($this->statement);
        array_pop($data);
        $this->rows = count($data);

        return $data;
    }

    public function query($sql, $fetch_mode = self::FETCH_ASSOC)
    {
        $sth = $this->prepare($sql);
        $sth->execute();
        //verificar se houve erro
        return $sth->fetchAll($fetch_mode);
    }

    public function find($table, $fields = '*', $where = null)
    {
        $fields = $this->prepareColumns($fields);
        $where  = $this->prepareConditions($where);

        $sql = trim("SELECT {$fields} FROM {$table} $where");

        $sth = $this->prepare($sql);
        $sth->execute();

        $this->setHistory($sql);

        return $sth->fetchAll();
    }

    public function findOne($table, $fields = '*', $where = null)
    {
        $data = $this->find($table, $fields, $where)[0];
        $this->rows = 1;

        return $data;
    }

    public function f_g($table, $params = null)
    {
        $sth = $this->prepare("SELECT $table($params) AS V_OUTPUT FROM DUAL");
        $sth->execute();

        return $sth->fetch()['V_OUTPUT'];
    }

    public function delete($table, $conditions = null)
    {
        $conditions = $this->prepareConditions($conditions);

        $sql = trim("DELETE FROM {$table} {$conditions}");
        $this->setHistory($sql);

        $sth = $this->prepare($sql);
        return $sth->execute();
    }

    public function insert($table, $data)
    {
        $fields = implode(',', array_keys($data));
        $values = ':'.implode(',:', array_keys($data));

        $sql = trim("INSERT INTO {$table} ($fields) VALUES ($values) ");

        $sth = $this->prepare($sql);
        
        $this->prepareFields($data, $sql);

        return $sth->execute();
    }

    public function update($table, $data, $where = null)
    {
        $where = $this->prepareConditions($where);

        foreach ($data as $field => $value) {
            $settings[] = " {$field} = :{$field} ";
        }

        $settings = implode(',', $settings);

        $sql = trim("UPDATE {$table} SET $settings $where");
        $sth = $this->prepare($sql);

        $this->prepareFields($data, $sql);

        return $sth->execute();
    }

    protected function prepareColumns($fields)
    {
        if (is_array($fields)) {
            $fields = implode(',', $fields);
        }

        return $fields;
    }

    protected function prepareFields($data, $sql = null)
    {
        foreach ($data as $field => $value) {
            $this->bindValue(":$field", $value, strlen($value));
            $sql = str_replace(":$field", "'$value'", $sql);
        }

        $this->setHistory($sql);

        return $data;
    }

    protected function prepareConditions($conditions, $force = false)
    {
        if (is_array($conditions)) {
            foreach ($conditions as $field => $value) {

                $field = addslashes($field);
                $value = addslashes($value);

                $sign = ($force) ? ' = ' : ' LIKE ';

                $condition[] = " $field $sign '$value' ";
            }

            $conditions = " WHERE " . implode(' AND ', $condition);
        }

        return $conditions;
    }
}