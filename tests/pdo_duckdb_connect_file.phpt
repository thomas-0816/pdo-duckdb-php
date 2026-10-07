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
$statement = $db->prepare("INSERT INTO t VALUES (?, ?)");
$statement->execute([1, 'hello']);
$statement = $db->query("SELECT * FROM t");
while ($row = $statement->fetch()) { var_dump($row); }
foreach ($db->query("SELECT * FROM t") as $row) { var_dump($row); }

try {
    $duckDb = new PDO('duckdb:' . $invalidFile);
} catch (Exception $e) {
    echo "Caught: " . trim($e->getMessage()) . "\n";
}

/* not working with windows
$db = new PDO('duckdb:' . $tmpFile . '2', null, null, [PDO::DUCKDB_ATTR_CONFIG => ['access_mode' => 'read_only', 'memory_limit' => '4GB', 'threads' => 1]]);
$statement = $db->query("SELECT value FROM duckdb_settings() WHERE name IN ('access_mode', 'memory_limit', 'threads')");
var_dump($statement->fetchAll(PDO::FETCH_COLUMN));
*/

try {
  new PDO('duckdb:' . $tmpFile . '3', null, null, [PDO::DUCKDB_ATTR_CONFIG => ['invalid' => 1]]);
} catch (Exception $e) {
    echo "Caught: " . trim($e->getMessage()) . "\n";
}

$tmpFile = tempnam(sys_get_temp_dir(), 'connect') . '.db';

$db = new PDO('duckdb:' . $tmpFile);
$db->exec('CREATE TABLE t1 (i1 integer, v1 varchar)');
$db->exec("INSERT INTO t1 values (1, 'asd')");

$db2 = new PDO('duckdb:' . $tmpFile);
var_dump($db2->query('SELECT count(*) from t1')->fetchColumn(0));
$db2->exec('CREATE TABLE t2 (i1 integer, v1 varchar)');
$db2->exec("INSERT INTO t2 values (1, 'asd')");

$db = new PDO('duckdb:' . $tmpFile);
var_dump($db->query('SELECT count(*) from t2')->fetchColumn(0));

// TODO v2 ATTACH 'my_file.db' AS mmaped_db (IO_MODE 'MMAP');
// TODO v2 ATTACH 'my_file.db' AS mmaped_db (IO_MODE 'DIRECT_IO');

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
Caught: SQLSTATE[HY000]: Could not open DuckDB database: %s
int(1)
int(1)
