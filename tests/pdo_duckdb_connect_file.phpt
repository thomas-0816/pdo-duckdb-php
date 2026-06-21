--TEST--
PDO_duckdb: Test connection with file
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

if (file_exists('/tmp/pdo_duckdb_test.db')) {
    unlink('/tmp/pdo_duckdb_test.db');
}

$db = new PDO('duckdb:/tmp/pdo_duckdb_test.db');
$db->exec("CREATE TABLE t (i INTEGER, v VARCHAR)");
$stmt = $db->prepare("INSERT INTO t VALUES (?, ?)");
$stmt->execute([1, 'hello']);
$stmt = $db->query("SELECT * FROM t");
while ($row = $stmt->fetch()) { var_dump($row); }
foreach ($db->query("SELECT * FROM t") as $row) { var_dump($row); }
unset($db);

try {
    $duckDb = new PDO('duckdb:/tmp/invalid/test.db');
} catch (Exception $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

$db = new PDO('duckdb::memory:');
try {
    $db->exec("
        LOAD parquet;
        SET enable_external_access = false;
        select * from 'http://127.0.0.1/tmp/pdo_duckdb_test_table1.parquet';
    ");
} catch (Exception $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

$db = new PDO('duckdb::memory:');
try {
    $db->exec("
        LOAD parquet;
        SET enable_external_access = false;
        select * from '/tmp/pdo_duckdb_test_table1.parquet';
    ");
} catch (Exception $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

?>
--EXPECTF--
array(4) {
  ["i"]=>
  int(1)
  [0]=>
  int(1)
  ["v"]=>
  string(5) "hello"
  [1]=>
  string(5) "hello"
}
array(4) {
  ["i"]=>
  int(1)
  [0]=>
  int(1)
  ["v"]=>
  string(5) "hello"
  [1]=>
  string(5) "hello"
}
Caught: Could not open DuckDB database:
Caught: SQLSTATE[HY000]: Permission Error: Cannot access file "http://127.0.0.1/tmp/pdo_duckdb_test_table1.parquet" - file system operations are disabled by configuration
Caught: SQLSTATE[HY000]: Permission Error: Cannot access file "/tmp/pdo_duckdb_test_table1.parquet" - file system operations are disabled by configuration
