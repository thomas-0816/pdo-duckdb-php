--TEST--
PDO_duckdb: Test memory limit (hard)
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

if (getenv('USE_ZEND_ALLOC') === '0') { // valgrind-mode has no memory-limit
    echo "Hard memory limit of 67108864 bytes exhausted foo in bar on line 9\n\nTermsig=9";
}

ini_set('memory_limit', '32M');
$db = new PDO('duckdb::memory:');
$result = $db->query("SELECT * FROM generate_series(200_000)")->fetchAll(PDO::FETCH_ASSOC);

?>
--EXPECTF--
Hard memory limit of 67108864 bytes exhausted %s in %s on line 9

Termsig=9
