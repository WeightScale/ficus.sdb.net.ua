<?php

namespace accounting;

use Exception;
use mysqli;

define("__REMOTE_HOST__","golf.cityhost.com.ua");

class Database{
    public mysqli $mysqli;
    private $result;
    private $row;

    function __construct($database, $password) {
        try {
            $this->mysqli = new mysqli($this->local()? "sdb.net.ua" : "localhost", $database, $password, $database);
            //$this->mysqli = new mysqli("sdb.net.ua", $database, $password, $database);
            $this->mysqli->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
            if ($this->mysqli->connect_error)
                trigger_error($this->mysqli->connect_error);
            if (!$this->mysqli->set_charset("utf8"))
                trigger_error($this->mysqli->error);
        }catch (Exception $e){
            trigger_error($e->getMessage());
        }
    }
    function close(): void{
        if (!$this->mysqli->close())
            trigger_error($this->mysqli->error);
    }
    function transaction(): void{
        if (!$this->mysqli->begin_transaction())
            trigger_error($this->mysqli->error);
    }
    function commit(): void{
        if (!$this->mysqli->commit())
            trigger_error($this->mysqli->error);
    }
//	function error(): string
//    {
//	    return $this->mysqli->error;
//    }
    function get($column) {
        return $this->row[$column];
    }
    function get_object(): object {
        return (object)$this->row;
    }
    function get_array(): array{
        $array_ = [];
        while ($this->next())
            $array_[] = $this->get_object();
        return $array_;
    }
    function next() {
        return $this->row = $this->result->fetch_assoc();
    }
    function counts(){
        return $this->result->num_rows;
    }
    function query($sql, ...$values) {
        if ($values) {
            $sql_ = $sql;
            $sql = "";
            for ($i = 0, $v = 0; $i < strlen($sql_); $i++)
                if ($sql_[$i] == "?") {
                    if (is_null($values[$v]))
                        $values[$v] = "NULL";
                    elseif (is_string($values[$v]))
                        $values[$v] = "'" . $this->mysqli->escape_string($values[$v]) . "'";
                    elseif (is_bool($values[$v]))
                        $values[$v] = $values[$v]? "TRUE" : "FALSE";
                    $sql .= $values[$v++];
                } else
                    $sql .= $sql_[$i];
        }
        $this->result = $this->mysqli->query($sql);
        if ($this->mysqli->error){
            trigger_error($this->mysqli->error . " in " . $sql);
        }
        return $this->mysqli->insert_id;
    }

    /**
     * @throws Exception
     */
    function query_ex($sql, ...$values) {
        if ($values) {
            $sql_ = $sql;
            $sql = "";
            for ($i = 0, $v = 0; $i < strlen($sql_); $i++)
                if ($sql_[$i] == "?") {
                    if (is_null($values[$v]))
                        $values[$v] = "NULL";
                    elseif (is_string($values[$v]))
                        $values[$v] = "'" . $this->mysqli->escape_string($values[$v]) . "'";
                    elseif (is_bool($values[$v]))
                        $values[$v] = $values[$v]? "TRUE" : "FALSE";
                    $sql .= $values[$v++];
                } else
                    $sql .= $sql_[$i];
        }
        $this->result = $this->mysqli->query($sql);
        if ($this->mysqli->error){
            throw new Exception($this->mysqli->error,$this->mysqli->errno);
        }
        return $this->mysqli->insert_id;
    }

    function local(): bool {
        return gethostname() != __REMOTE_HOST__;
    }
}

class FicusDatabase extends Database
{
    function __construct(){
        parent::__construct("ch1edfe7b1_ficus", "CswwYI4TVJ");
    }
}