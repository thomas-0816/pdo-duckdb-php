--TEST--
PDO_duckdb: Test array
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$statement = $db->query("SELECT null, true, false, NULL::BOOLEAN, DATE '1992-09-20'");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT NULL = NULL");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

?>
--EXPECTF--
array(1) {
  [0]=>
  array(5) {
    ["NULL"]=>
    NULL
    ["CAST('t' AS BOOLEAN)"]=>
    bool(true)
    ["CAST('f' AS BOOLEAN)"]=>
    bool(false)
    ["CAST(NULL AS BOOLEAN)"]=>
    NULL
    ["CAST('1992-09-20' AS "DATE")"]=>
    string(10) "1992-09-20"
  }
}
array(1) {
  [0]=>
  array(1) {
    ["(NULL = NULL)"]=>
    NULL
  }
}
