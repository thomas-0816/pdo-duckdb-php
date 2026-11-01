--TEST--
PDO_duckdb: Test create type
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$db->exec('CREATE TYPE Person AS STRUCT(id INTEGER, name VARCHAR)');
$db->exec('CREATE TYPE Tags AS VARCHAR[]');
$db->exec('CREATE TABLE t1 (employee Person, categories Tags)');
$statement = $db->prepare('INSERT INTO t1 VALUES (?, ?)');
$statement->execute([['id' => 1, 'name' => 'Foo'], ['foo', 'bar']]);

$statement = $db->query('SELECT * FROM t1');
var_dump($statement->fetch(PDO::FETCH_ASSOC));
var_dump($statement->getColumnMeta(0));
var_dump($statement->getColumnMeta(1));

?>
--EXPECTF--
array(2) {
  ["employee"]=>
  array(2) {
    ["id"]=>
    int(1)
    ["name"]=>
    string(3) "Foo"
  }
  ["categories"]=>
  array(2) {
    [0]=>
    string(3) "foo"
    [1]=>
    string(3) "bar"
  }
}
array(7) {
  ["native_type"]=>
  string(6) "struct"
  ["pdo_type"]=>
  int(3)
  ["duckdb:decl_type"]=>
  string(34) "STRUCT(id INTEGER, "name" VARCHAR)"
  ["flags"]=>
  array(0) {
  }
  ["name"]=>
  string(8) "employee"
  ["len"]=>
  int(0)
  ["precision"]=>
  int(0)
}
array(7) {
  ["native_type"]=>
  string(4) "list"
  ["pdo_type"]=>
  int(3)
  ["duckdb:decl_type"]=>
  string(9) "VARCHAR[]"
  ["flags"]=>
  array(0) {
  }
  ["name"]=>
  string(10) "categories"
  ["len"]=>
  int(0)
  ["precision"]=>
  int(0)
}
