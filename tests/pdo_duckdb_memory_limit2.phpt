--TEST--
PDO_duckdb: Test memory limit (hard)
--EXTENSIONS--
pdo_duckdb
--SKIPIF--
<?php
if (getenv('ZEND_DONT_UNLOAD_MODULES') === '1') {
    die('skip valgrind-mode has no memory-limit');
}
?>
--FILE--
<?php

ini_set('memory_limit', '16M');
$db = new PDO('duckdb::memory:');
$result = $db->query("SELECT * FROM generate_series(200_000)")->fetchAll(PDO::FETCH_ASSOC);

?>
--EXPECTF--
Hard memory limit of %s bytes exhausted %s in %s on line 5

Termsig=9
