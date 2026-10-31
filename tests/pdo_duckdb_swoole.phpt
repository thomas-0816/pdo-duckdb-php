--TEST--
PDO_duckdb: Test swoole
--EXTENSIONS--
pdo_duckdb
swoole
--FILE--
<?php

$db = new PDO('duckdb::memory:', null, null, [PDO::DUCKDB_ATTR_CONFIG => ['threads' => 1]]);
$db->exec("CREATE TABLE t (i INTEGER, v VARCHAR)");
$statement = $db->prepare("INSERT INTO t VALUES (?, ?)");
$statement->execute([1, 'hello']);
unset($db);

Swoole\Coroutine\run(function() {
    $wg = new Swoole\Coroutine\WaitGroup();
    for ($i = 0; $i < 5; $i++) {
        $wg->add();
        Swoole\Coroutine::create(function () use ($wg, $i) {
            $pdo = new PDO('duckdb::memory:', null, null, [PDO::DUCKDB_ATTR_CONFIG => ['threads' => 1]]);
            echo $pdo->query("select $i, sleep_ms(500 - ($i * 100))")->fetchColumn();
            $wg->done();
        });
    }
    $wg->wait();
    echo 'done', PHP_EOL;
});

Swoole\Coroutine\run(function() {
    $pdo = new PDO('duckdb::memory:', null, null, [PDO::DUCKDB_ATTR_CONFIG => ['threads' => 5]]);
    $wg = new Swoole\Coroutine\WaitGroup();
    for ($i = 0; $i < 5; $i++) {
        $wg->add();
        Swoole\Coroutine::create(function () use ($wg, $pdo, $i) {
            Swoole\Coroutine::sleep(0.5 - ($i * 0.1));
            echo $pdo->query("select $i")->fetchColumn();
            $wg->done();
        });
    }
    $wg->wait();
    echo 'done', PHP_EOL;
});

Swoole\Coroutine\run(function() {
    $pdo = new PDO('duckdb::memory:', null, null, [PDO::DUCKDB_ATTR_CONFIG => ['threads' => 2]]);
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
