--TEST--
PDO_duckdb: Test uuid
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$statement = $db->query("SELECT uuidv4(), uuidv7()");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

?>
--EXPECTF--
array(1) {
  [0]=>
  array(2) {
    ["uuidv4()"]=>
    string(36) "%s"
    ["uuidv7()"]=>
    string(36) "%s"
  }
}
