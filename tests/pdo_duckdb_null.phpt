--TEST--
PDO_duckdb: Test list
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');

$statement = $db->query("SELECT
  null::boolean, null::tinyint, null::smallint, null::integer, null::bigint, null::utinyint, null::usmallint,
  null::uinteger, null::ubigint, null::float, null::double, null::timestamp, null::date, null::time, null::interval,
  null::hugeint, null::uhugeint, null::varchar, null::blob, null::decimal, null::timestamp_s, null::timestamp_ms,
  null::timestamp_ns, null::enum('a'), null::struct(duck integer), null::integer[], null::integer[1], map_from_entries(null),
  null::union(str varchar), null::uuid, null::bit, null::timetz, null::timestamptz, null::time_ns, null::geometry,
  null::variant, null::bignum
");
$columnCount = $statement->columnCount();
for ($i = 0; $i < $columnCount; $i++) {
  var_dump($statement->getColumnMeta($i)['native_type']);
}
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

/*
// TODO v2
SELECT null::variant, null::bignum
SELECT row(), struct_pack()
SELECT typeof(row())
SELECT typeof(NULL::STRUCT)
SELECT NULL::STRUCT()
SELECT NULL::STRUCT, row()::STRUCT
CREATE TABLE t(i INTEGER, s STRUCT)
INSERT INTO t VALUES (1, row()), (2, NULL), (3, row())
SELECT i, s FROM t ORDER BY i
SELECT count(*) FROM t WHERE s IS NOT NULL
SELECT i FROM t ORDER BY s, i
SELECT s, count(*) FROM t WHERE s IS NOT NULL GROUP BY s
SELECT count(*) FROM (SELECT DISTINCT s FROM t)
SELECT row() = row(), row() IS NOT DISTINCT FROM row()
SELECT t1.i, t2.i, t1.s FROM t t1 JOIN t t2 USING (s) WHERE t1.s IS NOT NULL AND t1.i < t2.i ORDER BY 1, 2
SELECT {'a': row(), 'b': 1}, [row(), row()], typeof({'a': row()})
SELECT row()::VARCHAR
SELECT struct_concat(row(), row()), struct_concat(row(), {'x': 1}), struct_concat({'x': 1}, row())
SELECT struct_concat(row(), row(1, 2))
SELECT struct_contains(row(), 'a'), struct_contains(row(), 5)
SELECT struct_extract(row(), 'a')
SELECT row()[1]
SELECT unnest(row())
SELECT i, s FROM t ORDER BY i
*/

?>
--EXPECTF--
string(7) "boolean"
string(7) "tinyint"
string(8) "smallint"
string(7) "integer"
string(6) "bigint"
string(8) "utinyint"
string(9) "usmallint"
string(8) "uinteger"
string(7) "ubigint"
string(5) "float"
string(6) "double"
string(9) "timestamp"
string(4) "date"
string(4) "time"
string(8) "interval"
string(7) "hugeint"
string(8) "uhugeint"
string(7) "varchar"
string(4) "blob"
string(7) "decimal"
string(11) "timestamp_s"
string(12) "timestamp_ms"
string(12) "timestamp_ns"
string(4) "enum"
string(6) "struct"
string(4) "list"
string(7) "unknown"
string(7) "integer"
string(5) "union"
string(4) "uuid"
string(3) "bit"
string(6) "timetz"
string(11) "timestamptz"
string(7) "time_ns"
string(8) "geometry"
string(4) "json"
string(6) "bignum"
array(1) {
  [0]=>
  array(37) {
    ["CAST(NULL AS BOOLEAN)"]=>
    NULL
    ["CAST(NULL AS "TINYINT")"]=>
    NULL
    ["CAST(NULL AS SMALLINT)"]=>
    NULL
    ["CAST(NULL AS INTEGER)"]=>
    NULL
    ["CAST(NULL AS BIGINT)"]=>
    NULL
    ["CAST(NULL AS "UTINYINT")"]=>
    NULL
    ["CAST(NULL AS "USMALLINT")"]=>
    NULL
    ["CAST(NULL AS "UINTEGER")"]=>
    NULL
    ["CAST(NULL AS "UBIGINT")"]=>
    NULL
    ["CAST(NULL AS FLOAT)"]=>
    NULL
    ["CAST(NULL AS "DOUBLE")"]=>
    NULL
    ["CAST(NULL AS TIMESTAMP)"]=>
    NULL
    ["CAST(NULL AS "DATE")"]=>
    NULL
    ["CAST(NULL AS TIME)"]=>
    NULL
    ["CAST(NULL AS INTERVAL)"]=>
    NULL
    ["CAST(NULL AS "HUGEINT")"]=>
    NULL
    ["CAST(NULL AS "UHUGEINT")"]=>
    NULL
    ["CAST(NULL AS VARCHAR)"]=>
    NULL
    ["CAST(NULL AS "BLOB")"]=>
    NULL
    ["CAST(NULL AS DECIMAL)"]=>
    NULL
    ["CAST(NULL AS "TIMESTAMP_S")"]=>
    NULL
    ["CAST(NULL AS "TIMESTAMP_MS")"]=>
    NULL
    ["CAST(NULL AS "TIMESTAMP_NS")"]=>
    NULL
    ["CAST(NULL AS "ENUM"('a'))"]=>
    NULL
    ["CAST(NULL AS STRUCT(duck INTEGER))"]=>
    NULL
    ["CAST(NULL AS INTEGER[])"]=>
    NULL
    ["CAST(NULL AS INTEGER[1])"]=>
    NULL
    ["map_from_entries(NULL)"]=>
    NULL
    ["CAST(NULL AS UNION(str VARCHAR))"]=>
    NULL
    ["CAST(NULL AS "UUID")"]=>
    NULL
    ["CAST(NULL AS BIT(1))"]=>
    NULL
    ["CAST(NULL AS "TIME WITH TIME ZONE")"]=>
    NULL
    ["CAST(NULL AS "TIMESTAMP WITH TIME ZONE")"]=>
    NULL
    ["CAST(NULL AS "TIME_NS")"]=>
    NULL
    ["CAST(NULL AS "GEOMETRY")"]=>
    NULL
    ["CAST(NULL AS "VARIANT")"]=>
    NULL
    ["CAST(NULL AS "BIGNUM")"]=>
    NULL
  }
}
