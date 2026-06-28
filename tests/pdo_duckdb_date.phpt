--TEST--
PDO_duckdb: Test date
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$db->query("INSTALL icu;");

$db = new PDO('duckdb::memory:', null, null, [PDO::DUCKDB_ATTR_CONFIG => ['timezone' => 'Europe/Berlin']]);

$statement = $db->query("SELECT '-infinity'::DATE AS negative, 'epoch'::DATE AS epoch, 'infinity'::DATE AS positive");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("select '2969-01-01'::date, '0001-01-01'::date");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("select '2969-01-01'::datetime, '0001-01-01'::datetime");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIMESTAMP '1992-09-20 11:30:00.123456789', TIMESTAMP '1992-09-20 11:30:00'");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIMESTAMP_NS '1992-09-20 11:30:00.123456789', TIMESTAMP_MS '1992-09-20 11:30:00.123456789', TIMESTAMP_S '1992-09-20 11:30:00.123456789'");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIMESTAMPTZ '1992-09-20 11:30:00.123456789', TIMESTAMPTZ '1992-09-20 12:30:00.123456789+01:00', timezone('America/Denver', TIMESTAMP '2001-02-16 20:38:40')");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT '-infinity'::TIMESTAMP, 'epoch'::TIMESTAMP, 'infinity'::TIMESTAMP");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIME '1992-09-20 11:30:00.123456', TIME '1992-09-20 11:30:00'");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIMETZ '1992-09-20 11:30:00.123456'");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIMETZ '1992-09-20 11:30:00.123456-02:00'");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT TIMETZ '1992-09-20 11:30:00.123456+05:30'");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT '15:30:00.123456789'::TIME_NS");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

?>
--EXPECTF--
array(1) {
  [0]=>
  array(3) {
    ["negative"]=>
    string(9) "-infinity"
    ["epoch"]=>
    string(10) "1970-01-01"
    ["positive"]=>
    string(8) "infinity"
  }
}
array(1) {
  [0]=>
  array(2) {
    ["CAST('2969-01-01' AS "DATE")"]=>
    string(10) "2969-01-01"
    ["CAST('0001-01-01' AS "DATE")"]=>
    string(10) "0001-01-01"
  }
}
array(1) {
  [0]=>
  array(2) {
    ["CAST('2969-01-01' AS TIMESTAMP)"]=>
    string(19) "2969-01-01 00:00:00"
    ["CAST('0001-01-01' AS TIMESTAMP)"]=>
    string(19) "0001-01-01 00:00:00"
  }
}
array(1) {
  [0]=>
  array(2) {
    ["CAST('1992-09-20 11:30:00.123456789' AS TIMESTAMP)"]=>
    string(26) "1992-09-20 11:30:00.123456"
    ["CAST('1992-09-20 11:30:00' AS TIMESTAMP)"]=>
    string(19) "1992-09-20 11:30:00"
  }
}
array(1) {
  [0]=>
  array(3) {
    ["CAST('1992-09-20 11:30:00.123456789' AS "TIMESTAMP_NS")"]=>
    string(29) "1992-09-20 11:30:00.123456789"
    ["CAST('1992-09-20 11:30:00.123456789' AS "TIMESTAMP_MS")"]=>
    string(23) "1992-09-20 11:30:00.123"
    ["CAST('1992-09-20 11:30:00.123456789' AS "TIMESTAMP_S")"]=>
    string(19) "1992-09-20 11:30:00"
  }
}
array(1) {
  [0]=>
  array(3) {
    ["CAST('1992-09-20 11:30:00.123456789' AS "TIMESTAMP WITH TIME ZONE")"]=>
    string(32) "1992-09-20 11:30:00.123456+02:00"
    ["CAST('1992-09-20 12:30:00.123456789+01:00' AS "TIMESTAMP WITH TIME ZONE")"]=>
    string(32) "1992-09-20 13:30:00.123456+02:00"
    ["timezone('America/Denver', CAST('2001-02-16 20:38:40' AS TIMESTAMP))"]=>
    string(25) "2001-02-17 04:38:40+01:00"
  }
}
array(1) {
  [0]=>
  array(3) {
    ["CAST('-infinity' AS TIMESTAMP)"]=>
    string(9) "-infinity"
    ["CAST('epoch' AS TIMESTAMP)"]=>
    string(19) "1970-01-01 00:00:00"
    ["CAST('infinity' AS TIMESTAMP)"]=>
    string(8) "infinity"
  }
}
array(1) {
  [0]=>
  array(2) {
    ["CAST('1992-09-20 11:30:00.123456' AS TIME)"]=>
    string(15) "11:30:00.123456"
    ["CAST('1992-09-20 11:30:00' AS TIME)"]=>
    string(8) "11:30:00"
  }
}
array(1) {
  [0]=>
  array(1) {
    ["CAST('1992-09-20 11:30:00.123456' AS "TIME WITH TIME ZONE")"]=>
    string(21) "11:30:00.123456+02:00"
  }
}
array(1) {
  [0]=>
  array(1) {
    ["CAST('1992-09-20 11:30:00.123456-02:00' AS "TIME WITH TIME ZONE")"]=>
    string(21) "13:30:00.123456+02:00"
  }
}
array(1) {
  [0]=>
  array(1) {
    ["CAST('1992-09-20 11:30:00.123456+05:30' AS "TIME WITH TIME ZONE")"]=>
    string(21) "06:00:00.123456+02:00"
  }
}
array(1) {
  [0]=>
  array(1) {
    ["CAST('15:30:00.123456789' AS "TIME_NS")"]=>
    string(18) "15:30:00.123456789"
  }
}
