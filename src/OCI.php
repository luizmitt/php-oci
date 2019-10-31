<?php

class OCI
{
    protected $oci = null;
    protected $statement = null;
    protected $errors = [];

    const FETCH_BOTH  = OCI_BOTH+OCI_RETURN_NULLS;
    const FETCH_ASSOC = OCI_ASSOC+OCI_RETURN_NULLS;
    const FETCH_NUM   = OCI_NUM+OCI_RETURN_NULLS;
    const FETCH_LOBS  = OCI_ASSOC+OCI_RETURN_LOBS;

    public function __construct($username, $password, $connection_string, $character_set = 'utf8', $session_mode = OCI_DEFAULT)
    {
        if (!$this->oci = oci_connect($username, $password, $connection_string, $character_set, $session_mode)) {
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

    public function getError()
    {
        return $this->errors;
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

    public function find($table, $fields = '*', $where = null)
    {
        if (is_array($fields)) {
            $fields = implode(',', $fields);
        }

        if (is_array($where)) {
            foreach ($where as $field => $value) {
                $field = addslashes($field);
                $value = addslashes($value);

                $condition[] = " $field LIKE '$value' ";
            }

            $where = implode(' AND ', $condition);
        }

        $sth = $this->prepare("SELECT {$fields} FROM {$table} $where");
        $sth->execute();

        return $sth->fetchAll();
    }

    protected function prepareColumns($columns)
    {

    }

    protected function prepareFields($fields)
    {

    }

    protected function prepareConditions($conditions)
    {

    }
}

$oci = new OCI("LUIZ_SCHMITT", "123456", "curuduri:1521/pmmdev");

$sth = $oci->prepare("DELETE FROM LUIZ_SCHMITT.FORTEST");
$sth->execute();


$sth = $oci->prepare("SELECT * FROM LUIZ_SCHMITT.FORTEST");
$error = $sth->execute();
$retorno = $sth->fetchAll();

$sth = $oci->prepare("INSERT INTO LUIZ_SCHMITT.FORTEST (TX_TESTE) VALUES ('AAAA')");
$sth->execute();

var_dump($oci->getError());
