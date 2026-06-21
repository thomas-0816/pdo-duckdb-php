--TEST--
PDO_duckdb: Test connection
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (i INTEGER, b BIGINT, d DECIMAL(10, 2), v VARCHAR)");
$stmt = $db->prepare("INSERT INTO t VALUES (?, ?, ?, ?)");
$stmt->execute([1, 9223372036854775807, 3.141511313212312312, 'hello']);
var_dump($db->lastInsertId());
$stmt = $db->query("SELECT * FROM t", PDO::FETCH_ASSOC);
while ($row = $stmt->fetch()) { var_dump($row); }

$statement = $db->query('SELECT * FROM t');
var_dump($statement->getColumnMeta(0));
var_dump($statement->getColumnMeta(1));
var_dump($statement->getColumnMeta(2));
var_dump($statement->getColumnMeta(3));
var_dump($statement->getColumnMeta(4));

try {
    $duckDb = new PDO('duckdb:/tmp/invalid/test.db');
} catch (Exception $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

var_dump(in_array('duckdb', PDO::getAvailableDrivers()));

?>
--EXPECTF--
string(1) "0"
array(4) {
  ["i"]=>
  int(1)
  ["b"]=>
  int(9223372036854775807)
  ["d"]=>
  float(3.14)
  ["v"]=>
  string(5) "hello"
}
Caught: Could not open DuckDB database
bool(true)
