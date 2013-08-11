<?php
/**
 * Created by JetBrains PhpStorm.
 * User: David
 * Date: 16.04.13
 * Time: 14:29
 * To change this template use File | Settings | File Templates.
 */

use Yaff\db\Quoter;
use Yaff\db\QuoterSQL;
use Yaff\db\QuoterPostgres;

require_once __DIR__ . "/../src/autoload.php";

class TestQuoter extends PHPUnit_Framework_TestCase {
    public function testSQLQuoter() {
        $q = new QuoterSQL();
        $this->assertEquals("NULL", $q->quoteString(null));
        $this->assertEquals("'abcd''ef'", $q->quoteString("abcd'ef"));
        $this->assertEquals("12", $q->quoteInt("12"));
    }

    public function testScalars() {
        $q   = new QuoterSQL();
        $res = $q->format(
            "select * from table?t where id = ?i and active = 2? and login = 3?s",
            array("table" => "users", 12, true, "d'ivan")
        );
        $this->assertEquals(
            "select * from \"users\" where id = 12 and active = TRUE and login = 'd''ivan'",
            $res
        );
    }

    public function testList() {
        $q   = new QuoterSQL();
        $res = $q->format(
            "select * from users where id in (?@i) or login in (?@s) or name in (?@) or active in (?@)",
            array(
                 array(1, 2, "3", "foo"), array("vasya", "petya", 324234), array(1, "2", "cat", true, null), "dsdd"
            )
        );
        $this->assertEquals(
            "select * from users where id in (1, 2, 3, 0) or login in ('vasya', 'petya', '324234') or name in (1, 2, 'cat', TRUE, NULL) or active in (NULL)",
            $res
        );
    }

    public function testInsert() {
        $q   = new QuoterSQL();
        $res = $q->format(
            "insert into users ?#v", array(
                                          array(
                                              "login"  => "vasya",
                                              "name"   => "Petya",
                                              "active" => true,
                                              "rank"   => 1.0
                                          )
                                     )
        );
        $this->assertEquals(
            "insert into users (\"login\", \"name\", \"active\", \"rank\") values ('vasya', 'Petya', TRUE, 1)",
            $res
        );
    }

    public function testUpdate() {
        $q   = new QuoterSQL();
        $res = $q->format(
            "update users set ?#u where id = ?i", array(
                                                       array(
                                                           "login"  => "vasya",
                                                           "name"   => "Petya",
                                                           "active" => true,
                                                           "rank"   => 2.0
                                                       ),
                                                       5
                                                  )
        );
        $this->assertEquals(
            "update users set \"login\" = 'vasya', \"name\" = 'Petya', \"active\" = TRUE, \"rank\" = 2 where id = 5",
            $res
        );
    }

    public function testRaw() {
        $q   = new QuoterSQL();
        $res = $q->format("update users set ?!", array("dad'sdadsa\"?!!?!!'"));
        $this->assertEquals(
            "update users set dad'sdadsa\"?!!?!!'",
            $res
        );
    }

    public function testEmptyList() {
        $q   = new QuoterSQL();
        $res = $q->format("?@", array(1));
        $this->assertEquals("NULL", $res);
    }

    public function testEmptyHashUpdate() {
        $this->setExpectedException("Yaff\\db\\ErrorInvalidValue", "Invalid value (non-empty hash expected)");
        $q = new QuoterSQL();
        $q->format("?#u", array(1));
    }

    public function testEmptyHashValues() {
        $q = new QuoterSQL();
        $res = $q->format("?#v", array(array()));
        $this->assertEquals("default values", $res);
    }

    public function testInvalidHashValues() {
        $this->setExpectedException("Yaff\\db\\ErrorInvalidValue", "Invalid value (hash expected)");
        $q = new QuoterSQL();
        $q->format("?#v", array(1));
    }

    public function testNull() {
        $q   = new QuoterSQL();
        $res = $q->format("a = ?", array(null));
        $this->assertEquals("a IS NULL", $res);
        $res = $q->format("a <> ?", array(null));
        $this->assertEquals("a IS NOT NULL", $res);
        $res = $q->format("a >= ?", array(null));
        $this->assertEquals("a >= NULL", $res);
    }

    public function testQuestion() {
        $q   = new QuoterSQL();
        $res = $q->format("a = ??");
        $this->assertEquals("a = ?", $res);
    }

    public function testPostgresIdentifier() {
        $q = new QuoterPostgres();
        $this->assertEquals('"a"."b"', $q->quoteIdentifier('a.b'));
    }

    public function testPostgresArray() {
        $q = new QuoterPostgres();
        $res = $q->format("?Ai", array(array(1,2,3)));
        $this->assertEquals("ARRAY[1, 2, 3]", $res);
        $res = $q->format("?Ai", array(array()));
        $this->assertEquals("ARRAY[]", $res);
        $res = $q->format("?Ai", array(1));
        $this->assertEquals("NULL", $res);
    }

}
