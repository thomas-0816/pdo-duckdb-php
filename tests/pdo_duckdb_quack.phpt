--TEST--
PDO_duckdb: Test quack
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$server = new PDO('duckdb::memory:');
$server->exec("CALL quack_serve('quack:127.0.0.1:9494', token='secret')");
$client1 = new PDO('duckdb::memory:', null, null, [
    PDO::DUCKDB_ATTR_INIT_COMMAND => "ATTACH 'quack:127.0.0.1:9494' AS remote (TOKEN 'secret'); USE remote;"
]);
$client1->exec('CREATE TABLE IF NOT EXISTS table1 (v VARCHAR, v2 VARCHAR)');
$client1->exec("INSERT INTO table1 VALUES ('foo', 'bar')");
var_dump($client1->query('SELECT * FROM table1')->fetchAll(PDO::FETCH_ASSOC));
$client2 = new PDO('duckdb::memory:', null, null, [
    PDO::DUCKDB_ATTR_INIT_COMMAND => "ATTACH 'quack:127.0.0.1:9494' AS remote (TOKEN 'secret'); USE remote;"
]);
var_dump($client2->query('SELECT * FROM table1')->fetchAll(PDO::FETCH_ASSOC));
$server->exec("CALL quack_stop('quack:127.0.0.1:9494')");

$server = new PDO('duckdb::memory:');
$server->exec("CALL quack_serve('quack:127.0.0.1:9495', token='secret')");
$client1 = new PDO('duckdb::memory:', null, null, [
    PDO::DUCKDB_ATTR_INIT_COMMAND => "ATTACH 'quack:127.0.0.1:9495' AS remote (TOKEN 'secret'); CONNECT remote;"
]);
$client1->exec('CREATE TABLE IF NOT EXISTS table1 (v VARCHAR, v2 VARCHAR)');
$client1->exec("INSERT INTO table1 VALUES ('foo', 'bar')");
var_dump($client1->query('SELECT * FROM table1')->fetchAll(PDO::FETCH_ASSOC));
    $statement = $client1->prepare("INSERT INTO table1 VALUES (?, ?)");
try {
    $statement->execute(['foo', 'bar']);
} catch (PDOException $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}
$client2 = new PDO('duckdb::memory:', null, null, [
    PDO::DUCKDB_ATTR_INIT_COMMAND => "ATTACH 'quack:127.0.0.1:9495' AS remote (TOKEN 'secret'); CONNECT remote;"
]);
var_dump($client2->query('SELECT * FROM table1')->fetchAll(PDO::FETCH_ASSOC));
$server->exec("CALL quack_stop('quack:127.0.0.1:9495')");

?>
--EXPECTF--
array(1) {
  [0]=>
  array(2) {
    ["v"]=>
    string(3) "foo"
    ["v2"]=>
    string(3) "bar"
  }
}
array(1) {
  [0]=>
  array(2) {
    ["v"]=>
    string(3) "foo"
    ["v2"]=>
    string(3) "bar"
  }
}
array(1) {
  [0]=>
  array(2) {
    ["v"]=>
    string(3) "foo"
    ["v2"]=>
    string(3) "bar"
  }
}
Caught: SQLSTATE[HY000]: parameter binding failed for parameter 1
array(1) {
  [0]=>
  array(2) {
    ["v"]=>
    string(3) "foo"
    ["v2"]=>
    string(3) "bar"
  }
}
