--TEST--
PDO_duckdb: Test connection
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (i INTEGER, b BIGINT, d DECIMAL(10, 2), v VARCHAR)");
$statement = $db->prepare("INSERT INTO t VALUES (?, ?, ?, ?)");
$statement->execute([1, 9223372036854775807, 3.141511313212312312, 'hello']);

var_dump($db->lastInsertId());

$statement = $db->query("SELECT * FROM t", PDO::FETCH_ASSOC);
while ($row = $statement->fetch()) { var_dump($row); }

$statement = $db->query("SELECT * FROM t", PDO::FETCH_ASSOC);
foreach ($statement->getIterator() as $row) {
    var_dump($row);
}

$statement = $db->query('SELECT * FROM t');
var_dump($statement->getColumnMeta(0));
var_dump($statement->getColumnMeta(1));
var_dump($statement->getColumnMeta(2));
var_dump($statement->getColumnMeta(3));
var_dump($statement->getColumnMeta(4));

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (n INTEGER NULL, i INTEGER NULL, b BIGINT NULL, d DECIMAL(10, 2) NULL, v VARCHAR NULL)");
$statement = $db->prepare("INSERT INTO t VALUES (?, ?, ?, ?, ?)");
$statement->execute([1, 2, 9223372036854775807, 3.141511313212312312, 'hello']);
$statement = $db->prepare("UPDATE t SET n = ?, i = ?, b = ?, d = ?, v = ?");
$statement->execute([2, 3, 4, 5.67, 'world']);
var_dump($db->query('SELECT * FROM t')->fetchAll(PDO::FETCH_ASSOC));


$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (n INTEGER NULL, i INTEGER NULL, b BIGINT NULL, d DECIMAL(10, 2) NULL, v VARCHAR NULL)");
$statement = $db->prepare('INSERT INTO t VALUES ($2, $1, $3, $5, $4)');
$statement->execute([2, 1, 9223372036854775807, 'hello', 3.141511313212312312]);
var_dump($db->query('SELECT * FROM t')->fetchAll(PDO::FETCH_ASSOC));

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (n INTEGER NULL, i INTEGER NULL, b BIGINT NULL, d DECIMAL(10, 2) NULL, v VARCHAR NULL)");
$statement = $db->prepare('INSERT INTO t VALUES ($aa, $bb, $cc, $dd, $ee)');
$statement->execute(['bb' => 2, 'aa' => 1, 'cc' => 9223372036854775807, 'ee' => 'hello', 'dd' => 3.141511313212312312]);
var_dump($db->query('SELECT * FROM t')->fetchAll(PDO::FETCH_ASSOC));

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (n INTEGER NULL, i INTEGER NULL, b BIGINT NULL, d DECIMAL(10, 2) NULL, v VARCHAR NULL)");
$statement = $db->prepare('INSERT INTO t VALUES ($aa, $bb, $cc, $dd, $ee)');
try {
    $statement->execute(['bb' => 2, 'aa' => 1]);
} catch (Exception $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (n INTEGER NULL, i INTEGER NULL, b BIGINT NULL, d DECIMAL(10, 2) NULL, v VARCHAR NULL)");
$statement = $db->prepare('INSERT INTO t VALUES ($aa, $bb, $cc, $dd, $ee)');
$statement->bindValue('aa', 100, PDO::PARAM_INT);
$statement->bindValue('bb', 200, PDO::PARAM_INT);
$statement->bindValue('cc', 300, PDO::PARAM_INT);
$statement->bindValue('dd', 42.21, PDO::PARAM_STR);
$statement->bindValue('ee', 'bindValue test', PDO::PARAM_STR);
$statement->execute();
var_dump($db->query('SELECT * FROM t')->fetchAll(PDO::FETCH_ASSOC));

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
array(7) {
  ["native_type"]=>
  string(7) "integer"
  ["pdo_type"]=>
  int(1)
  ["duckdb:decl_type"]=>
  string(7) "integer"
  ["flags"]=>
  array(0) {
  }
  ["name"]=>
  string(1) "i"
  ["len"]=>
  int(0)
  ["precision"]=>
  int(0)
}
array(7) {
  ["native_type"]=>
  string(6) "bigint"
  ["pdo_type"]=>
  int(1)
  ["duckdb:decl_type"]=>
  string(6) "bigint"
  ["flags"]=>
  array(0) {
  }
  ["name"]=>
  string(1) "b"
  ["len"]=>
  int(0)
  ["precision"]=>
  int(0)
}
array(7) {
  ["native_type"]=>
  string(7) "decimal"
  ["pdo_type"]=>
  int(2)
  ["duckdb:decl_type"]=>
  string(7) "decimal"
  ["flags"]=>
  array(0) {
  }
  ["name"]=>
  string(1) "d"
  ["len"]=>
  int(0)
  ["precision"]=>
  int(0)
}
array(7) {
  ["native_type"]=>
  string(7) "varchar"
  ["pdo_type"]=>
  int(2)
  ["duckdb:decl_type"]=>
  string(7) "varchar"
  ["flags"]=>
  array(0) {
  }
  ["name"]=>
  string(1) "v"
  ["len"]=>
  int(0)
  ["precision"]=>
  int(0)
}
bool(false)
array(1) {
  [0]=>
  array(5) {
    ["n"]=>
    int(2)
    ["i"]=>
    int(3)
    ["b"]=>
    int(4)
    ["d"]=>
    float(5.67)
    ["v"]=>
    string(5) "world"
  }
}
array(1) {
  [0]=>
  array(5) {
    ["n"]=>
    int(1)
    ["i"]=>
    int(2)
    ["b"]=>
    int(9223372036854775807)
    ["d"]=>
    float(3.14)
    ["v"]=>
    string(5) "hello"
  }
}
array(1) {
  [0]=>
  array(5) {
    ["n"]=>
    int(1)
    ["i"]=>
    int(2)
    ["b"]=>
    int(9223372036854775807)
    ["d"]=>
    float(3.14)
    ["v"]=>
    string(5) "hello"
  }
}
Caught: SQLSTATE[HY000]: Invalid Input Error: Values were not provided for the following prepared statement parameters: cc, dd, ee
array(1) {
  [0]=>
  array(5) {
    ["n"]=>
    int(100)
    ["i"]=>
    int(200)
    ["b"]=>
    int(300)
    ["d"]=>
    float(42.21)
    ["v"]=>
    string(14) "bindValue test"
  }
}
