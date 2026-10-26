--TEST--
PDO_duckdb: Test memory limit (soft)
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

if (getenv('USE_ZEND_ALLOC') === '0') { // valgrind-mode has no memory-limit
    echo "Fatal error: Allowed memory size of 33554432 bytes exhausted foo in bar on line 9";
}

ini_set('memory_limit', '32M');
$db = new PDO('duckdb::memory:');
$result = $db->query("SELECT * FROM generate_series(100_000)")->fetchAll(PDO::FETCH_ASSOC);

?>
--EXPECTF--
Fatal error: Allowed memory size of 33554432 bytes exhausted %s in %s on line 9
