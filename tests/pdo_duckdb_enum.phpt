--TEST--
PDO_duckdb: Test enum
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TYPE mood AS ENUM ('sad', 'ok', 'happy')");
$statement = $db->query("SELECT 'sad'::mood AS m1, 'happy'::mood AS m2, NULL::mood AS mn");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));
$db->exec("CREATE TABLE enum_table (id INTEGER, m mood)");
$db->exec("INSERT INTO enum_table VALUES (1, 'ok'), (2, 'happy'), (3, 'sad')");
$statement = $db->query("SELECT * FROM enum_table ORDER BY id");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 'clubs'::ENUM ('spades', 'hearts', 'diamonds', 'clubs')");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$db->exec("CREATE TYPE priority AS ENUM ('low', 'medium', 'high')");
$statement = $db->query("SELECT unnest(['medium'::priority, 'high'::priority, 'low'::priority]) AS m ORDER BY m");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

?>
--EXPECTF--
array(1) {
  [0]=>
  array(3) {
    ["m1"]=>
    string(3) "sad"
    ["m2"]=>
    string(5) "happy"
    ["mn"]=>
    NULL
  }
}
array(3) {
  [0]=>
  array(2) {
    ["id"]=>
    int(1)
    ["m"]=>
    string(2) "ok"
  }
  [1]=>
  array(2) {
    ["id"]=>
    int(2)
    ["m"]=>
    string(5) "happy"
  }
  [2]=>
  array(2) {
    ["id"]=>
    int(3)
    ["m"]=>
    string(3) "sad"
  }
}
array(1) {
  [0]=>
  array(1) {
    ["CAST('clubs' AS ENUM('spades', 'hearts', 'diamonds', 'clubs'))"]=>
    string(5) "clubs"
  }
}
array(3) {
  [0]=>
  array(1) {
    ["m"]=>
    string(3) "low"
  }
  [1]=>
  array(1) {
    ["m"]=>
    string(6) "medium"
  }
  [2]=>
  array(1) {
    ["m"]=>
    string(4) "high"
  }
}
