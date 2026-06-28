--TEST--
PDO_duckdb: Test variant
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$db->exec("create table t1 (v VARIANT)");
$statement = $db->prepare("INSERT INTO t1 VALUES (?), (?), (?), (?), (?), (?), (?)");
$statement->execute(['hello', 42, 42.21, null, [1, 2], ['foo', 'bar'], ['foo' => 'bar']]);
$statement = $db->query("SELECT * FROM t1");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$db = new PDO('duckdb::memory:', null, null, [PDO::DUCKDB_ATTR_CONFIG => ['timezone' => 'Europe/Berlin', 'extension_directory' => getcwd()]]);
$db->exec("create table t1 (v VARIANT)");
$db->exec("CREATE TYPE mood AS ENUM ('sad', 'ok', 'happy')");
$statement = $db->prepare("INSERT INTO t1 VALUES (?), (?), (?), (?), (?), (?), (?), (?), (?)");
$statement->execute(['hello', 42, 42.21, null, [1, 2], ['foo', 'bar', true, null], ['foo' => 'bar'], 9223372036854775807, '340282366920938463463374607431768211455']);
$db->exec("INSERT INTO t1 VALUES (1/0), (-1/0), (0/0), ('101010'::BIT), ('2969-01-01'::date), (INTERVAL 1 YEAR), (true), (uuidv4()), ('sad'::mood), (union_value(str := 'three'))");
$db->exec("INSERT INTO t1 VALUES (MAP {'key1': 10}), ('[1, null, {\"key\": \"value\"}]'::JSON), ({'key1': 'value1'})");
$db->exec("INSERT INTO t1 VALUES (TIMESTAMP_NS '1992-09-20 11:30:00.123456789'), (TIMESTAMP_MS '1992-09-20 11:30:00.123456789'), (TIMESTAMP_S '1992-09-20 11:30:00.123456789')");
$db->exec("INSERT INTO t1 VALUES (TIMESTAMPTZ '1992-09-20 11:30:00.123456789'), (TIMESTAMPTZ '1992-09-20 12:30:00.123456789+01:00'), (timezone('America/Denver', TIMESTAMP '2001-02-16 20:38:40'))");

$statement = $db->query("SELECT * FROM t1");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$db = new PDO('duckdb::memory:', null, null, [PDO::DUCKDB_ATTR_CONFIG => ['timezone' => 'UTC']]);
$db->exec("create table t1 (v2 VARIANT)");
$db->exec("INSERT INTO t1 VALUES (TIMESTAMPTZ '1992-09-20 11:30:00.123456789'), (TIMESTAMPTZ '1992-09-20 12:30:00.123456789+01:00'), (timezone('America/Denver', TIMESTAMP '2001-02-16 20:38:40'))");
$statement = $db->query("SELECT * FROM t1");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$db = new PDO('duckdb::memory:');
$db->exec("create table t1 (v3 VARIANT)");
$db->exec("INSERT INTO t1 VALUES (TIMESTAMPTZ '1992-09-20 11:30:00.123456789'), (TIMESTAMPTZ '1992-09-20 12:30:00.123456789+01:00'), (timezone('America/Denver', TIMESTAMP '2001-02-16 20:38:40'))");
$statement = $db->query("SELECT * FROM t1");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$db = new PDO('duckdb::memory:');
$db->exec("create table t1 (s STRUCT(v VARIANT))");
$db->exec("CREATE TYPE mood AS ENUM ('sad', 'ok', 'happy')");
$statement = $db->prepare("INSERT INTO t1 VALUES (?)");
$statement->execute([['v' => 'hello']]);
$db->exec("INSERT INTO t1 VALUES ({'v': MAP {'key1': 10}}), ({'v': {'key1': 'value1'}})");

$statement = $db->query("SELECT * FROM t1");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

?>
--EXPECTF--
array(7) {
  [0]=>
  array(1) {
    ["v"]=>
    string(5) "hello"
  }
  [1]=>
  array(1) {
    ["v"]=>
    int(42)
  }
  [2]=>
  array(1) {
    ["v"]=>
    float(42.21)
  }
  [3]=>
  array(1) {
    ["v"]=>
    NULL
  }
  [4]=>
  array(1) {
    ["v"]=>
    array(2) {
      [0]=>
      int(1)
      [1]=>
      int(2)
    }
  }
  [5]=>
  array(1) {
    ["v"]=>
    array(2) {
      [0]=>
      string(3) "foo"
      [1]=>
      string(3) "bar"
    }
  }
  [6]=>
  array(1) {
    ["v"]=>
    array(1) {
      ["foo"]=>
      string(3) "bar"
    }
  }
}
array(28) {
  [0]=>
  array(1) {
    ["v"]=>
    string(5) "hello"
  }
  [1]=>
  array(1) {
    ["v"]=>
    int(42)
  }
  [2]=>
  array(1) {
    ["v"]=>
    float(42.21)
  }
  [3]=>
  array(1) {
    ["v"]=>
    NULL
  }
  [4]=>
  array(1) {
    ["v"]=>
    array(2) {
      [0]=>
      int(1)
      [1]=>
      int(2)
    }
  }
  [5]=>
  array(1) {
    ["v"]=>
    array(4) {
      [0]=>
      string(3) "foo"
      [1]=>
      string(3) "bar"
      [2]=>
      bool(true)
      [3]=>
      NULL
    }
  }
  [6]=>
  array(1) {
    ["v"]=>
    array(1) {
      ["foo"]=>
      string(3) "bar"
    }
  }
  [7]=>
  array(1) {
    ["v"]=>
    int(9223372036854775807)
  }
  [8]=>
  array(1) {
    ["v"]=>
    string(39) "340282366920938463463374607431768211455"
  }
  [9]=>
  array(1) {
    ["v"]=>
    string(8) "Infinity"
  }
  [10]=>
  array(1) {
    ["v"]=>
    string(9) "-Infinity"
  }
  [11]=>
  array(1) {
    ["v"]=>
    string(3) "NaN"
  }
  [12]=>
  array(1) {
    ["v"]=>
    int(101010)
  }
  [13]=>
  array(1) {
    ["v"]=>
    string(10) "2969-01-01"
  }
  [14]=>
  array(1) {
    ["v"]=>
    string(6) "1 year"
  }
  [15]=>
  array(1) {
    ["v"]=>
    bool(true)
  }
  [16]=>
  array(1) {
    ["v"]=>
    string(36) "%s"
  }
  [17]=>
  array(1) {
    ["v"]=>
    string(3) "sad"
  }
  [18]=>
  array(1) {
    ["v"]=>
    string(5) "three"
  }
  [19]=>
  array(1) {
    ["v"]=>
    array(1) {
      [0]=>
      array(2) {
        ["key"]=>
        string(4) "key1"
        ["value"]=>
        int(10)
      }
    }
  }
  [20]=>
  array(1) {
    ["v"]=>
    array(3) {
      [0]=>
      int(1)
      [1]=>
      NULL
      [2]=>
      array(1) {
        ["key"]=>
        string(5) "value"
      }
    }
  }
  [21]=>
  array(1) {
    ["v"]=>
    array(1) {
      ["key1"]=>
      string(6) "value1"
    }
  }
  [22]=>
  array(1) {
    ["v"]=>
    string(29) "1992-09-20 11:30:00.123456789"
  }
  [23]=>
  array(1) {
    ["v"]=>
    string(23) "1992-09-20 11:30:00.123"
  }
  [24]=>
  array(1) {
    ["v"]=>
    string(19) "1992-09-20 11:30:00"
  }
  [25]=>
  array(1) {
    ["v"]=>
    string(29) "1992-09-20 09:30:00.123456+00"
  }
  [26]=>
  array(1) {
    ["v"]=>
    string(29) "1992-09-20 11:30:00.123456+00"
  }
  [27]=>
  array(1) {
    ["v"]=>
    string(22) "2001-02-17 03:38:40+00"
  }
}
array(3) {
  [0]=>
  array(1) {
    ["v2"]=>
    string(29) "1992-09-20 11:30:00.123456+00"
  }
  [1]=>
  array(1) {
    ["v2"]=>
    string(29) "1992-09-20 11:30:00.123456+00"
  }
  [2]=>
  array(1) {
    ["v2"]=>
    string(22) "2001-02-17 03:38:40+00"
  }
}
array(3) {
  [0]=>
  array(1) {
    ["v3"]=>
    string(29) "1992-09-20 11:30:00.123456+00"
  }
  [1]=>
  array(1) {
    ["v3"]=>
    string(29) "1992-09-20 11:30:00.123456+00"
  }
  [2]=>
  array(1) {
    ["v3"]=>
    string(22) "2001-02-17 03:38:40+00"
  }
}
array(3) {
  [0]=>
  array(1) {
    ["s"]=>
    array(1) {
      ["v"]=>
      string(5) "hello"
    }
  }
  [1]=>
  array(1) {
    ["s"]=>
    array(1) {
      ["v"]=>
      array(1) {
        [0]=>
        array(2) {
          ["key"]=>
          string(4) "key1"
          ["value"]=>
          int(10)
        }
      }
    }
  }
  [2]=>
  array(1) {
    ["s"]=>
    array(1) {
      ["v"]=>
      array(1) {
        ["key1"]=>
        string(6) "value1"
      }
    }
  }
}
