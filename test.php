<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (i INTEGER, b BIGINT, d DECIMAL(10, 2), v VARCHAR)");
$stmt = $db->prepare("INSERT INTO t VALUES (?, ?, ?, ?)");
$stmt->execute([1, 9223372036854775807, 3.141511313212312312, 'hello']);
$stmt = $db->query("SELECT * FROM t", PDO::FETCH_ASSOC);
while ($row = $stmt->fetch()) { print_r($row); }

if (file_exists('/tmp/pdo_duckdb_test.db')) {
    unlink('/tmp/pdo_duckdb_test.db');
}

$db = new PDO('duckdb:/tmp/pdo_duckdb_test.db');
$db->exec("CREATE TABLE t (i INTEGER, v VARCHAR)");
$stmt = $db->prepare("INSERT INTO t VALUES (?, ?)");
$stmt->execute([1, 'hello']);
$stmt = $db->query("SELECT * FROM t");
while ($row = $stmt->fetch()) { print_r($row); }

$db = new PDO('duckdb::memory:');
$statement = $db->query("SELECT {'birds': ['duck', 'goose', 'heron'], 'aliens': NULL, 'amphibians': ['frog', 'toad']} as struct");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {'test': [MAP([1, 5], [42.1, 45]), MAP([1, 5], [42.1, 45])]} as struct");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT [union_value(num := 2), union_value(str := 'ABC')::UNION(str VARCHAR, num INTEGER)] as union");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT array_value(1, 2, 3) as aa");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT array_value(1, 2, 3)[2] as aa");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT [3, 2, 1]::INTEGER[3] as aa");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT array_value(array_value(1, 2), array_value(3, 4), array_value(5, 6)) as aa");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT array_value({'a': 1, 'b': 2}, {'a': 3, 'b': 4}) as aa");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT null, true, false, NULL::BOOLEAN, DATE '1992-09-20'");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT uuidv4(), uuidv7()");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT '-infinity'::DATE AS negative, 'epoch'::DATE AS epoch, 'infinity'::DATE AS positive"); // TODO fix?
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 0/0, 0//0"); // TODO fix?
var_export($statement->fetchAll(PDO::FETCH_ASSOC));

$db->exec("CREATE TABLE array_table (id INTEGER, arr INTEGER[3])");
$db->exec("INSERT INTO array_table VALUES (10, [1, 2, 3]), (20, [4, 5, 6])");
$statement = $db->query("SELECT id, arr[1] AS element FROM array_table");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));
$statement = $db->query("SELECT id, list_extract(arr, 1) AS element FROM array_table");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));
$statement = $db->query("SELECT id, array_extract(arr, 1) AS element FROM array_table");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));
$statement = $db->query("SELECT id, arr[1:2] AS elements FROM array_table");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$db->exec("CREATE TABLE x (i INTEGER, v FLOAT[3])");
$db->exec("CREATE TABLE y (i INTEGER, v FLOAT[3])");
$db->exec("INSERT INTO x VALUES (1, array_value(1.0::FLOAT, 2.0::FLOAT, 3.0::FLOAT))");
$db->exec("INSERT INTO y VALUES (1, array_value(2.1::FLOAT, 3.2::FLOAT, 4.3::FLOAT))");

$statement = $db->query("SELECT * FROM x");
var_export($statement->fetchAll(PDO::FETCH_ASSOC));
$statement = $db->query("SELECT * FROM y");
var_export($statement->fetchAll(PDO::FETCH_ASSOC));

try {
    $statement = $db->query("SELECT CAST(999 AS TINYINT)");
    var_export($statement);
} catch (Exception $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

$statement = $db->query("SELECT CAST(42.5 AS VARCHAR), CAST(3.1 AS INTEGER)");
var_export($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT CAST(0.9 AS FLOAT), CAST(3.1 AS FLOAT), CAST([1, 2, 3] AS VARCHAR[])");
var_export($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT CAST({'a': 42} AS STRUCT(a VARCHAR)), CAST({'a': 42} AS STRUCT(a VARCHAR, b VARCHAR))");
var_export($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT CAST({'a': 42, 'b': 43} AS STRUCT(a VARCHAR)), CAST({'a': 42, 'b': 84} AS STRUCT(b VARCHAR, a VARCHAR))");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT [{'a': 42}, {'b': 84}]");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {'outer1': {'inner1': 42, 'inner2': 42}} AS c
    UNION SELECT {'outer1': {'inner2': 'hello', 'inner3': 'world'}, 'outer2': '100'} AS c;");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$db->exec("CREATE TABLE txn_test (id INTEGER, val VARCHAR)");
$db->beginTransaction();
$statement = $db->prepare("INSERT INTO txn_test VALUES (?, ?)");
$statement->execute([1, 'committed']);
$db->commit();
$statement = $db->query("SELECT * FROM txn_test");
echo "After commit:\n";
print_r($statement->fetchAll(PDO::FETCH_ASSOC));
$db->beginTransaction();
$statement->execute([2, 'rolled_back']);
$db->rollback();
$statement = $db->query("SELECT * FROM txn_test");
echo "After rollback:\n";
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$db->exec("CREATE TABLE unicode_test (id INTEGER, latin VARCHAR, mb4 VARCHAR)");
$statement = $db->prepare("INSERT INTO unicode_test VALUES (?, ?, ?)");
$statement->execute([1, 'äöüßÄÖÜ', 'emoji 🐘 test']);
$statement = $db->query("SELECT * FROM unicode_test");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 'Straße' AS german, 'München' AS city, 'café' AS french, '🐘🐋🦀' AS emoji");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->prepare("INSERT INTO unicode_test VALUES (?, ?, ?)");
$statement->execute([2, 'café résumé', 'four-byte: 🐘🐋🦀🌍']);
$statement = $db->query("SELECT * FROM unicode_test ORDER BY id");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("select 0.9::float, pi(), 9223372036854775808, -9223372036854775809");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("select '2969-01-01'::date, '0001-01-01'::date");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("select '2969-01-01'::datetime, '0001-01-01'::datetime");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$db->exec("CREATE TABLE tbl1 (u UNION(num INTEGER, str VARCHAR))");
$db->exec("INSERT INTO tbl1 VALUES (1), ('two'), (union_value(str := 'three'))");
$statement = $db->query("SELECT u, u.str, union_extract(u, 'str') AS str2, union_tag(u) AS t FROM tbl1");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$db->exec("CREATE TYPE mood AS ENUM ('sad', 'ok', 'happy')");
$statement = $db->query("SELECT 'sad'::mood AS m1, 'happy'::mood AS m2, NULL::mood AS mn");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));
$db->exec("CREATE TABLE enum_table (id INTEGER, m mood)");
$db->exec("INSERT INTO enum_table VALUES (1, 'ok'), (2, 'happy'), (3, 'sad')");
$statement = $db->query("SELECT * FROM enum_table ORDER BY id");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIMESTAMP '1992-09-20 11:30:00.123456789', TIMESTAMP '1992-09-20 11:30:00'");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIMESTAMP_NS '1992-09-20 11:30:00.123456789'");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIMESTAMP_MS '1992-09-20 11:30:00.123456789'");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIMESTAMP_S '1992-09-20 11:30:00.123456789'");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIMESTAMPTZ '1992-09-20 11:30:00.123456789'"); // TODO fix +00
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIMESTAMPTZ '1992-09-20 12:30:00.123456789+01:00'"); // TODO fix +00
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT timezone('America/Denver', TIMESTAMP '2001-02-16 20:38:40')"); // TODO fix +00
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

// $statement = $db->query("SELECT '-infinity'::TIMESTAMP, 'epoch'::TIMESTAMP, 'infinity'::TIMESTAMP");
//print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIME '1992-09-20 11:30:00.123456', TIME '1992-09-20 11:30:00'"); // TODO missing ms
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIMETZ '1992-09-20 11:30:00.123456'"); // TODO missing ms+tz
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIMETZ '1992-09-20 11:30:00.123456-02:00'");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIMETZ '1992-09-20 11:30:00.123456+05:30'");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT '15:30:00.123456789'::TIME_NS");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));
