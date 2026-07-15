--TEST--
PDO_duckdb: Test interval
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$statement = $db->query("SELECT INTERVAL 1 YEAR, INTERVAL (4 * 10) YEAR, INTERVAL '1 month 1 day', '16 months'::INTERVAL, '48:00:00'::INTERVAL");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT INTERVAL '1.5' YEARS");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT DATE '2000-01-01' + INTERVAL (i) MONTH as i FROM range(2) t(i)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT datepart('decade', INTERVAL 12 YEARS), datepart('year', INTERVAL 12 YEARS), datepart('second', INTERVAL 1_234 MILLISECONDS), datepart('microsecond', INTERVAL 1_234 MILLISECONDS)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT DATE '2000-01-01' + INTERVAL 1 YEAR,
    TIMESTAMP '2000-01-01 01:33:30' - INTERVAL '1 month 13 hours',
    TIME '02:00:00' - INTERVAL '3 days 23 hours'");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT
    TIMESTAMP '2000-02-06 12:00:00' - TIMESTAMP '2000-01-01 11:00:00',
    TIMESTAMP '2000-02-01' + (TIMESTAMP '2000-02-01' - TIMESTAMP '2000-01-01'),
");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

?>
--EXPECTF--
array(1) {
  [0]=>
  array(5) {
    ["to_years(CAST(trunc(CAST(1 AS DOUBLE)) AS INTEGER))"]=>
    string(6) "1 year"
    ["to_years(CAST(trunc(CAST((4 * 10) AS DOUBLE)) AS INTEGER))"]=>
    string(8) "40 years"
    ["CAST('1 month 1 day' AS INTERVAL)"]=>
    string(13) "1 month 1 day"
    ["CAST('16 months' AS INTERVAL)"]=>
    string(15) "1 year 4 months"
    ["CAST('48:00:00' AS INTERVAL)"]=>
    string(8) "48:00:00"
  }
}
array(1) {
  [0]=>
  array(1) {
    ["to_years(CAST(trunc(CAST('1.5' AS DOUBLE)) AS INTEGER))"]=>
    string(6) "1 year"
  }
}
array(2) {
  [0]=>
  array(1) {
    ["i"]=>
    string(19) "2000-01-01 00:00:00"
  }
  [1]=>
  array(1) {
    ["i"]=>
    string(19) "2000-02-01 00:00:00"
  }
}
array(1) {
  [0]=>
  array(4) {
    ["datepart('decade', to_years(CAST(trunc(CAST(12 AS DOUBLE)) AS INTEGER)))"]=>
    int(1)
    ["datepart('year', to_years(CAST(trunc(CAST(12 AS DOUBLE)) AS INTEGER)))"]=>
    int(12)
    ["datepart('second', to_milliseconds(CAST(1234 AS DOUBLE)))"]=>
    int(1)
    ["datepart('microsecond', to_milliseconds(CAST(1234 AS DOUBLE)))"]=>
    int(1234000)
  }
}
array(1) {
  [0]=>
  array(3) {
    ["(CAST('2000-01-01' AS DATE) + to_years(CAST(trunc(CAST(1 AS DOUBLE)) AS INTEGER)))"]=>
    string(19) "2001-01-01 00:00:00"
    ["(CAST('2000-01-01 01:33:30' AS TIMESTAMP) - CAST('1 month 13 hours' AS INTERVAL))"]=>
    string(19) "1999-11-30 12:33:30"
    ["(CAST('02:00:00' AS TIME) - CAST('3 days 23 hours' AS INTERVAL))"]=>
    string(8) "03:00:00"
  }
}
array(1) {
  [0]=>
  array(2) {
    ["(CAST('2000-02-06 12:00:00' AS TIMESTAMP) - CAST('2000-01-01 11:00:00' AS TIMESTAMP))"]=>
    string(16) "36 days 01:00:00"
    ["(CAST('2000-02-01' AS TIMESTAMP) + (CAST('2000-02-01' AS TIMESTAMP) - CAST('2000-01-01' AS TIMESTAMP)))"]=>
    string(19) "2000-03-03 00:00:00"
  }
}
