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

$db = new PDO('duckdb::memory:');
$db->exec('CREATE TABLE t1 (id INTEGER)');
var_dump($db->inTransaction());

$db->exec('BEGIN');
var_dump($db->inTransaction());
$db->exec('INSERT INTO t1 VALUES (1)');
$db->exec('COMMIT');
var_dump($db->inTransaction());

$db->exec('BEGIN');
var_dump($db->inTransaction());
$db->exec('INSERT INTO t1 VALUES (1)');
$db->rollBack();
var_dump($db->inTransaction());

$db->exec('BEGIN');
var_dump($db->inTransaction());
$db->exec('INSERT INTO t1 VALUES (1)');
$db->commit();
var_dump($db->inTransaction());

$db->beginTransaction();
var_dump($db->inTransaction());
$db->exec('INSERT INTO t1 VALUES (1)');
$db->exec('COMMIT');
var_dump($db->inTransaction());

$db->beginTransaction();
var_dump($db->inTransaction());
$db->exec('INSERT INTO t1 VALUES (1)');
$db->exec('ROLLBACK');
var_dump($db->inTransaction());

$db->beginTransaction();
var_dump($db->inTransaction());
$db->exec('INSERT INTO t1 VALUES (1)');
try {
    $db->exec('SELECT INVALID');
}
catch (Exception $e) {
    echo 'Caught: ' . $e->getMessage(), PHP_EOL;
}
var_dump($db->inTransaction());
$db->exec('COMMIT');
var_dump($db->inTransaction());

var_dump($db->query('SELECT count(*) FROM t1')->fetchColumn());

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
bool(false)
bool(true)
bool(false)
bool(true)
bool(false)
bool(true)
bool(false)
bool(true)
bool(false)
bool(true)
bool(false)
bool(true)
Caught: SQLSTATE[HY000]: Binder Error: Referenced column "INVALID" was not found because the FROM clause is missing

LINE 1: SELECT INVALID
               ^
bool(true)
bool(false)
int(4)
