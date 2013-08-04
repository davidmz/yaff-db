<?php
namespace Yaff\db;

class QuoterPostgres extends QuoterSQL {
    /**
     * Экранирование идентификатора
     *
     * ab.c → "ab"."c"
     *
     * @param $v
     * @return string
     */
    public function quoteIdentifier($v) {
        $parts = explode(".", strval($v));
        foreach ($parts as &$p) $p = parent::quoteIdentifier($p);
        unset($p);
        return join(".", $parts);
    }
}