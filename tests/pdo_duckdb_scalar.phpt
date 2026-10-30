--TEST--
PDO_duckdb: Test scalar
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$statement = $db->query("SELECT null, true, false, NULL::BOOLEAN, DATE '1992-09-20'");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT NULL = NULL");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT make_type('INTEGER'), get_type(42), NULL::TYPE");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));
var_dump($statement->getColumnMeta(0));

?>
--EXPECTF--
array(1) {
  [0]=>
  array(5) {
    ["NULL"]=>
    NULL
    ["true"]=>
    bool(true)
    ["false"]=>
    bool(false)
    ["CAST(NULL AS BOOLEAN)"]=>
    NULL
    ["CAST('1992-09-20' AS DATE)"]=>
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
array(1) {
  [0]=>
  array(3) {
    ["make_type('INTEGER')"]=>
    string(7) "INTEGER"
    ["get_type(42)"]=>
    string(7) "INTEGER"
    ["CAST(NULL AS TYPE)"]=>
    NULL
  }
}
array(7) {
  ["native_type"]=>
  string(4) "type"
  ["pdo_type"]=>
  int(2)
  ["duckdb:decl_type"]=>
  string(4) "TYPE"
  ["flags"]=>
  array(0) {
  }
  ["name"]=>
  string(20) "make_type('INTEGER')"
  ["len"]=>
  int(0)
  ["precision"]=>
  int(0)
}
