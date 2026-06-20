--TEST--
PDO_duckdb: Test union
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$statement = $db->query("SELECT [union_value(num := 2), union_value(str := 'ABC')::UNION(str VARCHAR, num INTEGER)] as union");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$db->exec("CREATE TABLE tbl1 (u UNION(num INTEGER, str VARCHAR))");
$db->exec("INSERT INTO tbl1 VALUES (1), ('two'), (union_value(str := 'three'))");
$statement = $db->query("SELECT u, u.str, union_extract(u, 'str') AS str2, union_tag(u) AS t FROM tbl1");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

?>
--EXPECTF--
array(1) {
  [0]=>
  array(1) {
    ["union"]=>
    array(2) {
      [0]=>
      array(1) {
        ["num"]=>
        int(2)
      }
      [1]=>
      array(1) {
        ["str"]=>
        string(3) "ABC"
      }
    }
  }
}
array(3) {
  [0]=>
  array(4) {
    ["u"]=>
    array(1) {
      ["num"]=>
      int(1)
    }
    ["str"]=>
    NULL
    ["str2"]=>
    NULL
    ["t"]=>
    string(3) "num"
  }
  [1]=>
  array(4) {
    ["u"]=>
    array(1) {
      ["str"]=>
      string(3) "two"
    }
    ["str"]=>
    string(3) "two"
    ["str2"]=>
    string(3) "two"
    ["t"]=>
    string(3) "str"
  }
  [2]=>
  array(4) {
    ["u"]=>
    array(1) {
      ["str"]=>
      string(5) "three"
    }
    ["str"]=>
    string(5) "three"
    ["str2"]=>
    string(5) "three"
    ["t"]=>
    string(3) "str"
  }
}
