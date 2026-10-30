--TEST--
PDO_duckdb: Test swoole
--EXTENSIONS--
pdo_duckdb
swoole
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (i INTEGER, b BIGINT, d DECIMAL(10, 2), v VARCHAR, t TUPLE(integer, string))");
$statement = $db->prepare("INSERT INTO t VALUES (?, ?, ?, ?, ?)");
$statement->execute([1, 9223372036854775807, 3.141511313212312312, 'hello', "(1, 'a')"]);

Swoole\Coroutine\run(function() {
    $wg = new Swoole\Coroutine\WaitGroup();
    for ($i = 0; $i < 5; $i++) {
        Swoole\Coroutine::create(function () use ($wg, $i) {
            $wg->add();
            $pdo = new PDO('duckdb::memory:');
            echo $pdo->query("select $i, sleep_ms(500 - ($i * 100))")->fetchColumn();
            $wg->done();
        });
    }
    $wg->wait(10);
    echo 'done', PHP_EOL;
});

Swoole\Coroutine\run(function() {
    $pdo = new PDO('duckdb::memory:');
    $wg = new Swoole\Coroutine\WaitGroup();
    for ($i = 0; $i < 5; $i++) {
        Swoole\Coroutine::create(function () use ($wg, $pdo, $i) {
            $wg->add();
            usleep(500000 - ($i * 100000));
            echo $pdo->query("select $i")->fetchColumn();
            $wg->done();
        });
    }
    $wg->wait(10);
    echo 'done', PHP_EOL;
});

?>
--EXPECTF--
43210done
43210done
