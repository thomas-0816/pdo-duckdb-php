--TEST--
PDO_duckdb: Test memory limit (soft)
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

ini_set('memory_limit', '32M');
$db = new PDO('duckdb::memory:');
$result = $db->query("SELECT * FROM generate_series(100_000)")->fetchAll(PDO::FETCH_ASSOC);

?>
--EXPECTF--
Fatal error: Allowed memory size of 33554432 bytes exhausted (tried to allocate 2097152 bytes) in %s on line 5
