--TEST--
PDO_duckdb: Test struct
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');

$tmpFile = sys_get_temp_dir() . '/test_logs.json';
$parquetFile = sys_get_temp_dir() . '/test_logs.parquet';
$parquetFile2 = sys_get_temp_dir() . '/test_logs2.parquet';

file_put_contents($tmpFile, json_encode(['date' => '2026-01-02 03:04:05', 'log' => 'log text']) . PHP_EOL);
file_put_contents($tmpFile, json_encode(['date' => '2026-02-03 04:05:06', 'log' => 'log text 2']) . PHP_EOL, FILE_APPEND);

// limit threads and memory usage for converting big files, see https://github.com/duckdb/duckdb/issues/16078
// 100k rows per group
$db->exec("
    set memory_limit='4GB';
    set threads = 1;
    SET preserve_insertion_order=false;
    copy (select * from read_ndjson('{$tmpFile}', ignore_errors=true))
    to '{$parquetFile}' (FORMAT 'parquet', COMPRESSION 'zstd', ROW_GROUP_SIZE 100_000)
");
// 100M bytes per group
$db->exec("
    set memory_limit='4GB';
    set threads = 1;
    SET preserve_insertion_order=false;
    copy (select * from read_ndjson('{$tmpFile}', ignore_errors=true))
    to '{$parquetFile2}' (FORMAT 'parquet', COMPRESSION 'zstd', ROW_GROUP_SIZE_BYTES 100_000_000)
");
$statement = $db->query("SELECT * FROM parquet_schema('{$parquetFile2}')");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));
$statement = $db->query("SELECT * FROM parquet_metadata('{$parquetFile2}')");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));
$statement = $db->query("SELECT * FROM parquet_file_metadata('{$parquetFile2}')");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT value FROM duckdb_settings() WHERE name IN ('threads', 'memory_limit')");
var_dump($statement->fetchAll(PDO::FETCH_COLUMN));

?>
--EXPECTF--
array(3) {
  [0]=>
  array(13) {
    ["file_name"]=>
    string(%d) "%s/test_logs2.parquet"
    ["name"]=>
    string(13) "duckdb_schema"
    ["type"]=>
    NULL
    ["type_length"]=>
    NULL
    ["repetition_type"]=>
    string(8) "REQUIRED"
    ["num_children"]=>
    int(2)
    ["converted_type"]=>
    NULL
    ["scale"]=>
    NULL
    ["precision"]=>
    NULL
    ["field_id"]=>
    NULL
    ["logical_type"]=>
    NULL
    ["duckdb_type"]=>
    NULL
    ["column_id"]=>
    int(0)
  }
  [1]=>
  array(13) {
    ["file_name"]=>
    string(%d) "%s/test_logs2.parquet"
    ["name"]=>
    string(4) "date"
    ["type"]=>
    string(5) "INT64"
    ["type_length"]=>
    NULL
    ["repetition_type"]=>
    string(8) "OPTIONAL"
    ["num_children"]=>
    NULL
    ["converted_type"]=>
    string(16) "TIMESTAMP_MICROS"
    ["scale"]=>
    NULL
    ["precision"]=>
    NULL
    ["field_id"]=>
    NULL
    ["logical_type"]=>
    string(99) "TimestampType(isAdjustedToUTC=0, unit=TimeUnit(MILLIS=<null>, MICROS=MicroSeconds(), NANOS=<null>))"
    ["duckdb_type"]=>
    string(9) "TIMESTAMP"
    ["column_id"]=>
    int(1)
  }
  [2]=>
  array(13) {
    ["file_name"]=>
    string(%d) "%s/test_logs2.parquet"
    ["name"]=>
    string(3) "log"
    ["type"]=>
    string(10) "BYTE_ARRAY"
    ["type_length"]=>
    NULL
    ["repetition_type"]=>
    string(8) "OPTIONAL"
    ["num_children"]=>
    NULL
    ["converted_type"]=>
    string(4) "UTF8"
    ["scale"]=>
    NULL
    ["precision"]=>
    NULL
    ["field_id"]=>
    NULL
    ["logical_type"]=>
    NULL
    ["duckdb_type"]=>
    string(7) "VARCHAR"
    ["column_id"]=>
    int(2)
  }
}
array(2) {
  [0]=>
  array(31) {
    ["file_name"]=>
    string(%d) "%s/test_logs2.parquet"
    ["row_group_id"]=>
    int(0)
    ["row_group_num_rows"]=>
    int(2)
    ["row_group_num_columns"]=>
    int(2)
    ["row_group_bytes"]=>
    int(88)
    ["column_id"]=>
    int(0)
    ["file_offset"]=>
    int(0)
    ["num_values"]=>
    int(2)
    ["path_in_schema"]=>
    string(4) "date"
    ["type"]=>
    string(5) "INT64"
    ["stats_min"]=>
    string(19) "2026-01-02 03:04:05"
    ["stats_max"]=>
    string(19) "2026-02-03 04:05:06"
    ["stats_null_count"]=>
    int(0)
    ["stats_distinct_count"]=>
    NULL
    ["stats_min_value"]=>
    string(19) "2026-01-02 03:04:05"
    ["stats_max_value"]=>
    string(19) "2026-02-03 04:05:06"
    ["compression"]=>
    string(4) "ZSTD"
    ["encodings"]=>
    string(5) "PLAIN"
    ["index_page_offset"]=>
    NULL
    ["dictionary_page_offset"]=>
    NULL
    ["data_page_offset"]=>
    int(4)
    ["total_compressed_size"]=>
    int(48)
    ["total_uncompressed_size"]=>
    int(39)
    ["key_value_metadata"]=>
    array(0) {
    }
    ["bloom_filter_offset"]=>
    NULL
    ["bloom_filter_length"]=>
    NULL
    ["min_is_exact"]=>
    bool(true)
    ["max_is_exact"]=>
    bool(true)
    ["row_group_compressed_bytes"]=>
    int(101)
    ["geo_bbox"]=>
    NULL
    ["geo_types"]=>
    NULL
  }
  [1]=>
  array(31) {
    ["file_name"]=>
    string(%d) "%s/test_logs2.parquet"
    ["row_group_id"]=>
    int(0)
    ["row_group_num_rows"]=>
    int(2)
    ["row_group_num_columns"]=>
    int(2)
    ["row_group_bytes"]=>
    int(88)
    ["column_id"]=>
    int(1)
    ["file_offset"]=>
    int(0)
    ["num_values"]=>
    int(2)
    ["path_in_schema"]=>
    string(3) "log"
    ["type"]=>
    string(10) "BYTE_ARRAY"
    ["stats_min"]=>
    string(8) "log text"
    ["stats_max"]=>
    string(10) "log text 2"
    ["stats_null_count"]=>
    int(0)
    ["stats_distinct_count"]=>
    NULL
    ["stats_min_value"]=>
    string(8) "log text"
    ["stats_max_value"]=>
    string(10) "log text 2"
    ["compression"]=>
    string(4) "ZSTD"
    ["encodings"]=>
    string(5) "PLAIN"
    ["index_page_offset"]=>
    NULL
    ["dictionary_page_offset"]=>
    NULL
    ["data_page_offset"]=>
    int(52)
    ["total_compressed_size"]=>
    int(53)
    ["total_uncompressed_size"]=>
    int(49)
    ["key_value_metadata"]=>
    array(0) {
    }
    ["bloom_filter_offset"]=>
    NULL
    ["bloom_filter_length"]=>
    NULL
    ["min_is_exact"]=>
    bool(true)
    ["max_is_exact"]=>
    bool(true)
    ["row_group_compressed_bytes"]=>
    int(101)
    ["geo_bbox"]=>
    NULL
    ["geo_types"]=>
    NULL
  }
}
array(1) {
  [0]=>
  array(10) {
    ["file_name"]=>
    string(%d) "%s/test_logs2.parquet"
    ["created_by"]=>
    string(40) "DuckDB version v1.5.5 (build d8cdaa33fd)"
    ["num_rows"]=>
    int(2)
    ["num_row_groups"]=>
    int(1)
    ["format_version"]=>
    int(1)
    ["encryption_algorithm"]=>
    NULL
    ["footer_signing_key_metadata"]=>
    NULL
    ["file_size_bytes"]=>
    int(388)
    ["footer_size"]=>
    int(275)
    ["column_orders"]=>
    array(2) {
      [0]=>
      string(42) "ColumnOrder(TYPE_ORDER=TypeDefinedOrder())"
      [1]=>
      string(42) "ColumnOrder(TYPE_ORDER=TypeDefinedOrder())"
    }
  }
}
array(2) {
  [0]=>
  string(7) "3.7 GiB"
  [1]=>
  string(1) "1"
}
