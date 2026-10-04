<?php

/*
    1 million row insert benchmark

    run:
    rm -rf /tmp/db.duckdb && cat bulk_insertion.php && echo && time php8.5 bulk_insertion.php && ls -lisah /tmp/db.duckdb

    Test results: Ubuntu 24.04, Kernel 7.0, AMD 7840U, DDR5 6400 MT/s, 8.5.11, DuckDB 1.5.6
    0.64s for the bulk insertion
    0.881s total time for running PHP (data generation, create table, insert, select)

    # kernel: default settings
    0.64s
    Array
    (
        [0] => 1000000
        [1] => 499999500000
    )

    real    0m0,881s
    user    0m0,649s
    sys     0m0,240s
    108 17M -rw-rw-r-- 1 tb tb 17M Okt  4 19:03 /tmp/db.duckdb

    # kernel: nosmt mitigations=off (runtime -13.5%)
    0.59s
    Array
    (
        [0] => 1000000
        [1] => 499999500000
    )

    real    0m0,762s
    user    0m0,638s
    sys     0m0,139s
    68 17M -rw-rw-r-- 1 tb tb 17M Okt  4 18:53 /tmp/db.duckdb
*/

error_reporting(E_ALL);
ini_set('display_errors', 1);

$data = [];
for ($i = 0; $i < 1_000_000; $i++) {
    $data[] = [$i, 'foo' . $i];
}

$db = new PDO('duckdb:/tmp/db.duckdb');
$db->exec("SET force_compression='uncompressed'");
$db->exec('CREATE TABLE t1 (id integer, v1 varchar)');
$start = microtime(true);
$stmt = $db->prepare("INSERT INTO t1 SELECT value->>0, value->>1 FROM json_each(?)");
$stmt->execute([$data]);
echo round(microtime(true) - $start, 2), 's', PHP_EOL;
print_r($db->query('SELECT count(*), sum(id) from t1')->fetch(PDO::FETCH_NUM)); // 1000000, 499999500000
