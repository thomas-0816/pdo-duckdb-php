<?php

Swoole\Coroutine::set(['hook_flags'=> SWOOLE_HOOK_ALL]);

// run parallel, connection opened inside of coroutine
$start = microtime(true);
Swoole\Coroutine\run(function() {
    $wg = new Swoole\Coroutine\WaitGroup();
    for ($i = 0; $i < 5; $i++) {
        Swoole\Coroutine::create(function () use ($wg) {
            $wg->add();
            $pdo = new PDO('duckdb::memory:');
            $pdo->exec("select sleep_ms(1000)");
            echo '.';
            $wg->done();
        });
    }
    $wg->wait(10);
    echo 'done';
});
echo microtime(true) - $start, PHP_EOL;

// run sequentially, connection opened outside of coroutine
$start = microtime(true);
Swoole\Coroutine\run(function() {
    $wg = new Swoole\Coroutine\WaitGroup();
    $pdo = new PDO('duckdb::memory:');
    for ($i = 0; $i < 5; $i++) {
        Swoole\Coroutine::create(function () use ($wg, $pdo) {
            $wg->add();
            $pdo->exec("select sleep_ms(1000)");
            echo '.';
            $wg->done();
        });
    }
    $wg->wait(10);
    echo 'done';
});
echo microtime(true) - $start, PHP_EOL;


// run sequentially, random delay, changes order
Swoole\Coroutine\run(function() {
    $pdo = new PDO('duckdb::memory:');
    $pdo->exec('create table orders (user_id integer primary key)');
    $wg = new Swoole\Coroutine\WaitGroup();
    for ($i = 0; $i < 10; $i++) {
        Swoole\Coroutine::create(function () use ($wg, $pdo, $i) {
            $wg->add();
            usleep(rand(100_000, 200_000));
            $pdo->exec("INSERT INTO orders (user_id) VALUES ($i)");
            $wg->done();
        });
    }
    $wg->wait(10);
    echo json_encode($pdo->query("SELECT * from orders")->fetchAll(PDO::FETCH_COLUMN)), PHP_EOL;
});


if (! class_exists(Swoole\Thread::class)) {
    return;
}

// run in parallel using threads, in-memory
file_put_contents('/tmp/swoole_test.php', '<?php
    $args = Swoole\Thread::getArguments();
    $db = new PDO("duckdb::memory:");
    $rows = $db->query("select sleep_ms(500 + $args[0])");
    $args[1][] = json_encode($rows->fetchAll(PDO::FETCH_ASSOC));
');
$start = microtime(true);
$threads = [];
$list = new Swoole\Thread\ArrayList();
for ($i = 0; $i < 8; $i++) {
    $threads[] = new Swoole\Thread('/tmp/swoole_test.php' , $i, $list);
}
for ($i = 0; $i < 8; $i++) {
    $threads[$i]->join();
}
var_dump($list->toArray());
echo '5x sleep 0.5s took ', round(microtime(true) - $start, 2), 's', PHP_EOL;


// run write in parallel using threads and Quack protocol, on-disk
@unlink('/tmp/test.duckdb');
$pdo = new PDO('duckdb:/tmp/test.duckdb');
$pdo->exec('create table orders (user_id integer primary key)');
$result = $pdo->query("CALL quack_serve('quack:127.0.0.1:9494')")->fetch(PDO::FETCH_ASSOC);
$authToken = $result['auth_token'];
file_put_contents('/tmp/swoole_test.php', <<<'END'
<?php
    $args = Swoole\Thread::getArguments();
    $pdo = new PDO('duckdb::memory:');
    $pdo->exec("ATTACH 'quack:127.0.0.1:9494' AS remote_db (TOKEN '{$args[1]}', DISABLE_SSL true)");
    $pdo->exec("INSERT INTO remote_db.orders (user_id) VALUES ({$args[0]})");
END);
$threads = [];
for ($i = 0; $i < 10; $i++) {
    $threads[] = new Swoole\Thread('/tmp/swoole_test.php' , $i, $authToken);
}
for ($i = 0; $i < 8; $i++) {
    $threads[$i]->join();
}
$pdo->exec("CALL quack_stop('quack:127.0.0.1')");
echo json_encode($pdo->query("SELECT * from orders")->fetchAll(PDO::FETCH_COLUMN)), PHP_EOL;


// run read and write in parallel using coroutines to write and threads to read, on-disk
Swoole\Coroutine\run(function() {
    file_put_contents('/tmp/swoole_test.php', <<<'END'
    <?php
        $args = Swoole\Thread::getArguments();
        $pdo = new PDO('duckdb:/tmp/test.duckdb', null, null, [PDO::DUCKDB_ATTR_CONFIG => ['access_mode' => 'read_only']]);
        $args[1][] = json_encode($pdo->query("SELECT * from orders")->fetchAll(PDO::FETCH_COLUMN));
    END);
    @unlink('/tmp/test.duckdb');
    $pdo = new PDO('duckdb:/tmp/test.duckdb');
    $pdo->exec('create table orders (user_id integer primary key)');
    $threads = [];
    $coroutines = [];
    $list = new Swoole\Thread\ArrayList();
    $wg = new Swoole\Coroutine\WaitGroup();
    for ($i = 0; $i < 10; $i++) {
        $coroutines[] = Swoole\Coroutine::create(function() use ($i, $pdo, $wg) {
            $wg->add();
            usleep(rand(15_000, 20_000));
            $pdo->exec("INSERT INTO orders (user_id) VALUES ($i)");
            $wg->done();
        });
        $threads[] = new Swoole\Thread('/tmp/swoole_test.php' , $i, $list);
    }
    $wg->wait(10);
    for ($i = 0; $i < 10; $i++) {
        $threads[$i]->join();
    }
    print_r($list->toArray());
    echo json_encode($pdo->query("SELECT * from orders")->fetchAll(PDO::FETCH_COLUMN)), PHP_EOL;
});


/* PDOPool is currently not supported */
