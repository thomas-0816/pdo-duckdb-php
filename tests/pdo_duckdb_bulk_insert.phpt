--TEST--
PDO_duckdb: Test bulk insert
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$data_associative = [];
for ($i = 0; $i < 100_000; $i++) {
    $data_associative[] = ['i1' => $i, 'v1' => 'foo' . $i];
}

/* json-path-associative */
$db = new PDO('duckdb::memory:');
$db->exec('CREATE TABLE t1 (i1 integer, v1 varchar)');
$db->exec("INSERT INTO t1 SELECT value->>'i1', value->>'v1' FROM json_each('" . json_encode($data_associative) . "')");
echo $db->query('SELECT count(*) from t1')->fetchColumn(), PHP_EOL;

/* json-path-associative-prepare */
$db = new PDO('duckdb::memory:');
$db->exec('CREATE TABLE t1 (i1 integer, v1 varchar)');
$stmt = $db->prepare("INSERT INTO t1 SELECT value->>'i1', value->>'v1' FROM json_each(?)");
$stmt->execute([$data_associative]);
echo $db->query('SELECT count(*) from t1')->fetchColumn(), PHP_EOL;

/* json-path-associative-prepare-bindvalue */
$db = new PDO('duckdb::memory:');
$db->exec('CREATE TABLE t1 (i1 integer, v1 varchar)');
$stmt = $db->prepare("INSERT INTO t1 SELECT value->>'i1', value->>'v1' FROM json_each(?)");
$stmt->bindValue(1, $data_associative);
$stmt->execute();
echo $db->query('SELECT count(*) from t1')->fetchColumn(), PHP_EOL;

$data_list = [];
for ($i = 0; $i < 100_000; $i++) {
    $data_list[] = [$i, 'foo' . $i];
}

/* json-path-list */
$db = new PDO('duckdb::memory:');
$db->exec('CREATE TABLE t1 (i1 integer, v1 varchar)');
$db->exec("INSERT INTO t1 SELECT value->>0, value->>1 FROM json_each('" . json_encode($data_list) . "')");
echo $db->query('SELECT count(*) from t1')->fetchColumn(), PHP_EOL;

/* json-path-list-prepare */
$db = new PDO('duckdb::memory:');
$db->exec('CREATE TABLE t1 (i1 integer, v1 varchar)');
$stmt = $db->prepare("INSERT INTO t1 SELECT value->>0, value->>1 FROM json_each(?)");
$stmt->execute([$data_list]);
echo $db->query('SELECT count(*) from t1')->fetchColumn(), PHP_EOL;

/* json-path-list-prepare-bindvalue */
$db = new PDO('duckdb::memory:');
$db->exec('CREATE TABLE t1 (i1 integer, v1 varchar)');
$stmt = $db->prepare("INSERT INTO t1 SELECT value->>0, value->>1 FROM json_each(?)");
$stmt->bindValue(1, $data_list);
$stmt->execute();
echo $db->query('SELECT count(*) from t1')->fetchColumn(), PHP_EOL;

?>
--EXPECTF--
100000
100000
100000
100000
100000
100000
