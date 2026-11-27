--TEST--
PDO_duckdb: Test PDO::ATTR_EMULATE_PREPARES
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
var_dump($db->getAttribute(PDO::ATTR_EMULATE_PREPARES));

$db = new PDO('duckdb::memory:', null, null, [PDO::ATTR_EMULATE_PREPARES => true]);
var_dump($db->getAttribute(PDO::ATTR_EMULATE_PREPARES));

$statement = $db->prepare('SELECT ? as a, ? as b, ? as c, ? as d, ? as e, ? as f');
@$statement->execute([null, NAN, INF, -INF, true, false]);
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$db->exec('CREATE TABLE t (i INTEGER, b BOOLEAN, v VARCHAR)');

$statement = $db->prepare('INSERT INTO t VALUES (?, ?, ?)');
$statement->execute([1, true, "O'Brian"]);
$statement = $db->prepare('INSERT INTO t VALUES (:i, :b, :v)');
$statement->execute(['i' => 2, 'b' => true, 'v' => 'two']);

$statement = $db->prepare('INSERT INTO t VALUES (?, ?, ?)');
$statement->bindValue(1, 3, PDO::PARAM_INT);
$statement->bindValue(2, false, PDO::PARAM_BOOL);
$statement->bindValue(3, null, PDO::PARAM_NULL);
$statement->execute();

$statement->bindValue(1, 4, PDO::PARAM_INT);
$statement->bindValue(2, true, PDO::PARAM_BOOL);
$statement->bindValue(3, "O'Brian", PDO::PARAM_STR);
$statement->execute();

$db->exec("set variable foo = 'bar'");
$statement = $db->prepare('INSERT INTO t VALUES (?, ?, $foo)');
$statement->execute([5, true]);

$db->exec("set variable bar = 'baz'");
$statement = $db->prepare('INSERT INTO t VALUES (:i, :b, $bar)');
$statement->execute(['i' => 6, 'b' => 0]); // TODO fix false

$statement = $db->query('SELECT * FROM t');
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$db->exec('CREATE TABLE b (v BLOB)');
$statement = $db->prepare('INSERT INTO b VALUES (?)');
$statement->bindValue(1, "\x00\xAA\xFF", PDO::PARAM_LOB);
$statement->execute();
$stream = fopen('php://memory', 'r+');
fwrite($stream, "\x01\x02");
rewind($stream);
$statement = $db->prepare('INSERT INTO b VALUES (?)');
$statement->bindValue(1, $stream, PDO::PARAM_LOB);
$statement->execute();
fclose($stream);

$statement = $db->query('SELECT v::varchar FROM b');
var_dump($statement->fetchAll(PDO::FETCH_COLUMN));

$db->exec('CREATE TABLE n (v VARCHAR)');
$statement = $db->prepare('INSERT INTO n VALUES (?)');
$statement->execute(["a\0b"]);
$statement->bindValue(1, "c\0d", PDO::PARAM_STR);
$statement->execute();

$statement = $db->prepare('SELECT v FROM n');
$statement->execute();
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->prepare("SELECT 1\0");
$statement->execute();
var_dump($statement->fetchColumn());

$statement = $db->query("SELECT 1\0");
var_dump($statement->fetchColumn());

$statement = $db->prepare('SELEC 1');
try {
    $statement->execute();
} catch (PDOException $e) {
    echo 'Caught: ', $e->getMessage(), PHP_EOL;
}

$statement = $db->prepare('SELECT * FROM invalid');
try {
    $statement->execute();
} catch (PDOException $e) {
    echo 'Caught: ', $e->getMessage(), PHP_EOL;
}

$statement = $db->prepare('SELECT invalid FROM t');
try {
    $statement->execute();
} catch (PDOException $e) {
    echo 'Caught: ', $e->getMessage(), PHP_EOL;
}

$statement = $db->prepare('INSERT INTO t VALUES (?, ?, ?)');
try {
    $statement->execute([1]);
} catch (PDOException $e) {
    echo 'Caught: ', $e->getMessage(), PHP_EOL;
}
try {
    $statement->execute([1, 2, 3, 4]);
} catch (PDOException $e) {
    echo 'Caught: ', $e->getMessage(), PHP_EOL;
}
try {
    $statement->execute(['x' => 1, 'y' => true, 'z' => 'test']);
} catch (PDOException $e) {
    echo 'Caught: ', $e->getMessage(), PHP_EOL;
}

$db = new PDO('duckdb::memory:', null, null, [PDO::ATTR_EMULATE_PREPARES => true, PDO::DUCKDB_ATTR_UNBUFFERED => true]);
$db->exec('CREATE TABLE u (i INTEGER)');
$db->exec('INSERT INTO u VALUES (1), (2)');
$statement = $db->prepare('SELECT * FROM u WHERE i > ? ORDER BY i');
$statement->execute([0]);
var_dump($statement->fetchAll(PDO::FETCH_COLUMN));

$db = new PDO('duckdb::memory:', null, null, [PDO::ATTR_EMULATE_PREPARES => true]);
$statement = $db->prepare("SELECT '?' AS \"?\", ? as foo");
$statement->execute([7]);
var_dump($statement->fetch(PDO::FETCH_ASSOC));

$statement = $db->prepare('SELECT [10, 20, 30][i:j] AS slice, :id AS id FROM (VALUES (2, 3)) t(i, j)');
$statement->execute(['id' => 42]);
var_dump($statement->fetch(PDO::FETCH_ASSOC));

$statement = $db->prepare('SELECT list_value(1, 2, 3)[1:2] AS slice, :id AS id');
$statement->execute(['id' => 42]);
var_dump($statement->fetch(PDO::FETCH_ASSOC));

$statement = $db->prepare('SELECT :1a AS a, :01 AS b');
$statement->execute(['1a' => 1, '01' => 2]);
var_dump($statement->fetch(PDO::FETCH_ASSOC));

$statement = $db->prepare('SELECT :id::INT AS id');
$statement->execute(['id' => 7]);
var_dump($statement->fetch(PDO::FETCH_ASSOC));

$statement = $db->prepare('SELECT ?, ?, ?');
@$statement->execute([NAN, INF, -INF]);
var_dump($statement->fetch(PDO::FETCH_ASSOC));

$object = new stdClass();
$object->foo = 'bar';
$statement = $db->prepare('SELECT ? as a, ? as b, ? as c');
$statement->execute([['foo' => 'bar'], [1, true, null], $object]);
var_dump($statement->fetch(PDO::FETCH_ASSOC));

$statement = $db->prepare("SELECT value->>'id' as id FROM json_each('[{\"id\":1},{\"id\":2}]')");
$statement->execute([]);
var_dump($statement->fetch(PDO::FETCH_ASSOC));

$statement = $db->prepare("SELECT '?' as a, 'it''s ?' AS \"?\" /* ? */");
$statement->execute([]);
var_dump($statement->fetch(PDO::FETCH_ASSOC));

$statement = $db->prepare("SELECT e'it''s ?' AS a, E'line1\nline2 ?' AS b, e'it''s :name' AS c, ? AS value");
$statement->execute([42]);
var_dump($statement->fetch(PDO::FETCH_ASSOC));

$statement = $db->prepare('SELECT 1 /* outer ? :id */ AS x, ? AS value');
$statement->execute([42]);
var_dump($statement->fetch(PDO::FETCH_ASSOC));

$statement = $db->prepare("SELECT {'a': 1} AS a, {'b': true} AS b, :id AS id");
$statement->execute(['id' => 42]);
var_dump($statement->fetch(PDO::FETCH_ASSOC));

$statement = $db->prepare(<<<'SQL'
    SELECT e'it''s ?' as a, $$?$$ as b, $tag$ ? $tag$ as c, '?' as d
SQL);
$statement->execute([]);
var_dump($statement->fetch(PDO::FETCH_ASSOC));

$statement = $db->prepare(<<<'SQL'
    SELECT e'it''s :foo' as a, $$ :foo $$ as b, $tag$ :bar $tag$ as c, ':baz' as d
SQL);
$statement->execute([]);
var_dump($statement->fetch(PDO::FETCH_ASSOC));

$statement = $db->prepare('SELECT \'$a\' as a, ? as b');
$statement->execute([1]);
var_dump($statement->fetch(PDO::FETCH_ASSOC));

$statement = $db->prepare("SELECT 1::INT as a, 2.5::DECIMAL(10,2) as b, ? as c");
$statement->execute([42]);
var_dump($statement->fetch(PDO::FETCH_ASSOC));

$statement = $db->prepare('SELECT :missing');
try {
$statement->execute(['other' => 1]);
} catch (PDOException $e) {
    echo 'Caught: ', $e->getMessage(), PHP_EOL;
}

$server = new PDO('duckdb::memory:');
$server->exec("CALL quack_serve('quack:127.0.0.1:9494', token='secret')");
try {
    $client = new PDO('duckdb::memory:', null, null, [
        PDO::DUCKDB_ATTR_INIT_COMMAND => "ATTACH 'quack:127.0.0.1:9494' AS remote (TOKEN 'secret'); CONNECT remote;",
        PDO::ATTR_EMULATE_PREPARES => true,
    ]);
    $client->exec('CREATE TABLE t (i INTEGER, v VARCHAR)');
    $statement = $client->prepare('INSERT INTO t VALUES (?, ?)');
    $statement->execute([1, 'one']);
    $statement->bindValue(1, 2);
    $statement->bindValue(2, 'two');
    $statement->execute();
        $client->exec("set variable foo = 'bar'");
    $statement = $client->prepare('INSERT INTO t VALUES (?, $foo)');
    $statement->execute([3]);

    $statement = $client->prepare('SELECT * FROM t WHERE v != :v AND i > :i');
    $statement->execute(['v' => 'invalid', 'i' => 0]);
    var_dump($statement->fetchAll(PDO::FETCH_ASSOC));
} finally {
    $server->exec("CALL quack_stop('quack:127.0.0.1:9494')");
}

var_dump($db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false));
var_dump($db->getAttribute(PDO::ATTR_EMULATE_PREPARES));

?>
--EXPECTF--
bool(false)
bool(true)
array(1) {
  [0]=>
  array(6) {
    ["a"]=>
    NULL
    ["b"]=>
    string(3) "NAN"
    ["c"]=>
    string(3) "INF"
    ["d"]=>
    string(4) "-INF"
    ["e"]=>
    string(1) "1"
    ["f"]=>
    string(1) "0"
  }
}
array(6) {
  [0]=>
  array(3) {
    ["i"]=>
    int(1)
    ["b"]=>
    bool(true)
    ["v"]=>
    string(7) "O'Brian"
  }
  [1]=>
  array(3) {
    ["i"]=>
    int(2)
    ["b"]=>
    bool(true)
    ["v"]=>
    string(3) "two"
  }
  [2]=>
  array(3) {
    ["i"]=>
    int(3)
    ["b"]=>
    bool(false)
    ["v"]=>
    NULL
  }
  [3]=>
  array(3) {
    ["i"]=>
    int(4)
    ["b"]=>
    bool(true)
    ["v"]=>
    string(7) "O'Brian"
  }
  [4]=>
  array(3) {
    ["i"]=>
    int(5)
    ["b"]=>
    bool(true)
    ["v"]=>
    string(3) "bar"
  }
  [5]=>
  array(3) {
    ["i"]=>
    int(6)
    ["b"]=>
    bool(false)
    ["v"]=>
    string(3) "baz"
  }
}
array(2) {
  [0]=>
  string(12) "\x00\xAA\xFF"
  [1]=>
  string(8) "\x01\x02"
}
array(2) {
  [0]=>
  array(1) {
    ["v"]=>
    string(2) "ab"
  }
  [1]=>
  array(1) {
    ["v"]=>
    string(2) "cd"
  }
}
int(1)
int(1)
Caught: SQLSTATE[HY000]: Parser Error: syntax error at or near "SELEC"

LINE 1: SELEC 1
        ^^^^^
Caught: SQLSTATE[HY000]: Catalog Error: Table with name invalid does not exist!
Did you mean "n"?

LINE 1: SELECT * FROM invalid
                      ^^^^^^^
Caught: SQLSTATE[HY000]: Binder Error: Referenced column "invalid" not found in FROM clause!
Candidate bindings: "i", "v"

LINE 1: SELECT invalid FROM t
               ^^^^^^^
Caught: SQLSTATE[HY093]: Invalid parameter number: number of bound variables does not match number of tokens
Caught: SQLSTATE[HY093]: Invalid parameter number: number of bound variables does not match number of tokens
Caught: SQLSTATE[HY093]: Invalid parameter number: parameter was not defined
array(2) {
  [0]=>
  int(1)
  [1]=>
  int(2)
}
array(2) {
  ["?"]=>
  string(1) "?"
  ["foo"]=>
  string(1) "7"
}
array(2) {
  ["slice"]=>
  array(2) {
    [0]=>
    int(20)
    [1]=>
    int(30)
  }
  ["id"]=>
  string(2) "42"
}
array(2) {
  ["slice"]=>
  array(2) {
    [0]=>
    int(1)
    [1]=>
    int(2)
  }
  ["id"]=>
  string(2) "42"
}
array(2) {
  ["a"]=>
  string(1) "1"
  ["b"]=>
  string(1) "2"
}
array(1) {
  ["id"]=>
  int(7)
}
array(3) {
  ["'NAN'"]=>
  string(3) "NAN"
  ["'INF'"]=>
  string(3) "INF"
  ["'-INF'"]=>
  string(4) "-INF"
}
array(3) {
  ["a"]=>
  string(13) "{"foo":"bar"}"
  ["b"]=>
  string(13) "[1,true,null]"
  ["c"]=>
  string(13) "{"foo":"bar"}"
}
array(1) {
  ["id"]=>
  string(1) "1"
}
array(2) {
  ["a"]=>
  string(1) "?"
  ["?"]=>
  string(6) "it's ?"
}
array(4) {
  ["a"]=>
  string(6) "it's ?"
  ["b"]=>
  string(13) "line1
line2 ?"
  ["c"]=>
  string(10) "it's :name"
  ["value"]=>
  string(2) "42"
}
array(2) {
  ["x"]=>
  int(1)
  ["value"]=>
  string(2) "42"
}
array(3) {
  ["a"]=>
  array(1) {
    ["a"]=>
    int(1)
  }
  ["b"]=>
  array(1) {
    ["b"]=>
    bool(true)
  }
  ["id"]=>
  string(2) "42"
}
array(4) {
  ["a"]=>
  string(6) "it's ?"
  ["b"]=>
  string(1) "?"
  ["c"]=>
  string(3) " ? "
  ["d"]=>
  string(1) "?"
}
array(4) {
  ["a"]=>
  string(9) "it's :foo"
  ["b"]=>
  string(6) " :foo "
  ["c"]=>
  string(6) " :bar "
  ["d"]=>
  string(4) ":baz"
}
array(2) {
  ["a"]=>
  string(2) "$a"
  ["b"]=>
  string(1) "1"
}
array(3) {
  ["a"]=>
  int(1)
  ["b"]=>
  float(2.5)
  ["c"]=>
  string(2) "42"
}
Caught: SQLSTATE[HY093]: Invalid parameter number: parameter was not defined
array(3) {
  [0]=>
  array(2) {
    ["i"]=>
    int(1)
    ["v"]=>
    string(3) "one"
  }
  [1]=>
  array(2) {
    ["i"]=>
    int(2)
    ["v"]=>
    string(3) "two"
  }
  [2]=>
  array(2) {
    ["i"]=>
    int(3)
    ["v"]=>
    string(3) "bar"
  }
}
bool(true)
bool(false)
