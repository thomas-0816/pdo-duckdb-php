<?php

// run sequentially
$pdo = new PDO('duckdb::memory:');
$pdo->exec('create table orders (user_id integer primary key)');
$coroutines = [];
for ($i = 0; $i < 10; $i++) {
    $coroutines[] = Async\spawn(function() use ($pdo, $i) {
        $pdo->exec("INSERT INTO orders (user_id) VALUES ($i)");
    });
}
Async\await_all($coroutines);

echo json_encode($pdo->query("SELECT * from orders")->fetchAll(PDO::FETCH_COLUMN)), PHP_EOL;


// run sequentially, random delay, changes order
$pdo = new PDO('duckdb::memory:');
$pdo->exec('create table orders (user_id integer primary key)');
$coroutines = [];
for ($i = 0; $i < 10; $i++) {
    $coroutines[] = Async\spawn(function() use ($pdo, $i) {
        Async\delay(rand(100, 200));
        $pdo->exec("INSERT INTO orders (user_id) VALUES ($i)");
    });
}
Async\await_all($coroutines);

echo json_encode($pdo->query("SELECT * from orders")->fetchAll(PDO::FETCH_COLUMN)), PHP_EOL;


// run in parallel using threads, in-memory
$start = microtime(true);
$threads = [];
for ($i = 0; $i < 5; $i++) {
    $threads[] = Async\spawn_thread(function() use ($i) {
        $db = new PDO('duckdb::memory:');
        $rows = $db->query("select sleep_ms(500 + $i)");
        return json_encode($rows->fetchAll(PDO::FETCH_ASSOC));
    });
}

[$result, $errors] = Async\await_all($threads);
print_r($result);
echo '5x sleep 0.5s took ', round(microtime(true) - $start, 2), 's', PHP_EOL;


// run write in parallel using threads, on-disk
@unlink('/tmp/test.duckdb');
$pdo = new PDO('duckdb:/tmp/test.duckdb');
$pdo->exec('create table orders (user_id integer primary key)');
$result = $pdo->query("CALL quack_serve('quack:127.0.0.1:9494')")->fetch(PDO::FETCH_ASSOC);
// print_r($result);
$authToken = $result['auth_token'];

$threads = [];
for ($i = 0; $i < 10; $i++) {
    $threads[] = Async\spawn_thread(function() use ($i, $authToken) {
        $pdo = new PDO('duckdb::memory:');
        $pdo->exec("ATTACH 'quack:127.0.0.1:9494' AS remote_db (TOKEN '{$authToken}', DISABLE_SSL true)");
        $pdo->exec("INSERT INTO remote_db.orders (user_id) VALUES ($i)");
    });
}
Async\await_all($threads);

$pdo->exec("CALL quack_stop('quack:127.0.0.1')");
echo json_encode($pdo->query("SELECT * from orders")->fetchAll(PDO::FETCH_COLUMN)), PHP_EOL;


// run read and write in parallel using coroutines, on-disk
@unlink('/tmp/test.duckdb');
$pdo = new PDO('duckdb:/tmp/test.duckdb');
$pdo->exec('create table orders (user_id integer primary key)');
$threads = [];
$coroutines = [];
for ($i = 0; $i < 10; $i++) {
    $threads[] = Async\spawn_thread(function() {
        Async\delay(rand(5, 20));
        $pdo = new PDO('duckdb:/tmp/test.duckdb', null, null, [PDO::DUCKDB_ATTR_CONFIG => ['access_mode' => 'read_only']]);
        return json_encode($pdo->query("SELECT * from orders")->fetchAll(PDO::FETCH_COLUMN));
    });
    $coroutines[] = Async\spawn(function() use ($i, $pdo) {
        Async\delay(rand(0, 20));
        $pdo->exec("INSERT INTO orders (user_id) VALUES ($i)");
    });
}
Async\await_all($coroutines);
[$result, $errors] = Async\await_all($threads);
print_r($result);

echo json_encode($pdo->query("SELECT * from orders")->fetchAll(PDO::FETCH_COLUMN)), PHP_EOL;
