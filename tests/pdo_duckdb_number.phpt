--TEST--
PDO_duckdb: Test uuid
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$statement = $db->query("SELECT 0/0, 0//0"); // -nan, null
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 1/0, -1/0, 1//0, -1//0"); // inf, -inf, null, null
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("select 0.9::float, pi(), 9223372036854775808, -9223372036854775809");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

?>
--EXPECTF--
array(1) {
  [0]=>
  array(2) {
    ["(0 / 0)"]=>
    float(NAN)
    ["(0 // 0)"]=>
    NULL
  }
}
array(1) {
  [0]=>
  array(4) {
    ["(1 / 0)"]=>
    float(INF)
    ["(-1 / 0)"]=>
    float(-INF)
    ["(1 // 0)"]=>
    NULL
    ["(-1 // 0)"]=>
    NULL
  }
}
array(1) {
  [0]=>
  array(4) {
    ["CAST(0.9 AS FLOAT)"]=>
    float(0.9)
    ["pi()"]=>
    float(3.141592653589793)
    ["9223372036854775808"]=>
    string(19) "9223372036854775808"
    ["-9223372036854775809"]=>
    string(20) "-9223372036854775809"
  }
}
