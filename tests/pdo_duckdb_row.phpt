--TEST--
PDO_duckdb: Test row
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');

var_dump($db->query('SELECT row(), struct_pack(), typeof(row()), row()::STRUCT, row()::VARCHAR, NULL::STRUCT')->fetch(PDO::FETCH_ASSOC));
var_dump($db->query('SELECT row() = row(), row() IS NOT DISTINCT FROM row()')->fetch(PDO::FETCH_ASSOC));
var_dump($db->query("SELECT {'a': row(), 'b': 1}, [row(), row()], typeof({'a': row()})")->fetch(PDO::FETCH_ASSOC));

$db->exec('CREATE TABLE t(i INTEGER, s STRUCT)');
$db->exec('INSERT INTO t VALUES (1, row()), (2, NULL)');
var_dump($db->query('SELECT i, s FROM t ORDER BY i')->fetchAll(PDO::FETCH_ASSOC));
var_dump($db->query('SELECT i FROM t ORDER BY s, i')->fetchAll(PDO::FETCH_ASSOC));
var_dump($db->query('SELECT count(*) FROM t WHERE s IS NOT NULL')->fetch(PDO::FETCH_ASSOC));

var_dump($db->query('struct_concat(row(), row(1, 2))')->fetch(PDO::FETCH_ASSOC));
var_dump($db->query("SELECT struct_contains(row(), 'a'), struct_contains(row(), 5)")->fetch(PDO::FETCH_ASSOC));
var_dump($db->query("SELECT struct_concat(row(), row()), struct_concat(row(), {'x': 1}), struct_concat({'x': 1}, row())")->fetch(PDO::FETCH_ASSOC));

var_dump($db->query("SELECT struct_extract({'a': 1}, 'a')")->fetch(PDO::FETCH_ASSOC));

var_dump($db->query("SELECT unnest(row(1, 2))")->fetch(PDO::FETCH_ASSOC));
var_dump($db->query("SELECT unnest(struct_concat({'a': 1}, {'b': 1}))")->fetch(PDO::FETCH_ASSOC));

try {
  var_dump($db->query("SELECT struct_extract(row(), 'a')")->fetch(PDO::FETCH_ASSOC));
}
catch (Exception $e) {
  echo "Caught: " . $e->getMessage() . "\n";
}
try {
  var_dump($db->query("SELECT row()[1]")->fetch(PDO::FETCH_ASSOC));
}
catch (Exception $e) {
  echo "Caught: " . $e->getMessage() . "\n";
}
try {
  var_dump($db->query("SELECT unnest(row())")->fetch(PDO::FETCH_ASSOC));
}
catch (Exception $e) {
  echo "Caught: " . $e->getMessage() . "\n";
}

?>
--EXPECTF--
array(6) {
  [""row"()"]=>
  array(0) {
  }
  ["struct_pack()"]=>
  array(0) {
  }
  ["typeof("row"())"]=>
  string(5) "TUPLE"
  ["CAST("row"() AS STRUCT)"]=>
  array(0) {
  }
  ["CAST("row"() AS VARCHAR)"]=>
  string(2) "()"
  ["CAST(NULL AS STRUCT)"]=>
  NULL
}
array(2) {
  ["("row"() = "row"())"]=>
  bool(true)
  ["("row"() IS NOT DISTINCT FROM "row"())"]=>
  bool(true)
}
array(3) {
  ["struct_pack(a := "row"(), b := 1)"]=>
  array(2) {
    ["a"]=>
    array(0) {
    }
    ["b"]=>
    int(1)
  }
  ["list_value("row"(), "row"())"]=>
  array(2) {
    [0]=>
    array(0) {
    }
    [1]=>
    array(0) {
    }
  }
  ["typeof(struct_pack(a := "row"()))"]=>
  string(15) "STRUCT(a TUPLE)"
}
array(2) {
  [0]=>
  array(2) {
    ["i"]=>
    int(1)
    ["s"]=>
    array(0) {
    }
  }
  [1]=>
  array(2) {
    ["i"]=>
    int(2)
    ["s"]=>
    NULL
  }
}
array(2) {
  [0]=>
  array(1) {
    ["i"]=>
    int(1)
  }
  [1]=>
  array(1) {
    ["i"]=>
    int(2)
  }
}
array(1) {
  ["count_star()"]=>
  int(1)
}
array(1) {
  ["struct_concat("row"(), "row"(1, 2))"]=>
  array(2) {
    [0]=>
    int(1)
    [1]=>
    int(2)
  }
}
array(2) {
  ["struct_contains("row"(), 'a')"]=>
  bool(false)
  ["struct_contains("row"(), 5)"]=>
  bool(false)
}
array(3) {
  ["struct_concat("row"(), "row"())"]=>
  array(0) {
  }
  ["struct_concat("row"(), struct_pack(x := 1))"]=>
  array(1) {
    ["x"]=>
    int(1)
  }
  ["struct_concat(struct_pack(x := 1), "row"())"]=>
  array(1) {
    ["x"]=>
    int(1)
  }
}
array(1) {
  ["struct_extract(struct_pack(a := 1), 'a')"]=>
  int(1)
}
array(2) {
  ["element1"]=>
  int(1)
  ["element2"]=>
  int(2)
}
array(2) {
  ["a"]=>
  int(1)
  ["b"]=>
  int(1)
}
Caught: SQLSTATE[HY000]: Binder Error: Can't extract something from an empty struct
Caught: SQLSTATE[HY000]: Binder Error: Can't extract something from an empty struct
Caught: SQLSTATE[HY000]: Binder Error: UNNEST of an empty struct is not supported
