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
        $wg->add();
        Swoole\Coroutine::create(function () use ($wg, $i) {
            $pdo = new PDO('duckdb::memory:');
            echo $pdo->query("select $i, sleep_ms(50 - ($i * 10))")->fetchColumn();
            $wg->done();
        });
    }
    $wg->wait();
    echo 'done', PHP_EOL;
});

Swoole\Coroutine\run(function() {
    $pdo = new PDO('duckdb::memory:');
    $wg = new Swoole\Coroutine\WaitGroup();
    for ($i = 0; $i < 5; $i++) {
        $wg->add();
        Swoole\Coroutine::create(function () use ($wg, $pdo, $i) {
            usleep(50000 - ($i * 10000));
            echo $pdo->query("select $i")->fetchColumn();
            $wg->done();
        });
    }
    $wg->wait();
    echo 'done', PHP_EOL;
});

Swoole\Coroutine\run(function() {
    $pdo = new PDO('duckdb::memory:');
    $wg = new Swoole\Coroutine\WaitGroup();

    /* Hold the connection lock with a slow query. The second coroutine
     * reaches swoole_run() while the lock is taken, so it has to wait in
     * the busy loop (swoole_coroutine_usleep branch). */
    $wg->add();
    Swoole\Coroutine::create(function () use ($wg, $pdo) {
        $pdo->query('SELECT sleep_ms(500)')->fetchAll();
        echo 'slow', PHP_EOL;
        $wg->done();
    });
    $wg->add();
    Swoole\Coroutine::create(function () use ($wg, $pdo) {
        Swoole\Coroutine::sleep(0.1);
        echo $pdo->query('SELECT 42')->fetchColumn(), PHP_EOL;
        $wg->done();
    });

    $wg->wait();
    echo 'done', PHP_EOL;
});

?>
--EXPECTF--
43210done
43210done
slow
42
done
