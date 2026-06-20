--TEST--
PDO_duckdb: Test cast
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');

$statement = $db->query("SELECT CAST(42.5 AS VARCHAR), CAST(3.1 AS INTEGER)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT CAST(0.9 AS FLOAT), CAST(3.1 AS FLOAT), CAST([1, 2, 3] AS VARCHAR[])");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT CAST({'a': 42} AS STRUCT(a VARCHAR)), CAST({'a': 42} AS STRUCT(a VARCHAR, b VARCHAR))");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT CAST({'a': 42, 'b': 43} AS STRUCT(a VARCHAR)), CAST({'a': 42, 'b': 84} AS STRUCT(b VARCHAR, a VARCHAR))");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT [{'a': 42}, {'b': 84}]");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("FROM (SELECT {'outer1': {'inner1': 42, 'inner2': 42}} AS c
    UNION SELECT {'outer1': {'inner2': 'hello', 'inner3': 'world'}, 'outer2': '100'} AS c) a
    order by a.c.outer1.inner2");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT CAST(999 AS TINYINT)");
var_dump($statement);

?>
--EXPECTF--
array(1) {
  [0]=>
  array(2) {
    ["CAST(42.5 AS VARCHAR)"]=>
    string(4) "42.5"
    ["CAST(3.1 AS INTEGER)"]=>
    int(3)
  }
}
array(1) {
  [0]=>
  array(3) {
    ["CAST(0.9 AS FLOAT)"]=>
    float(0.9)
    ["CAST(3.1 AS FLOAT)"]=>
    float(3.1)
    ["CAST(main.list_value(1, 2, 3) AS VARCHAR[])"]=>
    array(3) {
      [0]=>
      string(1) "1"
      [1]=>
      string(1) "2"
      [2]=>
      string(1) "3"
    }
  }
}
array(1) {
  [0]=>
  array(2) {
    ["CAST(main.struct_pack(a := 42) AS STRUCT(a VARCHAR))"]=>
    array(1) {
      ["a"]=>
      string(2) "42"
    }
    ["CAST(main.struct_pack(a := 42) AS STRUCT(a VARCHAR, b VARCHAR))"]=>
    array(2) {
      ["a"]=>
      string(2) "42"
      ["b"]=>
      NULL
    }
  }
}
array(1) {
  [0]=>
  array(2) {
    ["CAST(main.struct_pack(a := 42, b := 43) AS STRUCT(a VARCHAR))"]=>
    array(1) {
      ["a"]=>
      string(2) "42"
    }
    ["CAST(main.struct_pack(a := 42, b := 84) AS STRUCT(b VARCHAR, a VARCHAR))"]=>
    array(2) {
      ["b"]=>
      string(2) "84"
      ["a"]=>
      string(2) "42"
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["main.list_value(main.struct_pack(a := 42), main.struct_pack(b := 84))"]=>
    array(2) {
      [0]=>
      array(2) {
        ["a"]=>
        int(42)
        ["b"]=>
        NULL
      }
      [1]=>
      array(2) {
        ["a"]=>
        NULL
        ["b"]=>
        int(84)
      }
    }
  }
}
array(2) {
  [0]=>
  array(1) {
    ["c"]=>
    array(2) {
      ["outer1"]=>
      array(3) {
        ["inner1"]=>
        int(42)
        ["inner2"]=>
        string(2) "42"
        ["inner3"]=>
        NULL
      }
      ["outer2"]=>
      NULL
    }
  }
  [1]=>
  array(1) {
    ["c"]=>
    array(2) {
      ["outer1"]=>
      array(3) {
        ["inner1"]=>
        NULL
        ["inner2"]=>
        string(5) "hello"
        ["inner3"]=>
        string(5) "world"
      }
      ["outer2"]=>
      string(3) "100"
    }
  }
}

Fatal error: Uncaught PDOException: SQLSTATE[HY000]: Conversion Error: Type INT32 with value 999 can't be cast because the value is out of range for the destination type INT8

LINE 1: SELECT CAST(999 AS TINYINT)
               ^ in /home/tb/code/pdo-duckdb2/pdo-duckdb/tests/pdo_duckdb_cast.php:25
Stack trace:
#0 /home/tb/code/pdo-duckdb2/pdo-duckdb/tests/pdo_duckdb_cast.php(25): PDO->query('SELECT CAST(999...')
#1 {main}
  thrown in /home/tb/code/pdo-duckdb2/pdo-duckdb/tests/pdo_duckdb_cast.php on line 25
