--TEST--
PDO_duckdb: Test duckdb max executioon time
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$db->exec('set max_execution_time = 100');
$start = microtime(true);
try {
    $db->query("SELECT COUNT(*) FROM range(10_000_000) t1, range(1_000) t2")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}
var_dump((int) ceil(microtime(true) - $start));

// TODO https://github.com/duckdb/duckdb/issues/28986
?>
--EXPECTF--
Caught: SQLSTATE[HY000]: INTERRUPT Error: Query exceeded maximum execution time
int(1)
