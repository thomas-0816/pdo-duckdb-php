--TEST--
PDO_duckdb: Test memory limit (soft)
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

ini_set('memory_limit', '12M');
$db = new PDO('duckdb::memory:');

$result = $db->query("SELECT range(500_000)::JSON")->fetchAll(PDO::FETCH_ASSOC);

?>
--EXPECTF--
Fatal error: Allowed memory size of 12582912 bytes exhausted %s in %s on line 6
