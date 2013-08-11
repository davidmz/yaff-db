<?php
namespace Yaff\db;

use PDO;
use Yaff\db\ErrorUnknownPlaceholderType;
use Yaff\db\ErrorUnmatched;

abstract class Quoter {

    protected $phTypes = array(
        "t"    => "Identifier",
        "s"    => "String",
        "i"    => "Int",
        "f"    => "Float",
        "b"    => "Bool",
        "auto" => "Auto",
    );

    // Минимальный набор квотеров

    abstract public function quoteIdentifier($v, $phType = "");

    abstract public function quoteString($v, $phType = "");

    abstract public function quoteAuto($v, $phType = "");

    abstract public function quoteInt($v, $phType = "");

    abstract public function quoteFloat($v, $phType = "");

    abstract public function quoteBool($v, $phType = "");

    /**********************************/

    /**
     * @param string $query
     * @param array $args
     * @throws ErrorInvalidValue
     * @throws ErrorUnmatched
     * @return string
     */
    public final function format($query, array $args = array()) {
        $parts    = preg_split('/(
            (?: [a-z_][a-z0-9_]* | [1-9][0-9]* )?
            (?:
                (?<!\\?)\\?(?!\\?)
                [!@\\#]? [a-z]*
            )
        )/uix', $query, -1, PREG_SPLIT_DELIM_CAPTURE);
        $chunks   = array_chunk($parts, 2);
        $out      = array();
        $phIndex  = 1;
        $usedKeys = array();
        foreach ($chunks as $chunk) {
            list($text, $placeholder) = $chunk;
            if (is_null($placeholder)) { // последний элемент
                $out[] = $this->unEscapeText($text);
                break;
            }

            preg_match('/
                ( [a-z_][a-z0-9_]* | [1-9][0-9]* )?
                (?:
                    \\?
                    ([!@\\#])? ([a-z]*)
                )
            /uix', $placeholder, $m);

            list(, $name, $listType, $type) = $m;
            if ($type == "") $type = "auto";
            if ($name == "") $name = $phIndex++;

            $key = is_numeric($name) ? intval($name) - 1 : $name;
            if (!array_key_exists($key, $args)) throw new ErrorUnmatched("Key not found: '{$key}'");
            $usedKeys[] = $key;

            $val = $args[$key];

            if ($listType == "!") {
                // RAW
                $escaped = strval($val);

            } elseif ($listType == "#" and $type == "v") {
                // #v
                if (!is_array($val)) throw new ErrorInvalidValue("Invalid value (hash expected)");
                if (empty($val)) {
                    $escaped = "default values";
                } else {
                    $keys   = array();
                    $values = array();
                    foreach ($val as $k => $v) {
                        $keys[]   = $this->quoteIdentifier($k);
                        $values[] = $this->quoteAuto($v);
                    }
                    $escaped = "(" . join(", ", $keys) . ") values (" . join(", ", $values) . ")";
                }

            } elseif ($listType == "#" and $type == "u") {
                // #u
                if (!is_array($val) or empty($val)) throw new ErrorInvalidValue("Invalid value (non-empty hash expected)");
                $pairs = array();
                foreach ($val as $k => $v) {
                    $pairs[] = $this->quoteIdentifier($k) . " = " . $this->quoteAuto($v);
                }
                $escaped = join(", ", $pairs);

            } elseif ($listType == "@") {
                // @
                $quoter = $this->getTypeQuoter($type);
                if (!is_array($val) or empty($val)) {
                    $escaped = call_user_func($quoter, null);
                } else {
                    $values = array();
                    foreach ($val as $v) {
                        $values[] = call_user_func($quoter, $v, $type);
                    }
                    $escaped = join(", ", $values);
                }

            } else {
                $quoter  = $this->getTypeQuoter($type);
                $escaped = call_user_func($quoter, $val, $type);

            }

            if ($this->isNullResult($escaped)) {
                $text = preg_replace(
                    array(
                         '/\s+=\s*$/',
                         '/(?<![<>!])=\s*$/',
                         '/\s*<>\s*$/',
                    ),
                    array(
                         ' IS ',
                         ' IS ',
                         ' IS NOT ',
                    ),
                    $text
                );
            }
            $out[] = $this->unEscapeText($text);
            $out[] = $escaped;
        }

        $unusedKeys = array_diff(array_keys($args), $usedKeys);
        if (!empty($unusedKeys)) throw new ErrorUnmatched("Args list have unused keys: " . join(", ", $unusedKeys));

        return join("", $out);
    }

    protected function isNullResult($escaped) {
        return (strtoupper($escaped) == "NULL" or strtoupper($escaped) == "UNKNOWN");
    }

    protected function getTypeQuoter($type) {
        if (!isset($this->phTypes[$type])) throw new ErrorUnknownPlaceholderType("UnknownType: '{$type}'");
        $typeMethod = array($this, "quote" . $this->phTypes[$type]);
        if (!is_callable($typeMethod)) throw new ErrorUnknownPlaceholderType("UnknownType: '{$type}'");
        return $typeMethod;
    }

    private function unEscapeText($text) {
        return str_replace("??", "?", $text);
    }
}