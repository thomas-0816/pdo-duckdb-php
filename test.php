<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$duckDb = new PDO('duckdb::memory:');
$duckDb->exec("CREATE TABLE table1 (id INTEGER, amount DECIMAL(10, 2), description VARCHAR USING COMPRESSION zstd)");

$statement = $duckDb->prepare("INSERT INTO table1 VALUES (?, ?, ?)");
$statement->execute([1, 42.21, 'Hello DuckDB! 🐘 💓 🦆']);

$statement = $duckDb->query("SELECT * FROM table1");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

if (file_exists('/tmp/pdo_duckdb_test.db')) {
    unlink('/tmp/pdo_duckdb_test.db');
}

$duckDb = new PDO('duckdb:/tmp/pdo_duckdb_test.db');
$duckDb->exec("CREATE TABLE table2 (id INTEGER, text VARCHAR, data JSON)");

$statement = $duckDb->prepare("INSERT INTO table2 VALUES (?, ?, ?)");
$statement->execute([1, 'Hello DuckDB 🦆', json_encode(['foo' => 'bar', 'baz' => 42])]);

$statement = $duckDb->exec("
    COPY (SELECT * FROM table2)
    TO '/tmp/pdo_duckdb_test_table1.parquet'
    (FORMAT parquet, COMPRESSION zstd, ROW_GROUP_SIZE 100_000)
");

foreach ($duckDb->query("SELECT * FROM '/tmp/pdo_duckdb_test_table1.parquet'", PDO::FETCH_ASSOC) as $row) {
    print_r($row);
}

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (i INTEGER, b BIGINT, d DECIMAL(10, 2), v VARCHAR)");
$stmt = $db->prepare("INSERT INTO t VALUES (?, ?, ?, ?)");
$stmt->execute([1, 9223372036854775807, 3.141511313212312312, 'hello']);
$stmt = $db->query("SELECT * FROM t", PDO::FETCH_ASSOC);
while ($row = $stmt->fetch()) { print_r($row); }

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (i INTEGER, ui UINTEGER, b BIGINT, b2 BIGINT, ub UBIGINT, h HUGEINT, u UHUGEINT)");
$stmt = $db->prepare("INSERT INTO t VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([1, 2, 9_223_372_036_854_775_806, -9_223_372_036_854_775_806, '18446744073709551614', '170141183460469231731687303715884105726', '340282366920938463463374607431768211455']);
$stmt = $db->query("SELECT * FROM t", PDO::FETCH_ASSOC);
while ($row = $stmt->fetch()) { print_r($row); }
exit;

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
foreach ($db->query("SELECT * FROM t") as $row) { print_r($row); }
unset($db);

file_put_contents('/tmp/test_logs.json', json_encode(['date' => '2026-01-02 03:04:05', 'log' => 'log text']) . PHP_EOL);
file_put_contents('/tmp/test_logs.json', json_encode(['date' => '2026-02-03 04:05:06', 'log' => 'log text 2']) . PHP_EOL, FILE_APPEND);

$db = new PDO('duckdb::memory:');
$statement = $db->query("SELECT * FROM '/tmp/test_logs.json'");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

if (file_exists('/tmp/test.csv')) {
    unlink('/tmp/test.csv');
}

$list = [
    ['aaa', 'bbb', 'ccc'],
    ['123', '456', '789'],
    ['aaa', 'bbb', 'ccc']
];
$fp = fopen('/tmp/test.csv', 'w');
foreach ($list as $fields) {
    fputcsv($fp, $fields, ',', '"', "");
}
fclose($fp);

$db = new PDO('duckdb::memory:');
$statement = $db->query("SELECT * FROM '/tmp/test.csv'");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

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

$statement = $db->query("SELECT '-infinity'::DATE AS negative, 'epoch'::DATE AS epoch, 'infinity'::DATE AS positive");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 0/0, 0//0"); // -nan, null
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 1/0, -1/0, 1//0, -1//0"); // inf, -inf, null, null
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

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

$statement = $db->query("SELECT TIMESTAMP_NS '1992-09-20 11:30:00.123456789', TIMESTAMP_MS '1992-09-20 11:30:00.123456789', TIMESTAMP_S '1992-09-20 11:30:00.123456789'");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIMESTAMPTZ '1992-09-20 11:30:00.123456789', TIMESTAMPTZ '1992-09-20 12:30:00.123456789+01:00', timezone('America/Denver', TIMESTAMP '2001-02-16 20:38:40')");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT '-infinity'::TIMESTAMP, 'epoch'::TIMESTAMP, 'infinity'::TIMESTAMP");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIME '1992-09-20 11:30:00.123456', TIME '1992-09-20 11:30:00'");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIMETZ '1992-09-20 11:30:00.123456'");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIMETZ '1992-09-20 11:30:00.123456-02:00'");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIMETZ '1992-09-20 11:30:00.123456+05:30'");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT '15:30:00.123456789'::TIME_NS");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 'Hello' || chr(10) || 'world' AS msg");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT struct_pack(key1 := 'value1', key2 := 42) AS s");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {'key1': 'value1', 'key2': 42} AS s");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT d AS s FROM (SELECT 'value1' AS key1, 42 AS key2) d");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {'x': 1, 'y': 2, 'z': 3} AS s");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {'yes': 'duck', 'maybe': 'goose', 'huh': NULL, 'no': 'heron'} AS s");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {'key1': 'string', 'key2': 1, 'key3': 12.345} AS s");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {
    'birds': {'yes': 'duck', 'maybe': 'goose', 'huh': NULL, 'no': 'heron'}, 'aliens': NULL,
    'amphibians': {'yes': 'frog', 'maybe': 'salamander', 'huh': 'dragon', 'no': 'toad'}
} AS s;
");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT struct_update({'a': 1, 'b': 2}, b := 3, c := 4) AS s");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT a.x FROM (SELECT {'x': 1, 'y': 2, 'z': 3} AS a)");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT a.\"x space\" FROM (SELECT {'x space': 1, 'y': 2, 'z': 3} AS a)");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT a['x space'] FROM (SELECT {'x space': 1, 'y': 2, 'z': 3} AS a)");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT struct_extract({'x space': 1, 'y': 2, 'z': 3}, 'x space')");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT unnest(a) FROM (SELECT {'x': 1, 'y': 2, 'z': 3} AS a)");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT a.* EXCLUDE ('y') FROM (SELECT {'x': 1, 'y': 2, 'z': 3} AS a)");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT struct_pack(y := a.x) AS b FROM (SELECT {'x': 42} AS a)");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT row(x, x + 1, y) as a FROM (SELECT 1 AS x, 'a' AS y) AS s");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT (x, x + 1, y) AS s FROM (SELECT 1 AS x, 'a' AS y)");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {'k1': 1, 'k2': 0} < {'k1': 0, 'k2': 1}");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {'k1': 1, 'k2': 0} > {'k3': 0, 'k1': 0}");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query('SELECT \'[1, null, {"key": "value"}]\'::JSON');
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

try {
    $statement = $db->query("SELECT 'unquoted'::JSON");
    var_dump($statement->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

$statement = $db->query('SELECT \'{  "a":5 }\'::JSON::VARCHAR');
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query('SELECT \'{"a":1,"a":2}\'::JSON');
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query('SELECT \'{"duck": 42}\'::JSON::STRUCT(duck INTEGER)');
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query('SELECT {duck: 42}::JSON');
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query('SELECT \'2023-05-12\'::DATE::JSON');
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT NULL = NULL");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT MAP {'key1': 10, 'key2': 20, 'key3': 30}");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT map_from_entries([('key1', 10), ('key2', 20), ('key3', 30)])");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT MAP(['key1', 'key2', 'key3'], [10, 20, 30])");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT MAP {1: 42.001, 5: -32.1}");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT MAP {['a', 'b', 'c']: [1.1, 2.2, null], ['c', 'd', null]: [3.3, 4.4, 5.5]}");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT MAP {'key1': 5, 'key2': 43}['key1']");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT MAP {'key1': 5, 'key2': 43}['key3']");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT map_extract(MAP {'key1': 5, 'key2': 43}, 'key1')");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 1.5, .50, 2.");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 1e2, 6.02214e23, 1e-10");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 100_000_000, '0xFF_FF'::INTEGER, 1_2.1_2E0_1, '0b0_1_0_1'::INTEGER");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 'Hello'
    ' '
    'World' AS greeting");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 'Hello' || ' ' || 'World' AS greeting");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

try {
    $statement = $db->query("SELECT 'Hello' ' ' 'World' AS greeting");
    var_dump($statement->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

$statement = $db->query('SELECT e\'Hello\nworld\' AS msg');
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query('SELECT $$Hello
world$$ AS msg');
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query('SELECT $$The price is $9.95$$ AS msg');
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query('SELECT $tag$ this string can contain newlines,
\'single quotes\',
"double quotes",
and $$dollar quotes$$ $tag$ AS msg');
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT [1, 2, 3]");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT ['duck', 'goose', NULL, 'heron']");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT [['duck', 'goose', 'heron'], NULL, ['frog', 'toad'], []]");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT list_value(1, 2, 3)");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT ['a', 'b', 'c'][1:2]");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT list_slice(['a', 'b', 'c'], 2, 3)");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT INTERVAL 1 YEAR, INTERVAL (4 * 10) YEAR, INTERVAL '1 month 1 day', '16 months'::INTERVAL, '48:00:00'::INTERVAL");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT INTERVAL '1.5' YEARS");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT DATE '2000-01-01' + INTERVAL (i) MONTH as i FROM range(2) t(i)");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT datepart('decade', INTERVAL 12 YEARS), datepart('year', INTERVAL 12 YEARS), datepart('second', INTERVAL 1_234 MILLISECONDS), datepart('microsecond', INTERVAL 1_234 MILLISECONDS)");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT DATE '2000-01-01' + INTERVAL 1 YEAR,
    TIMESTAMP '2000-01-01 01:33:30' - INTERVAL '1 month 13 hours',
    TIME '02:00:00' - INTERVAL '3 days 23 hours'");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT
    TIMESTAMP '2000-02-06 12:00:00' - TIMESTAMP '2000-01-01 11:00:00',
    TIMESTAMP '2000-02-01' + (TIMESTAMP '2000-02-01' - TIMESTAMP '2000-01-01'),
");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 'clubs'::ENUM ('spades', 'hearts', 'diamonds', 'clubs')");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$db->exec("CREATE TYPE priority AS ENUM ('low', 'medium', 'high')");
$statement = $db->query("SELECT unnest(['medium'::priority, 'high'::priority, 'low'::priority]) AS m ORDER BY m");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query('SELECT \'\xAA\'::BLOB as a');
echo bin2hex($statement->fetchAll(PDO::FETCH_ASSOC)[0]['a']), PHP_EOL;

$statement = $db->query('SELECT \'\xAA\xAB\xAC\'::BLOB as a');
echo bin2hex($statement->fetchAll(PDO::FETCH_ASSOC)[0]['a']), PHP_EOL;

$statement = $db->query("SELECT '101010'::BIT AS b, '1111'::BIT AS b2, NULL::BIT AS b3, '0'::BIT AS b5, '1'::BIT AS b6");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT BIT_COUNT('101010'::BIT) AS bitcount");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT ('10101010'::BIT || '11110000'::BIT) AS concat");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$db->exec("CREATE TABLE bit_table (id INTEGER, b BIT)");
$stmt = $db->prepare("INSERT INTO bit_table VALUES (?, CAST(? AS BIT))");
$stmt->bindValue(1, 1, PDO::PARAM_INT);
$stmt->bindValue(2, '101010', PDO::PARAM_STR);
$stmt->execute();
$stmt = $db->prepare("INSERT INTO bit_table VALUES (?, CAST(? AS BIT))");
$stmt->bindValue(1, 2, PDO::PARAM_INT);
$stmt->bindValue(2, '110011', PDO::PARAM_STR);
$stmt->execute();
$statement = $db->query("SELECT * FROM bit_table ORDER BY id");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$db->exec("CREATE TABLE geometries (id INTEGER, geom GEOMETRY)");
$db->exec("INSERT INTO geometries VALUES
  (1, 'POINT (30 10)'),
  (2, 'LINESTRING (30 10, 10 30, 40 40)'),
  (3, 'POLYGON ((30 10, 40 40, 20 40, 10 20, 30 10))'),
  (4, 'MULTIPOINT ((10 40), (40 30), (20 20), (30 10))'),
  (5, 'MULTILINESTRING ((10 10, 20 20, 10 40), (40 40, 30 30, 40 20))'),
  (6, 'MULTIPOLYGON (((30 20, 45 40, 10 40, 30 20)), ((15 5, 40 10, 10 20, 5 10,15 5)))'),
  (7, 'GEOMETRYCOLLECTION (POINT(40 10), LINESTRING(10 10,20 20,10 40), POLYGON((40 40,20 45,45 30,40 40)))')");
$statement = $db->query("SELECT geom FROM geometries");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

file_put_contents('/tmp/test_logs.json', json_encode(['date' => '2026-01-02 03:04:05', 'log' => 'log text']) . PHP_EOL);
file_put_contents('/tmp/test_logs.json', json_encode(['date' => '2026-02-03 04:05:06', 'log' => 'log text 2']) . PHP_EOL, FILE_APPEND);

// limit threads and memory usage for converting big files, see https://github.com/duckdb/duckdb/issues/16078
// 100k rows per group
$db->exec("
    set memory_limit='4GB';
    set threads = 1;
    SET preserve_insertion_order=false;
    copy (select * from read_ndjson('/tmp/test_logs.json', ignore_errors=true))
    to '/tmp/test_logs.parquet' (FORMAT 'parquet', COMPRESSION 'zstd', ROW_GROUP_SIZE 100_000)
");
// 100M bytes per group
$db->exec("
    set memory_limit='4GB';
    set threads = 1;
    SET preserve_insertion_order=false;
    copy (select * from read_ndjson('/tmp/test_logs.json', ignore_errors=true))
    to '/tmp/test_logs2.parquet' (FORMAT 'parquet', COMPRESSION 'zstd', ROW_GROUP_SIZE_BYTES 100_000_000)
");

$statement = $db->query("SELECT * FROM '/tmp/test_logs.parquet'");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));
$statement = $db->query("SELECT * FROM '/tmp/test_logs2.parquet'");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT * FROM parquet_schema('/tmp/test_logs2.parquet')");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));
$statement = $db->query("SELECT * FROM parquet_metadata('/tmp/test_logs2.parquet')");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));
$statement = $db->query("SELECT * FROM parquet_file_metadata('/tmp/test_logs2.parquet')");
print_r($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT value FROM duckdb_settings() WHERE name IN ('threads', 'memory_limit')");
print_r($statement->fetchAll(PDO::FETCH_COLUMN));

// TODO SET errors_as_json = true;

unset($db);
