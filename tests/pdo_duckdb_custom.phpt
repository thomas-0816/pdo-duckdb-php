--TEST--
PDO_duckdb: Test custom types (e.g. inet extension)
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$db->exec('INSTALL inet; LOAD inet;');

$statement = $db->query("SELECT '127.0.0.1'::INET, '2001:db8:3c4d::/48'::INET, '10.0.0.0/8'::INET, host('192.168.1.5'::INET), family('2001:db8::1'::INET),
  ('192.168.0.0/24'::INET <<= '192.168.0.0/16'::INET) AS contains");
var_dump($statement->fetch(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT '127.0.0.1'::INET, '2001:db8:3c4d::/48'::INET");
var_dump($statement->getColumnMeta(0));
var_dump($statement->getColumnMeta(1));

$statement = $db->query("SELECT NULL::INET");
var_dump($statement->fetch(PDO::FETCH_ASSOC));
var_dump($statement->getColumnMeta(0));

?>
--EXPECT--
array(6) {
  ["CAST('127.0.0.1' AS "INET")"]=>
  string(9) "127.0.0.1"
  ["CAST('2001:db8:3c4d::/48' AS "INET")"]=>
  string(18) "2001:db8:3c4d::/48"
  ["CAST('10.0.0.0/8' AS "INET")"]=>
  string(10) "10.0.0.0/8"
  ["host(CAST('192.168.1.5' AS "INET"))"]=>
  string(11) "192.168.1.5"
  [""family"(CAST('2001:db8::1' AS "INET"))"]=>
  int(6)
  ["contains"]=>
  bool(true)
}
array(7) {
  ["native_type"]=>
  string(7) "varchar"
  ["pdo_type"]=>
  int(2)
  ["duckdb:decl_type"]=>
  string(4) "INET"
  ["flags"]=>
  array(0) {
  }
  ["name"]=>
  string(27) "CAST('127.0.0.1' AS "INET")"
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
  string(4) "INET"
  ["flags"]=>
  array(0) {
  }
  ["name"]=>
  string(36) "CAST('2001:db8:3c4d::/48' AS "INET")"
  ["len"]=>
  int(0)
  ["precision"]=>
  int(0)
}
array(1) {
  ["CAST(NULL AS "INET")"]=>
  NULL
}
array(7) {
  ["native_type"]=>
  string(7) "varchar"
  ["pdo_type"]=>
  int(2)
  ["duckdb:decl_type"]=>
  string(4) "INET"
  ["flags"]=>
  array(0) {
  }
  ["name"]=>
  string(20) "CAST(NULL AS "INET")"
  ["len"]=>
  int(0)
  ["precision"]=>
  int(0)
}
