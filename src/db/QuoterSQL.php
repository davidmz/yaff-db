<?php
namespace Yaff\db;

class QuoterSQL extends Quoter {

    /**
     * Экранирование идентификатора
     *
     * abc → "abc"
     *
     * @param $v
     * @return string
     * @throws ErrorInvalidValue
     */
    public function quoteIdentifier($v) {
        if (empty($v)) throw new ErrorInvalidValue("Invalid value");
        return '"' . str_replace('"', '""', $v) . '"';
    }

    /**
     * Экранирование строки
     *
     * abc → 'abc'
     *
     * @param $v
     * @return string
     */
    public function quoteString($v) {
        if (is_null($v)) return 'NULL';
        return "'" . str_replace("'", "''", $v) . "'";
    }

    /**
     * Экранирование целого числа
     *
     * 123 → 123
     *
     * @param $v
     * @return string
     */
    public function quoteInt($v) {
        if (is_null($v)) return 'NULL';
        return strval(intval($v));
    }

    /**
     * Экранирование дробного числа
     *
     * 123.4 → 123.4
     *
     * @param $v
     * @return string
     */
    public function quoteFloat($v) {
        if (is_null($v)) return 'NULL';
        return strval(floatval($v));
    }

    /**
     * Экранирование логического выражения
     *
     * @param $v
     * @return string TRUE / FALSE / UNKNOWN
     */
    public function quoteBool($v) {
        if (is_null($v)) return 'UNKNOWN';
        return $v ? 'TRUE' : 'FALSE';
    }

    /**
     * Экранирование с автоопределением типа
     *
     * @param $v
     * @return string
     */
    public function quoteAuto($v) {
        if (is_null($v)) return 'NULL';
        if (is_numeric($v) and fmod((float)$v, 1) !== 0) return $this->quoteFloat($v);
        if (is_numeric($v)) return $this->quoteInt($v);
        if (is_bool($v)) return $this->quoteBool($v);
        return $this->quoteString($v);
    }
}