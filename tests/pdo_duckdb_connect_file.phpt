--TEST--
PDO_duckdb: Test connection with file
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$tmpFile = tempnam(sys_get_temp_dir(), 'connect') . '.db';
$invalidFile = sys_get_temp_dir() . '/invalid/test.db';

$db = new PDO('duckdb:' . $tmpFile);
$db->exec("CREATE TABLE t (i INTEGER, v VARCHAR)");
$stmt = $db->prepare("INSERT INTO t VALUES (?, ?)");
$stmt->execute([1, 'hello']);
$stmt = $db->query("SELECT * FROM t");
while ($row = $stmt->fetch()) { var_dump($row); }
foreach ($db->query("SELECT * FROM t") as $row) { var_dump($row); }

try {
    $duckDb = new PDO('duckdb:' . $invalidFile);
} catch (Exception $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

/* not working with windows
$db = new PDO('duckdb:' . $tmpFile . '2', null, null, [PDO::DUCKDB_ATTR_CONFIG => ['access_mode' => 'read_only', 'memory_limit' => '4GB', 'threads' => 1]]);
$statement = $db->query("SELECT value FROM duckdb_settings() WHERE name IN ('access_mode', 'memory_limit', 'threads')");
var_dump($statement->fetchAll(PDO::FETCH_COLUMN));
*/

try {
  new PDO('duckdb:' . $tmpFile, null, null, [PDO::DUCKDB_ATTR_CONFIG => ['invalid' => 1]]);
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
Caught: SQLSTATE[HY000]: Could not open DuckDB database: %s
Caught: SQLSTATE[HY000]: Could not open DuckDB database: Invalid Input Error: The following options were not recognized: invalid
