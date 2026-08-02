--TEST--
PDO_duckdb: Test json
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$list = [
    ['aaa', 'bbb', 'ccc'],
    ['123', '456', '789'],
    ['aaa', 'bbb', 'ccc']
];
$csvFile = sys_get_temp_dir() . '/test.csv';
$fp = fopen($csvFile, 'w');
foreach ($list as $fields) {
    fputcsv($fp, $fields, ',', '"', "");
}
fclose($fp);

$db = new PDO('duckdb::memory:');
$statement = $db->query("SELECT * FROM '{$csvFile}'");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$db = new PDO('duckdb::memory:');
$url = 'https://data.meteostat.net/hourly/2026/10381.csv.gz';
$rows = $db->query("select hour, temp from read_csv('{$url}') where year = 2026 and month = 7 and day = 25 and hour > 9 limit 4");
echo json_encode($rows->fetchAll(PDO::FETCH_ASSOC)), PHP_EOL;

?>
--EXPECTF--
array(2) {
  [0]=>
  array(3) {
    ["aaa"]=>
    string(3) "123"
    ["bbb"]=>
    string(3) "456"
    ["ccc"]=>
    string(3) "789"
  }
  [1]=>
  array(3) {
    ["aaa"]=>
    string(3) "aaa"
    ["bbb"]=>
    string(3) "bbb"
    ["ccc"]=>
    string(3) "ccc"
  }
}
[{"hour":10,"temp":24.1},{"hour":11,"temp":25.5},{"hour":12,"temp":26.4},{"hour":13,"temp":27.4}]
