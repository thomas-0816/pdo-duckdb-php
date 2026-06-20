--TEST--
PDO_duckdb: Test map
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$statement = $db->query("SELECT MAP {'key1': 10, 'key2': 20, 'key3': 30}");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT map_from_entries([('key1', 10), ('key2', 20), ('key3', 30)])");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT MAP(['key1', 'key2', 'key3'], [10, 20, 30])");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT MAP {1: 42.001, 5: -32.1}");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT MAP {['a', 'b', 'c']: [1.1, 2.2, null], ['c', 'd', null]: [3.3, 4.4, 5.5]}");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT MAP {'key1': 5, 'key2': 43}['key1']");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT MAP {'key1': 5, 'key2': 43}['key3']");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT map_extract(MAP {'key1': 5, 'key2': 43}, 'key1')");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

?>
--EXPECTF--
array(1) {
  [0]=>
  array(1) {
    ["main."map"(main.list_value('key1', 'key2', 'key3'), main.list_value(10, 20, 30))"]=>
    array(3) {
      ["key1"]=>
      int(10)
      ["key2"]=>
      int(20)
      ["key3"]=>
      int(30)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["map_from_entries(main.list_value(main."row"('key1', 10), main."row"('key2', 20), main."row"('key3', 30)))"]=>
    array(3) {
      ["key1"]=>
      int(10)
      ["key2"]=>
      int(20)
      ["key3"]=>
      int(30)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    [""map"(main.list_value('key1', 'key2', 'key3'), main.list_value(10, 20, 30))"]=>
    array(3) {
      ["key1"]=>
      int(10)
      ["key2"]=>
      int(20)
      ["key3"]=>
      int(30)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["main."map"(main.list_value(1, 5), main.list_value(42.001, -32.1))"]=>
    array(2) {
      [1]=>
      float(42.001)
      [5]=>
      float(-32.1)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["main."map"(main.list_value(main.list_value('a', 'b', 'c'), main.list_value('c', 'd', NULL)), main.list_value(main.list_value(1.1, 2.2, NULL), main.list_value(3.3, 4.4, 5.5)))"]=>
    array(2) {
      ["a, b, c"]=>
      array(3) {
        [0]=>
        float(1.1)
        [1]=>
        float(2.2)
        [2]=>
        NULL
      }
      ["c, d, "]=>
      array(3) {
        [0]=>
        float(3.3)
        [1]=>
        float(4.4)
        [2]=>
        float(5.5)
      }
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["main."map"(main.list_value('key1', 'key2'), main.list_value(5, 43))['key1']"]=>
    int(5)
  }
}
array(1) {
  [0]=>
  array(1) {
    ["main."map"(main.list_value('key1', 'key2'), main.list_value(5, 43))['key3']"]=>
    NULL
  }
}
array(1) {
  [0]=>
  array(1) {
    ["map_extract(main."map"(main.list_value('key1', 'key2'), main.list_value(5, 43)), 'key1')"]=>
    array(1) {
      [0]=>
      int(5)
    }
  }
}
