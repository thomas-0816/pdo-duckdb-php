--TEST--
PDO_duckdb: Test FSST string select (DuckDB v1.2)
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$dbFile = sys_get_temp_dir() . '/pdo_duckdb_fsst_' . bin2hex(random_bytes(8)) . '.db';
$db = new PDO('duckdb::memory:');

$db->exec('SET enable_fsst_vectors = true');
$db->exec("SET force_compression = 'fsst'");
$db->exec("ATTACH '{$dbFile}' AS fsst_db (STORAGE_VERSION 'v1.2.0')");
$db->exec('CREATE TABLE fsst_db.fsst_values (id INTEGER, s VARCHAR)');
$db->exec("INSERT INTO fsst_db.fsst_values
    SELECT i, 'the quick brown fox jumps over the lazy dog ' || (i % 100)::VARCHAR
    FROM generate_series(1, 10000) t(i)");
$db->exec('CHECKPOINT fsst_db');

$statement = $db->query("SELECT DISTINCT compression FROM pragma_storage_info('fsst_db.fsst_values') WHERE segment_type = 'VARCHAR'");
var_dump($statement->fetchAll(PDO::FETCH_COLUMN));

var_dump($db->query('SELECT DISTINCT vector_type(s) FROM fsst_db.fsst_values')->fetchAll(PDO::FETCH_COLUMN));

$statement = $db->query('SELECT id, s FROM fsst_db.fsst_values ORDER BY id LIMIT 2');
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT count(*) FROM fsst_db.fsst_values WHERE s LIKE 'the quick brown fox%'");
var_dump($statement->fetchColumn());

$db->exec('DETACH fsst_db');
unlink($dbFile);
@unlink($dbFile . '.wal');

?>
--EXPECT--
array(1) {
  [0]=>
  string(4) "FSST"
}
array(1) {
  [0]=>
  string(11) "FSST_VECTOR"
}
array(2) {
  [0]=>
  array(2) {
    ["id"]=>
    int(1)
    ["s"]=>
    string(45) "the quick brown fox jumps over the lazy dog 1"
  }
  [1]=>
  array(2) {
    ["id"]=>
    int(2)
    ["s"]=>
    string(45) "the quick brown fox jumps over the lazy dog 2"
  }
}
int(10000)
