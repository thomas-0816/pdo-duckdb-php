--TEST--
PDO_duckdb: Test bitstring
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$statement = $db->query("SELECT '101010'::BIT AS b, '1111'::BIT AS b2, NULL::BIT AS b3, '0'::BIT AS b5, '1'::BIT AS b6");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT BIT_COUNT('101010'::BIT) AS bitcount");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT ('10101010'::BIT || '11110000'::BIT) AS concat");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$db->exec("CREATE TABLE bit_table (id INTEGER, b BIT)");
$statement = $db->prepare("INSERT INTO bit_table VALUES (?, CAST(? AS BIT))");
$statement->bindValue(1, 1, PDO::PARAM_INT);
$statement->bindValue(2, '101010', PDO::PARAM_STR);
$statement->execute();
$statement = $db->prepare("INSERT INTO bit_table VALUES (?, CAST(? AS BIT))");
$statement->bindValue(1, 2, PDO::PARAM_INT);
$statement->bindValue(2, '110011', PDO::PARAM_STR);
$statement->execute();
$statement = $db->query("SELECT * FROM bit_table ORDER BY id");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

?>
--EXPECTF--
array(1) {
  [0]=>
  array(5) {
    ["b"]=>
    string(6) "101010"
    ["b2"]=>
    string(4) "1111"
    ["b3"]=>
    NULL
    ["b5"]=>
    string(1) "0"
    ["b6"]=>
    string(1) "1"
  }
}
array(1) {
  [0]=>
  array(1) {
    ["bitcount"]=>
    int(3)
  }
}
array(1) {
  [0]=>
  array(1) {
    ["concat"]=>
    string(16) "1010101011110000"
  }
}
array(2) {
  [0]=>
  array(2) {
    ["id"]=>
    int(1)
    ["b"]=>
    string(6) "101010"
  }
  [1]=>
  array(2) {
    ["id"]=>
    int(2)
    ["b"]=>
    string(6) "110011"
  }
}
