--TEST--
PDO_duckdb: Test bitstring
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE geometries (id INTEGER, geom GEOMETRY)");
$db->exec("INSERT INTO geometries VALUES
  (1, 'POINT (30 10)'),
  (2, 'LINESTRING (30 10, 10 30, 40 40)'),
  (3, 'POLYGON ((30 10, 40 40, 20 40, 10 20, 30 10))'),
  (4, 'MULTIPOINT ((10 40), (40 30), (20 20), (30 10))'),
  (5, 'MULTILINESTRING ((10 10, 20 20, 10 40), (40 40, 30 30, 40 20))'),
  (6, 'MULTIPOLYGON (((30 20, 45 40, 10 40, 30 20)), ((15 5, 40 10, 10 20, 5 10,15 5)))'),
  (7, 'GEOMETRYCOLLECTION (POINT(40 10), LINESTRING(10 10,20 20,10 40), POLYGON((40 40,20 45,45 30,40 40)))')");
$statement = $db->query("SELECT geom FROM geometries");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

?>
--EXPECTF--
array(7) {
  [0]=>
  array(1) {
    ["geom"]=>
    string(13) "POINT (30 10)"
  }
  [1]=>
  array(1) {
    ["geom"]=>
    string(32) "LINESTRING (30 10, 10 30, 40 40)"
  }
  [2]=>
  array(1) {
    ["geom"]=>
    string(45) "POLYGON ((30 10, 40 40, 20 40, 10 20, 30 10))"
  }
  [3]=>
  array(1) {
    ["geom"]=>
    string(39) "MULTIPOINT (10 40, 40 30, 20 20, 30 10)"
  }
  [4]=>
  array(1) {
    ["geom"]=>
    string(62) "MULTILINESTRING ((10 10, 20 20, 10 40), (40 40, 30 30, 40 20))"
  }
  [5]=>
  array(1) {
    ["geom"]=>
    string(81) "MULTIPOLYGON (((30 20, 45 40, 10 40, 30 20)), ((15 5, 40 10, 10 20, 5 10, 15 5)))"
  }
  [6]=>
  array(1) {
    ["geom"]=>
    string(108) "GEOMETRYCOLLECTION (POINT (40 10), LINESTRING (10 10, 20 20, 10 40), POLYGON ((40 40, 20 45, 45 30, 40 40)))"
  }
}
