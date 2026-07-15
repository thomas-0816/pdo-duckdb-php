--TEST--
PDO_duckdb: Test string
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$statement = $db->query("SELECT 'Hello' || chr(32) || 'world' AS msg");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT 'Hello'
' '
'World' AS greeting");
var_dump($statement->fetchColumn());

$statement = $db->query("SELECT 'Hello' || ' ' || 'World' AS greeting");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

try {
    $statement = $db->query("SELECT 'Hello' ' ' 'World' AS greeting");
    var_dump($statement->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Caught: " . $e->getMessage() . "\n";
}

$statement = $db->query('SELECT e\'Hello\nworld\' AS msg');
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query('SELECT $$Hello world$$ AS msg');
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query('SELECT $$The price is $9.95$$ AS msg');
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query('SELECT $tag$ this string can contain newlines \'single quotes\', "double quotes", and $$dollar quotes$$ $tag$ AS msg');
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

?>
--EXPECTF--
array(1) {
  [0]=>
  array(1) {
    ["msg"]=>
    string(11) "Hello world"
  }
}
string(11) "Hello World"
array(1) {
  [0]=>
  array(1) {
    ["greeting"]=>
    string(11) "Hello World"
  }
}
Caught: SQLSTATE[HY000]: Parser Error: syntax error at or near "' '"

LINE 1: SELECT 'Hello' ' ' 'World' AS greeting
                       ^

LINE 1: SELECT 'Hello' ' ' 'World' AS greeting
                       ^
array(1) {
  [0]=>
  array(1) {
    ["msg"]=>
    string(11) "Hello
world"
  }
}
array(1) {
  [0]=>
  array(1) {
    ["msg"]=>
    string(11) "Hello world"
  }
}
array(1) {
  [0]=>
  array(1) {
    ["msg"]=>
    string(18) "The price is $9.95"
  }
}
array(1) {
  [0]=>
  array(1) {
    ["msg"]=>
    string(90) " this string can contain newlines 'single quotes', "double quotes", and $$dollar quotes$$ "
  }
}
