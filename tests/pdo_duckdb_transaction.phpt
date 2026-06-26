--TEST--
PDO_duckdb: Test transaction
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');

var_dump($db->inTransaction());
$db->exec("CREATE TABLE txn_test (id INTEGER, val VARCHAR)");
$db->beginTransaction();
var_dump($db->inTransaction());
$insert = $db->prepare("INSERT INTO txn_test VALUES (?, ?)");
$insert->execute([1, 'committed']);
$db->commit();
var_dump($db->inTransaction());
$statement = $db->query("SELECT * FROM txn_test");
echo "After commit:\n";
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));
$db->beginTransaction();
var_dump($db->inTransaction());
$insert->execute([2, 'rolled_back']);
$db->rollback();
var_dump($db->inTransaction());
$statement = $db->query("SELECT * FROM txn_test");
echo "After rollback:\n";
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

?>
--EXPECTF--
bool(false)
bool(true)
bool(false)
After commit:
array(1) {
  [0]=>
  array(2) {
    ["id"]=>
    int(1)
    ["val"]=>
    string(9) "committed"
  }
}
bool(true)
bool(false)
After rollback:
array(1) {
  [0]=>
  array(2) {
    ["id"]=>
    int(1)
    ["val"]=>
    string(9) "committed"
  }
}
