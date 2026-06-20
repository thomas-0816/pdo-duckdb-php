--TEST--
PDO_duckdb: Test transaction
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');

$db->exec("CREATE TABLE txn_test (id INTEGER, val VARCHAR)");
$db->beginTransaction();
$statement = $db->prepare("INSERT INTO txn_test VALUES (?, ?)");
$statement->execute([1, 'committed']);
$db->commit();
$statement = $db->query("SELECT * FROM txn_test");
echo "After commit:\n";
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));
$db->beginTransaction();
$statement->execute([2, 'rolled_back']);
$db->rollback();
$statement = $db->query("SELECT * FROM txn_test");
echo "After rollback:\n";
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

?>
--EXPECTF--
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
