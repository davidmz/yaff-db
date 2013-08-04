<?php

namespace Yaff\db;

use PDO;

/**
 * Сервисный класс для PDO
 */
class Db extends PDO {
    /** @var  Quoter */
    public $quoter;

    public function __construct($dsn, $username = null, $password = null, $driver_options = null) {
        if (!is_array($driver_options)) $driver_options = array();
        if (!isset($driver_options[PDO::ATTR_ERRMODE])) {
            $driver_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        }
        parent::__construct($dsn, $username, $password, $driver_options);

        switch ($this->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            case "pgsql":
                $this->quoter = new QuoterPostgres();
                break;
            default:
                $this->quoter = new QuoterSQL();
        }
    }

    public function justQuery($sqlTpl, array $args = array()) {
        return $this->query($this->quoter->format($sqlTpl, $args));
    }

    public function perform($sqlTpl, array $args = array()) {
        $st = $this->justQuery($sqlTpl, $args);
        $st->closeCursor();
    }

    public function performInsert($tableName, array $values = array()) {
        $st = $this->justQuery('insert into ?t ?#v', array($tableName, $values));
        $st->closeCursor();
    }

    public function getRow($sqlTpl, array $args = array()) {
        $st  = $this->justQuery($sqlTpl, $args);
        $res = $st->fetch(PDO::FETCH_ASSOC);
        $st->closeCursor();
        return $res;
    }

    public function getAll($sqlTpl, array $args = array()) {
        $st  = $this->justQuery($sqlTpl, $args);
        $res = $st->fetchAll(PDO::FETCH_ASSOC);
        $st->closeCursor();
        return $res;
    }

    public function getOne($sqlTpl, array $args = array()) {
        $st  = $this->justQuery($sqlTpl, $args);
        $res = $st->fetchColumn(0);
        $st->closeCursor();
        return $res;
    }

    public function getCol($sqlTpl, array $args = array(), $n = 0) {
        $res = array();
        $st  = $this->justQuery($sqlTpl, $args);
        while ($d = $st->fetchColumn($n)) $res[] = $d;
        $st->closeCursor();
        return $res;
    }

    public function getAssoc($sqlTpl, array $args = array(), $forceArray = false) {
        $res = array();
        $st  = $this->justQuery($sqlTpl, $args);
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $k = array_shift($row);
            if (!$forceArray and count($row) == 1) $row = array_shift($row);
            $res[$k] = $row;
        }
        $st->closeCursor();
        return $res;
    }

}
