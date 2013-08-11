<?php
namespace Yaff\db;

class QuoterSQL extends Quoter {

    /**
     * Экранирование идентификатора
     *
     * abc → "abc"
     *
     * @param mixed $v значение, которое надо заквотить  
     * @param string $phType строка-тип, использованная в метке
     * @throws ErrorInvalidValue
     * @return string
     */
    public function quoteIdentifier($v, $phType = "") {
        if (empty($v)) throw new ErrorInvalidValue("Invalid value");
        return '"' . str_replace('"', '""', $v) . '"';
    }

    /**
     * Экранирование строки
     *
     * abc → 'abc'
     *
     * @param mixed $v значение, которое надо заквотить 
     * @param string $phType строка-тип, использованная в метке
     * @return string
     */
    public function quoteString($v, $phType = "") {
        if (is_null($v)) return 'NULL';
        return "'" . str_replace("'", "''", $v) . "'";
    }

    /**
     * Экранирование целого числа
     *
     * 123 → 123
     *
     * @param mixed $v значение, которое надо заквотить 
     * @param string $phType строка-тип, использованная в метке
     * @return string
     */
    public function quoteInt($v, $phType = "") {
        if (is_null($v)) return 'NULL';
        return strval(intval($v));
    }

    /**
     * Экранирование дробного числа
     *
     * 123.4 → 123.4
     *
     * @param mixed $v значение, которое надо заквотить 
     * @param string $phType строка-тип, использованная в метке
     * @return string
     */
    public function quoteFloat($v, $phType = "") {
        if (is_null($v)) return 'NULL';
        return strval(floatval($v));
    }

    /**
     * Экранирование логического выражения
     *
     * @param mixed $v значение, которое надо заквотить 
     * @param string $phType строка-тип, использованная в метке
     * @return string TRUE / FALSE / UNKNOWN
     */
    public function quoteBool($v, $phType = "") {
        if (is_null($v)) return 'UNKNOWN';
        return $v ? 'TRUE' : 'FALSE';
    }

    /**
     * Экранирование с автоопределением типа
     *
     * @param mixed $v значение, которое надо заквотить 
     * @param string $phType строка-тип, использованная в метке
     * @return string
     */
    public function quoteAuto($v, $phType = "") {
        if (is_null($v)) return 'NULL';
        if (is_numeric($v) and fmod((float)$v, 1) !== 0) return $this->quoteFloat($v);
        if (is_numeric($v)) return $this->quoteInt($v);
        if (is_bool($v)) return $this->quoteBool($v);
        return $this->quoteString($v);
    }
}