--TEST--
PDO_duckdb: Test list
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$statement = $db->query("SELECT [1, 2, 3]");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT ['duck', 'goose', NULL, 'heron']");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT [['duck', 'goose', 'heron'], NULL, ['frog', 'toad'], []]");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT list_value(1, 2, 3)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT ['a', 'b', 'c'][1:2]");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT list_slice(['a', 'b', 'c'], 2, 3)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

?>
--EXPECTF--
array(1) {
  [0]=>
  array(1) {
    ["list_value(1, 2, 3)"]=>
    array(3) {
      [0]=>
      int(1)
      [1]=>
      int(2)
      [2]=>
      int(3)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["list_value('duck', 'goose', NULL, 'heron')"]=>
    array(4) {
      [0]=>
      string(4) "duck"
      [1]=>
      string(5) "goose"
      [2]=>
      NULL
      [3]=>
      string(5) "heron"
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["list_value(list_value('duck', 'goose', 'heron'), NULL, list_value('frog', 'toad'), list_value())"]=>
    array(4) {
      [0]=>
      array(3) {
        [0]=>
        string(4) "duck"
        [1]=>
        string(5) "goose"
        [2]=>
        string(5) "heron"
      }
      [1]=>
      NULL
      [2]=>
      array(2) {
        [0]=>
        string(4) "frog"
        [1]=>
        string(4) "toad"
      }
      [3]=>
      array(0) {
      }
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["list_value(1, 2, 3)"]=>
    array(3) {
      [0]=>
      int(1)
      [1]=>
      int(2)
      [2]=>
      int(3)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["list_value('a', 'b', 'c')[1:2]"]=>
    array(2) {
      [0]=>
      string(1) "a"
      [1]=>
      string(1) "b"
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["list_slice(list_value('a', 'b', 'c'), 2, 3)"]=>
    array(2) {
      [0]=>
      string(1) "b"
      [1]=>
      string(1) "c"
    }
  }
}
