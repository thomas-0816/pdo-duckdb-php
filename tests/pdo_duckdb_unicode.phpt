--TEST--
PDO_duckdb: Test unicode
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');

$db->exec("CREATE TABLE unicode_test (id INTEGER, latin VARCHAR, mb4 VARCHAR)");
$statement = $db->prepare("INSERT INTO unicode_test VALUES (?, ?, ?)");
$statement->execute([1, 'äöüßÄÖÜ', 'emoji 🐘 test']);
$statement = $db->query("SELECT * FROM unicode_test");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 'Straße' AS german, 'München' AS city, 'café' AS french, '🐘🐋🦀' AS emoji");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->prepare("INSERT INTO unicode_test VALUES (?, ?, ?)");
$statement->execute([2, 'café résumé', 'four-byte: 🐘🐋🦀🌍']);
$statement = $db->query("SELECT * FROM unicode_test ORDER BY id");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$db = new PDO('duckdb::memory:');
$string = 'Nice O\'Brian $dollar "quote" \'single quote\' 🐘🐋🦀🌍 öäüß';
var_dump($db->quote($string));

?>
--EXPECTF--
array(1) {
  [0]=>
  array(3) {
    ["id"]=>
    int(1)
    ["latin"]=>
    string(14) "äöüßÄÖÜ"
    ["mb4"]=>
    string(15) "emoji 🐘 test"
  }
}
array(1) {
  [0]=>
  array(4) {
    ["german"]=>
    string(7) "Straße"
    ["city"]=>
    string(8) "München"
    ["french"]=>
    string(5) "café"
    ["emoji"]=>
    string(12) "🐘🐋🦀"
  }
}
array(2) {
  [0]=>
  array(3) {
    ["id"]=>
    int(1)
    ["latin"]=>
    string(14) "äöüßÄÖÜ"
    ["mb4"]=>
    string(15) "emoji 🐘 test"
  }
  [1]=>
  array(3) {
    ["id"]=>
    int(2)
    ["latin"]=>
    string(14) "café résumé"
    ["mb4"]=>
    string(27) "four-byte: 🐘🐋🦀🌍"
  }
}
string(74) "'Nice O''Brian $dollar "quote" ''single quote'' 🐘🐋🦀🌍 öäüß'"
