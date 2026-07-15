--TEST--
PDO_duckdb: Test json
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$statement = $db->query('SELECT \'[1, null, {"key": "value"}]\'::JSON');
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

try {
    $statement = $db->query("SELECT 'unquoted'::JSON");
    var_dump($statement->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

$statement = $db->query('SELECT \'{  "a":5 }\'::JSON::VARCHAR');
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query('SELECT \'{"a":1,"a":2}\'::JSON');
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query('SELECT \'{"duck": 42}\'::JSON::STRUCT(duck INTEGER)');
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query('SELECT {duck: 42}::JSON');
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query('SELECT \'2023-05-12\'::DATE::JSON');
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

try {
    $db = new PDO('duckdb::memory:');
    $db->exec("SET errors_as_json = true; SELECT foo FROM bar");
} catch (Exception $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

?>
--EXPECTF--
array(1) {
  [0]=>
  array(1) {
    ["CAST('[1, null, {"key": "value"}]' AS "JSON")"]=>
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
}
Caught: SQLSTATE[HY000]: Conversion Error: Malformed JSON at byte 0 of input: unexpected character.  Input: "unquoted"

LINE 1: SELECT 'unquoted'::JSON
                         ^
array(1) {
  [0]=>
  array(1) {
    ["CAST(CAST('{  "a":5 }' AS "JSON") AS VARCHAR)"]=>
    string(10) "{  "a":5 }"
  }
}
array(1) {
  [0]=>
  array(1) {
    ["CAST('{"a":1,"a":2}' AS "JSON")"]=>
    array(1) {
      ["a"]=>
      int(2)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["CAST(CAST('{"duck": 42}' AS "JSON") AS STRUCT(duck INTEGER))"]=>
    array(1) {
      ["duck"]=>
      int(42)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["CAST(main.struct_pack(duck := 42) AS "JSON")"]=>
    array(1) {
      ["duck"]=>
      int(42)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["CAST(CAST('2023-05-12' AS DATE) AS "JSON")"]=>
    string(10) "2023-05-12"
  }
}
Caught: SQLSTATE[HY000]: {"exception_type":"Catalog","exception_message":"Table with name bar does not exist!\nDid you mean %s}
