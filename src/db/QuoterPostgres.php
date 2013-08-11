<?php
namespace Yaff\db;

class QuoterPostgres extends QuoterSQL {
    /**
     * Экранирование идентификатора
     *
     * ab.c → "ab"."c"
     *
     * @param mixed $v значение, которое надо заквотить
     * @param string $phType строка-тип, использованная в метке
     * @return string
     */
    public function quoteIdentifier($v, $phType = "") {
        $parts = explode(".", strval($v));
        foreach ($parts as &$p) $p = parent::quoteIdentifier($p);
        unset($p);
        return join(".", $parts);
    }

    /**
     * Экранирование массива с типом
     * Синтаксис: ?A<type>, т. е. ?Ai, ?As и так далее
     *
     * array("a", "b", "c") → Array['a', 'b', 'c']
     *
     * Алгоритм такой же как и у @-метки, с одним исключением — пустой массив не заменяется на NULL
     *
     * @param mixed $val значение, которое надо заквотить
     * @param string $phType строка-тип, использованная в метке
     * @return string
     */
    public function quoteArray($val, $phType = "") {
        preg_match('/^A([sifb]?)$/', $phType, $m);
        $quoter = $this->getTypeQuoter($m[1]);
        if (is_null($val) or !is_array($val)) {
            return 'NULL';
        } else {
            $values = array();
            foreach ($val as $v) {
                $values[] = call_user_func($quoter, $v);
            }
            $escaped = join(", ", $values);
        }
        return "ARRAY[{$escaped}]";
    }

    protected function getTypeQuoter($type) {
        try {
            return parent::getTypeQuoter($type);
        } catch (ErrorUnknownPlaceholderType $e) {
            if (preg_match('/^A[sifb]?$/', $type, $m)) {
                return array($this, 'quoteArray');
            } else {
                throw $e;
            }
        }
    }


}