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
