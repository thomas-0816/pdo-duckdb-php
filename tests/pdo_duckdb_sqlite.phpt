--TEST--
PDO_duckdb: Test sqlite interop
--EXTENSIONS--
pdo_sqlite
--FILE--
<?php

$db = new PDO('sqlite::memory:');
$db->exec('CREATE TABLE t (i INTEGER)');

$statement = $db->prepare('INSERT INTO t VALUES (?)');
$statement->execute([1]);

$statement = $db->prepare('INSERT INTO t VALUES (?)');
$statement->bindValue(1, 2, PDO::PARAM_INT);
$statement->execute();

$statement = $db->prepare('INSERT INTO t VALUES (:i)');
$statement->bindValue('i', 3, PDO::PARAM_INT);
$statement->execute();

$value = 1;
$value2 = 'foo';
$statement = $db->prepare('INSERT INTO t VALUES (?)');
$statement->bindParam(1, $value);
$statement->execute();

$statement = $db->query('SELECT * FROM t', PDO::FETCH_NUM);
while ($row = $statement->fetch()) { var_dump($row); }

$statement = $db->query('SELECT * FROM t');
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

?>
--EXPECTF--
array(1) {
  [0]=>
  int(1)
}
array(1) {
  [0]=>
  int(2)
}
array(1) {
  [0]=>
  int(3)
}
array(1) {
  [0]=>
  int(1)
}
array(4) {
  [0]=>
  array(1) {
    ["i"]=>
    int(1)
  }
  [1]=>
  array(1) {
    ["i"]=>
    int(2)
  }
  [2]=>
  array(1) {
    ["i"]=>
    int(3)
  }
  [3]=>
  array(1) {
    ["i"]=>
    int(1)
  }
}
