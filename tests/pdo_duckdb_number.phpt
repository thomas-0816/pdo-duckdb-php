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

$statement = $db->query("SELECT 1.5, .50, 2.");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 1e2, 6.02214e23, 1e-10");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 100_000_000, '0xFF_FF'::INTEGER, 1_2.1_2E0_1, '0b0_1_0_1'::INTEGER");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$db = new PDO('duckdb::memory:');
$db->exec("CREATE TABLE t (i INTEGER, ui UINTEGER, b BIGINT, b2 BIGINT, ub UBIGINT, h HUGEINT, u UHUGEINT)");
$statement = $db->prepare("INSERT INTO t VALUES (?, ?, ?, ?, ?, ?, ?)");
$statement->execute([1, 2, 9_223_372_036_854_775_806, -9_223_372_036_854_775_806, '18446744073709551614', '170141183460469231731687303715884105726', '340282366920938463463374607431768211455']);
$statement = $db->query("SELECT * FROM t", PDO::FETCH_ASSOC);
while ($row = $statement->fetch()) { var_dump($row); }

$statement = $db->query("SELECT '3402823669209384634633746074317682114571111'::bignum");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 1.23::DECIMAL(4,2), 0.001::DECIMAL(10,3), -3.14::DECIMAL(4,2), 0::DECIMAL(5,2)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 1.23::DECIMAL(13,3), 0.001::DECIMAL(14,4), -3.14::DECIMAL(15,5), 0::DECIMAL(16,6)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 999999999999999::DECIMAL(15,0), 0.99::DECIMAL(15,15)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 1000000000000000::DECIMAL(16,0), 0.9999999999999999::DECIMAL(16,16)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 0.311699786984687608::DECIMAL(18,18), 0.45971054587213542206239787480916869700::DECIMAL(38,38), -0.311699786984687608::DECIMAL(18,18)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT CAST(NULL AS DECIMAL(10,2))");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

// TODO v2 select ln(-1), sqrt(-1)

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
array(1) {
  [0]=>
  array(3) {
    ["1.5"]=>
    float(1.5)
    [".50"]=>
    float(0.5)
    [2]=>
    float(2)
  }
}
array(1) {
  [0]=>
  array(3) {
    ["100.0"]=>
    float(100)
    ["6.02214e+23"]=>
    float(6.02214E+23)
    ["1e-10"]=>
    float(1.0E-10)
  }
}
array(1) {
  [0]=>
  array(4) {
    [100000000]=>
    int(100000000)
    ["CAST('0xFF_FF' AS INTEGER)"]=>
    int(65535)
    ["121.2"]=>
    float(121.2)
    ["CAST('0b0_1_0_1' AS INTEGER)"]=>
    int(5)
  }
}
array(7) {
  ["i"]=>
  int(1)
  ["ui"]=>
  int(2)
  ["b"]=>
  int(9223372036854775806)
  ["b2"]=>
  int(-9223372036854775806)
  ["ub"]=>
  string(20) "18446744073709551614"
  ["h"]=>
  string(39) "170141183460469231731687303715884105726"
  ["u"]=>
  string(39) "340282366920938463463374607431768211455"
}
array(1) {
  [0]=>
  array(1) {
    ["CAST('3402823669209384634633746074317682114571111' AS "BIGNUM")"]=>
    string(43) "3402823669209384634633746074317682114571111"
  }
}
array(1) {
  [0]=>
  array(4) {
    ["CAST(1.23 AS DECIMAL(4, 2))"]=>
    float(1.23)
    ["CAST(0.001 AS DECIMAL(10, 3))"]=>
    float(0.001)
    ["-(CAST(3.14 AS DECIMAL(4, 2)))"]=>
    float(-3.14)
    ["CAST(0 AS DECIMAL(5, 2))"]=>
    float(0)
  }
}
array(1) {
  [0]=>
  array(4) {
    ["CAST(1.23 AS DECIMAL(13, 3))"]=>
    float(1.23)
    ["CAST(0.001 AS DECIMAL(14, 4))"]=>
    float(0.001)
    ["-(CAST(3.14 AS DECIMAL(15, 5)))"]=>
    float(-3.14)
    ["CAST(0 AS DECIMAL(16, 6))"]=>
    float(0)
  }
}
array(1) {
  [0]=>
  array(2) {
    ["CAST(999999999999999 AS DECIMAL(15, 0))"]=>
    float(999999999999999)
    ["CAST(0.99 AS DECIMAL(15, 15))"]=>
    float(0.99)
  }
}
array(1) {
  [0]=>
  array(2) {
    ["CAST(1000000000000000 AS DECIMAL(16, 0))"]=>
    float(1000000000000000)
    ["CAST(0.9999999999999999 AS DECIMAL(16, 16))"]=>
    string(17) ".9999999999999999"
  }
}
array(1) {
  [0]=>
  array(3) {
    ["CAST(0.311699786984687608 AS DECIMAL(18, 18))"]=>
    string(19) ".311699786984687608"
    ["CAST(0.45971054587213545 AS DECIMAL(38, 38))"]=>
    string(39) ".45971054587213539913392807288705646592"
    ["-(CAST(0.311699786984687608 AS DECIMAL(18, 18)))"]=>
    string(20) "-.311699786984687608"
  }
}
array(1) {
  [0]=>
  array(1) {
    ["CAST(NULL AS DECIMAL(10, 2))"]=>
    NULL
  }
}
