<?php

class OCI 
{
    protected $oci = null;
    protected $stmt = null;
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

    public function getError()
    {
        return $this->errors;
    }

    public function commit()
    {
        oci_commit($this->oci);
        return $this;
    }

    public function prepare($sql)
    {
        $this->stmt = oci_parse($this->oci, $sql);

        return $this;
    }

    public function execute()
    {
        oci_execute($this->stmt);

        if ($e = oci_error($this->stmt)) {
            $this->errors[] = $e;
        }
        
        return $this;
    }

    public function fetch($fetch_mode = self::FETCH_ASSOC)
    {
        $data[] = oci_fetch_array($this->stmt, $fetch_mode);
        oci_free_statement($this->stmt);
        $this->rows = count($data);
        return $data[0];
    }

    public function fetchAll($fetch_mode = self::FETCH_ASSOC)
    {
        while( ($data[] = oci_fetch_array($this->stmt, $fetch_mode)) != false);
        oci_free_statement($this->stmt);
        array_pop($data);
        $this->rows = count($data);
        return $data;
    }

    public function rowsCount()
    {
        return $this->rows ? $this->rows : 0;
    }
}

$oci = new OCI("LUIZ_SCHMITT", "123456", "curuduri:1521/pmmdev");


$stmt = $oci->prepare("SELECT * FROM LUIZ_SCHMITT.FORTEST")
            ->execute()
            ->fetchAll();

$stmt = $oci->prepare("INSERT INTO LUIZ_SCHMITT.FORTEST (TX_TESTE) VALUES ('AAAA')")
            ->execute();

var_dump($stmt);