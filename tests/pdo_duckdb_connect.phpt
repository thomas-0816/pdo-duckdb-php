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

$statement = $db->query("SELECT * FROM t", PDO::FETCH_ASSOC);
while ($row = $statement->fetch()) { var_dump($row); }

$db->exec("INSTAll parquet; INSTAll json; INSTALL icu;");

$statement = $db->query('SELECT extension_name, loaded, installed FROM duckdb_extensions() WHERE installed = 1 OR loaded = 1');
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

var_dump(in_array('duckdb', PDO::getAvailableDrivers()));
var_dump(in_array('pdo_duckdb', get_loaded_extensions()));

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

$db = new PDO('duckdb::memory:');
var_dump($db);

$db = method_exists(PDO::class, 'connect') ? PDO::connect('duckdb::memory:') : new PDO('duckdb::memory:');
var_dump($db);
var_dump($db->query("SELECT 'foo'")->fetch(PDO::FETCH_ASSOC));

$db = new PDO('duckdb::memory:');
$statement = $db->query("SELECT version()");
var_dump($statement->fetch(PDO::FETCH_ASSOC));

var_dump($db->getAttribute(PDO::ATTR_CLIENT_VERSION));
var_dump($db->getAttribute(PDO::ATTR_SERVER_VERSION));
var_dump($db->getAttribute(PDO::ATTR_DRIVER_NAME));

?>
--EXPECTF--
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
  [0]=>
  array(3) {
    ["extension_name"]=>
    string(14) "core_functions"
    ["loaded"]=>
    bool(false)
    ["installed"]=>
    bool(true)
  }
  [1]=>
  array(3) {
    ["extension_name"]=>
    string(3) "icu"
    ["loaded"]=>
    bool(false)
    ["installed"]=>
    bool(true)
  }
  [2]=>
  array(3) {
    ["extension_name"]=>
    string(4) "json"
    ["loaded"]=>
    bool(false)
    ["installed"]=>
    bool(true)
  }
  [3]=>
  array(3) {
    ["extension_name"]=>
    string(7) "parquet"
    ["loaded"]=>
    bool(false)
    ["installed"]=>
    bool(true)
  }
}
bool(true)
bool(true)
Caught: SQLSTATE[HY000]: Permission Error: Cannot access file "http://127.0.0.1/tmp/pdo_duckdb_test_table1.parquet" - file system operations are disabled by configuration
Caught: SQLSTATE[HY000]: Permission Error: Cannot access file "/tmp/pdo_duckdb_test_table1.parquet" - file system operations are disabled by configuration
object(PDO)#4 (0) {
}
object(%s)#5 (0) {
}
array(1) {
  ["'foo'"]=>
  string(3) "foo"
}
array(1) {
  [""version"()"]=>
  string(6) "v1.5.4"
}
string(6) "v1.5.4"
string(6) "v1.5.4"
string(6) "duckdb"
