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
