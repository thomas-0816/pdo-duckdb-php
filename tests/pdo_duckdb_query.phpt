--TEST--
PDO_duckdb: Test connection
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (i INTEGER, b BIGINT, d DECIMAL(10, 2), v VARCHAR)");
$statement = $db->prepare("INSERT INTO t VALUES (?, ?, ?, ?)");
$statement->execute([1, 9223372036854775807, 3.141511313212312312, 'hello']);

var_dump($db->lastInsertId());

$statement = $db->query("SELECT * FROM t", PDO::FETCH_ASSOC);
while ($row = $statement->fetch()) { var_dump($row); }

$statement = $db->query("SELECT * FROM t", PDO::FETCH_ASSOC);
foreach ($statement->getIterator() as $row) {
    var_dump($row);
}

$statement = $db->query('SELECT * FROM t');
var_dump($statement->getColumnMeta(0));
var_dump($statement->getColumnMeta(1));
var_dump($statement->getColumnMeta(2));
var_dump($statement->getColumnMeta(3));
var_dump($statement->getColumnMeta(4));
var_dump($statement->columnCount());

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (n INTEGER NULL, i INTEGER NULL, b BIGINT NULL, d DECIMAL(10, 2) NULL, v VARCHAR NULL)");
$statement = $db->prepare("INSERT INTO t VALUES (?, ?, ?, ?, ?)");
$statement->execute([1, 2, 9223372036854775807, 3.141511313212312312, 'hello']);
$statement = $db->prepare("UPDATE t SET n = ?, i = ?, b = ?, d = ?, v = ?");
$statement->execute([2, 3, 4, 5.67, 'world']);
var_dump($db->query('SELECT * FROM t')->fetchAll(PDO::FETCH_ASSOC));


$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (n INTEGER NULL, i INTEGER NULL, b BIGINT NULL, d DECIMAL(10, 2) NULL, v VARCHAR NULL)");
$statement = $db->prepare('INSERT INTO t VALUES ($2, $1, $3, $5, $4)');
$statement->execute([2, 1, 9223372036854775807, 'hello', 3.141511313212312312]);
$statement->execute([null, null, null, null, null]);
var_dump($db->query('SELECT * FROM t')->fetchAll(PDO::FETCH_ASSOC));

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (n INTEGER NULL, i INTEGER NULL, b BIGINT NULL, d DECIMAL(10, 2) NULL, v VARCHAR NULL)");
$statement = $db->prepare('INSERT INTO t VALUES (:aa, :bb, :cc, :dd, :ee)');
$statement->execute(['bb' => 2, 'aa' => 1, 'cc' => 9223372036854775807, 'ee' => 'hello', 'dd' => 3.141511313212312312]);
var_dump($db->query('SELECT * FROM t')->fetchAll(PDO::FETCH_ASSOC));

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (n INTEGER NULL, i INTEGER NULL, b BIGINT NULL, d DECIMAL(10, 2) NULL, v VARCHAR NULL)");
$statement = $db->prepare('INSERT INTO t VALUES (:aa, :bb, :cc, :dd, :ee)');
$statement->execute([':bb' => 2, ':aa' => 1, ':cc' => 9223372036854775807, ':ee' => 'hello', ':dd' => 3.141511313212312312]);
var_dump($db->query('SELECT * FROM t')->fetchAll(PDO::FETCH_ASSOC));

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (n INTEGER NULL, i INTEGER NULL, b BIGINT NULL, d DECIMAL(10, 2) NULL, v VARCHAR NULL)");
$statement = $db->prepare('INSERT INTO t VALUES (:aa, :bb, :cc, :dd, :ee)');
try {
    $statement->execute(['bb' => 2, 'aa' => 1]);
} catch (Exception $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (n INTEGER NULL, i INTEGER NULL, b BIGINT NULL, d DECIMAL(10, 2) NULL, v VARCHAR NULL)");
$statement = $db->prepare('INSERT INTO t VALUES (:aa, :bb, :cc, :dd, :ee)');
$statement->bindValue('aa', null, PDO::PARAM_NULL);
$statement->bindValue('bb', 201, PDO::PARAM_INT);
$statement->bindValue('cc', 300, PDO::PARAM_INT);
$statement->bindValue('dd', 42.21, PDO::PARAM_STR);
$statement->bindValue('ee', 'test', PDO::PARAM_STR);
$statement->execute();
$statement = $db->prepare('INSERT INTO t VALUES (?, ?, ?, ?, ?)');
$statement->bindValue(1, null);
$statement->bindValue(2, 202);
$statement->bindValue(3, 300);
$statement->bindValue(4, 42.21);
$statement->bindValue(5, 'test');
$statement->execute();
$statement = $db->prepare('INSERT INTO t VALUES (:aa, :bb, :cc, :dd, :ee)');
$statement->bindValue('aa', null);
$statement->bindValue('bb', 203);
$statement->bindValue('cc', 300);
$statement->bindValue('dd', 42.21);
$statement->bindValue('ee', 'test');
$statement->execute();
$statement = $db->prepare('INSERT INTO t VALUES (:aa, :bb, :cc, :dd, :ee)');
$statement->bindValue(':aa', null);
$statement->bindValue(':bb', 204);
$statement->bindValue(':cc', 300);
$statement->bindValue(':dd', 42.21);
$statement->bindValue(':ee', 'test');
$statement->execute();
$statement = $db->prepare('INSERT INTO t VALUES ($aa, $bb, $cc, $dd, $ee)');
$statement->bindValue('aa', null);
$statement->bindValue('bb', 205);
$statement->bindValue('cc', 300);
$statement->bindValue('dd', 42.21);
$statement->bindValue('ee', 'test');
$statement->execute();
var_dump($db->query('SELECT * FROM t')->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->prepare('INSERT INTO t VALUES (:aa, :bb, :cc, :dd, :ee)');
$statement->bindValue('aa', null);
$statement->bindValue('bb', 203);
try {
    $statement->execute();
} catch (Exception $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

$statement = $db->prepare('INSERT INTO t VALUES (?, ?, ?, ?, ?)');
$statement->bindValue(1, null);
$statement->bindValue(2, 203);
try {
    $statement->execute();
} catch (Exception $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

$db = new PDO('duckdb::memory:');
$db->exec('CREATE TABLE t1 (i INTEGER)');
$statement = $db->query("INSERT INTO t1
    SELECT 42
    RETURNING *");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$count = 0;
$db = new PDO('duckdb::memory:');
$db->setAttribute(PDO::DUCKDB_ATTR_UNBUFFERED, false);
var_dump($db->getAttribute(PDO::DUCKDB_ATTR_UNBUFFERED));
foreach ($db->query("SELECT range::INTEGER AS n FROM range(10000) ORDER BY n") as $row) {
    $count++;
}
$db->setAttribute(PDO::DUCKDB_ATTR_UNBUFFERED, true);
var_dump($db->getAttribute(PDO::DUCKDB_ATTR_UNBUFFERED));
foreach ($db->query("SELECT range::INTEGER AS n FROM range(10000) ORDER BY n") as $row) {
    $count++;
}
echo $count . PHP_EOL;

$db = new PDO('duckdb::memory:');
$statement = $db->query("SELECT 1\0 as a, 'te\0st' as b");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$db->exec("CREATE TABLE t (v VARCHAR)");
$statement = $db->prepare("INSERT INTO t VALUES (?)");
$statement->execute(["he\0llo"]);
$statement = $db->prepare('INSERT INTO t VALUES (:aa)');
$statement->bindValue('aa', "he\0llo", PDO::PARAM_STR);
$statement->execute();
$statement = $db->prepare("INSERT INTO t VALUES (?)");
$statement->bindValue(1, "he\0llo");
$statement->execute();
$statement = $db->prepare("INSERT INTO t VALUES (?)");
$statement->bindValue(1, "he\0llo", PDO::PARAM_STR);
$statement->execute();
$statement = $db->query("SELECT * FROM t");
echo json_encode($statement->fetchAll(PDO::FETCH_COLUMN)), PHP_EOL;

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (n INTEGER NULL, i INTEGER NULL, b BIGINT NULL, d DECIMAL(10, 2) NULL, v VARCHAR NULL)");
$statement = $db->prepare('INSERT INTO t VALUES ($aa, $bb, $cc, $dd, $ee)');
$statement->execute(['bb' => 2, 'aa' => 1, 'cc' => 9223372036854775807, 'ee' => 'hello', 'dd' => 3.141511313212312312]);
var_dump($db->query('SELECT * FROM t')->fetchAll(PDO::FETCH_ASSOC));

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (n INTEGER NULL, i INTEGER NULL, b BIGINT NULL, d DECIMAL(10, 2) NULL, v VARCHAR NULL)");
$statement = $db->prepare('INSERT INTO t VALUES ($aa, $bb, $cc, $dd, $ee)');
$statement->execute(['$bb' => 2, '$aa' => 1, '$cc' => 9223372036854775807, '$ee' => '$hello', '$dd' => 3.141511313212312312]);
var_dump($db->query('SELECT * FROM t')->fetchAll(PDO::FETCH_ASSOC));

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (v VARCHAR[])");
$statement = $db->prepare("INSERT INTO t VALUES (?)");
$statement->execute([['hello', 'world']]);
var_dump($db->query('SELECT * FROM t')->fetchAll(PDO::FETCH_ASSOC));

$s = new stdClass();
$s->foo = 'bar';
$s->hello = 'world';
$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (j JSON)");
$statement = $db->prepare("INSERT INTO t VALUES (?)");
$statement->execute([$s]);
$statement->bindValue(1, ['foo', 'bar'], PDO::PARAM_LOB);
$statement->execute();
$statement->bindValue(1, $s, PDO::PARAM_LOB);
$statement->execute();
$statement->execute([json_encode($s)]);
$statement->execute([json_encode(['foo', 'bar'])]);
var_dump($db->query('SELECT * FROM t')->fetchAll(PDO::FETCH_ASSOC));

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (b BIGINT NULL)");
$statement = $db->prepare('INSERT INTO t VALUES (:bb)');
$statement->bindValue('bb', 9223372036854775807, PDO::PARAM_INT);
$statement->execute();
$statement = $db->query("SELECT * FROM t");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$db = new PDO('duckdb::memory:');
$db->setAttribute(PDO::ATTR_TIMEOUT, 1);
try {
  $db->exec("select sleep_ms(1100)");
} catch (Exception $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

?>
--EXPECTF--
string(1) "0"
array(4) {
  ["i"]=>
  int(1)
  ["b"]=>
  int(9223372036854775807)
  ["d"]=>
  float(3.14)
  ["v"]=>
  string(5) "hello"
}
array(4) {
  ["i"]=>
  int(1)
  ["b"]=>
  int(9223372036854775807)
  ["d"]=>
  float(3.14)
  ["v"]=>
  string(5) "hello"
}
array(7) {
  ["native_type"]=>
  string(7) "integer"
  ["pdo_type"]=>
  int(1)
  ["duckdb:decl_type"]=>
  string(7) "integer"
  ["flags"]=>
  array(0) {
  }
  ["name"]=>
  string(1) "i"
  ["len"]=>
  int(0)
  ["precision"]=>
  int(0)
}
array(7) {
  ["native_type"]=>
  string(6) "bigint"
  ["pdo_type"]=>
  int(1)
  ["duckdb:decl_type"]=>
  string(6) "bigint"
  ["flags"]=>
  array(0) {
  }
  ["name"]=>
  string(1) "b"
  ["len"]=>
  int(0)
  ["precision"]=>
  int(0)
}
array(7) {
  ["native_type"]=>
  string(7) "decimal"
  ["pdo_type"]=>
  int(2)
  ["duckdb:decl_type"]=>
  string(7) "decimal"
  ["flags"]=>
  array(0) {
  }
  ["name"]=>
  string(1) "d"
  ["len"]=>
  int(0)
  ["precision"]=>
  int(0)
}
array(7) {
  ["native_type"]=>
  string(7) "varchar"
  ["pdo_type"]=>
  int(2)
  ["duckdb:decl_type"]=>
  string(7) "varchar"
  ["flags"]=>
  array(0) {
  }
  ["name"]=>
  string(1) "v"
  ["len"]=>
  int(0)
  ["precision"]=>
  int(0)
}
bool(false)
int(4)
array(1) {
  [0]=>
  array(5) {
    ["n"]=>
    int(2)
    ["i"]=>
    int(3)
    ["b"]=>
    int(4)
    ["d"]=>
    float(5.67)
    ["v"]=>
    string(5) "world"
  }
}
array(2) {
  [0]=>
  array(5) {
    ["n"]=>
    int(1)
    ["i"]=>
    int(2)
    ["b"]=>
    int(9223372036854775807)
    ["d"]=>
    float(3.14)
    ["v"]=>
    string(5) "hello"
  }
  [1]=>
  array(5) {
    ["n"]=>
    NULL
    ["i"]=>
    NULL
    ["b"]=>
    NULL
    ["d"]=>
    NULL
    ["v"]=>
    NULL
  }
}
array(1) {
  [0]=>
  array(5) {
    ["n"]=>
    int(1)
    ["i"]=>
    int(2)
    ["b"]=>
    int(9223372036854775807)
    ["d"]=>
    float(3.14)
    ["v"]=>
    string(5) "hello"
  }
}
array(1) {
  [0]=>
  array(5) {
    ["n"]=>
    int(1)
    ["i"]=>
    int(2)
    ["b"]=>
    int(9223372036854775807)
    ["d"]=>
    float(3.14)
    ["v"]=>
    string(5) "hello"
  }
}
Caught: SQLSTATE[HY000]: Invalid Input Error: Values were not provided for the following prepared statement parameters: cc, dd, ee
array(5) {
  [0]=>
  array(5) {
    ["n"]=>
    NULL
    ["i"]=>
    int(201)
    ["b"]=>
    int(300)
    ["d"]=>
    float(42.21)
    ["v"]=>
    string(4) "test"
  }
  [1]=>
  array(5) {
    ["n"]=>
    NULL
    ["i"]=>
    int(202)
    ["b"]=>
    int(300)
    ["d"]=>
    float(42.21)
    ["v"]=>
    string(4) "test"
  }
  [2]=>
  array(5) {
    ["n"]=>
    NULL
    ["i"]=>
    int(203)
    ["b"]=>
    int(300)
    ["d"]=>
    float(42.21)
    ["v"]=>
    string(4) "test"
  }
  [3]=>
  array(5) {
    ["n"]=>
    NULL
    ["i"]=>
    int(204)
    ["b"]=>
    int(300)
    ["d"]=>
    float(42.21)
    ["v"]=>
    string(4) "test"
  }
  [4]=>
  array(5) {
    ["n"]=>
    NULL
    ["i"]=>
    int(205)
    ["b"]=>
    int(300)
    ["d"]=>
    float(42.21)
    ["v"]=>
    string(4) "test"
  }
}
Caught: SQLSTATE[HY000]: Invalid Input Error: Values were not provided for the following prepared statement parameters: cc, dd, ee
Caught: SQLSTATE[HY000]: Invalid Input Error: Values were not provided for the following prepared statement parameters: 3, 4, 5
array(1) {
  [0]=>
  array(1) {
    ["i"]=>
    int(42)
  }
}
bool(false)
bool(true)
20000
array(1) {
  [0]=>
  array(2) {
    ["a"]=>
    int(1)
    ["b"]=>
    string(4) "test"
  }
}
["he\u0000llo","he\u0000llo","he\u0000llo","he\u0000llo"]
array(1) {
  [0]=>
  array(5) {
    ["n"]=>
    int(1)
    ["i"]=>
    int(2)
    ["b"]=>
    int(9223372036854775807)
    ["d"]=>
    float(3.14)
    ["v"]=>
    string(5) "hello"
  }
}
array(1) {
  [0]=>
  array(5) {
    ["n"]=>
    int(1)
    ["i"]=>
    int(2)
    ["b"]=>
    int(9223372036854775807)
    ["d"]=>
    float(3.14)
    ["v"]=>
    string(6) "$hello"
  }
}
array(1) {
  [0]=>
  array(1) {
    ["v"]=>
    array(2) {
      [0]=>
      string(5) "hello"
      [1]=>
      string(5) "world"
    }
  }
}
array(5) {
  [0]=>
  array(1) {
    ["j"]=>
    array(2) {
      ["foo"]=>
      string(3) "bar"
      ["hello"]=>
      string(5) "world"
    }
  }
  [1]=>
  array(1) {
    ["j"]=>
    array(2) {
      [0]=>
      string(3) "foo"
      [1]=>
      string(3) "bar"
    }
  }
  [2]=>
  array(1) {
    ["j"]=>
    array(2) {
      ["foo"]=>
      string(3) "bar"
      ["hello"]=>
      string(5) "world"
    }
  }
  [3]=>
  array(1) {
    ["j"]=>
    array(2) {
      ["foo"]=>
      string(3) "bar"
      ["hello"]=>
      string(5) "world"
    }
  }
  [4]=>
  array(1) {
    ["j"]=>
    array(2) {
      [0]=>
      string(3) "foo"
      [1]=>
      string(3) "bar"
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["b"]=>
    int(9223372036854775807)
  }
}
Caught: SQLSTATE[HY000]: INTERRUPT Error: Interrupted!
