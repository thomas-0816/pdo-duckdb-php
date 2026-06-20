--TEST--
PDO_duckdb: Test array
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$statement = $db->query("SELECT array_value(1, 2, 3) as aa");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT array_value(1, 2, 3)[2] as aa");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT [3, 2, 1]::INTEGER[3] as aa");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT array_value(array_value(1, 2), array_value(3, 4), array_value(5, 6)) as aa");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT array_value({'a': 1, 'b': 2}, {'a': 3, 'b': 4}) as aa");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$db->exec("CREATE TABLE array_table (id INTEGER, arr INTEGER[3])");
$db->exec("INSERT INTO array_table VALUES (10, [1, 2, 3]), (20, [4, 5, 6])");
$statement = $db->query("SELECT id, arr[1] AS element FROM array_table");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));
$statement = $db->query("SELECT id, list_extract(arr, 1) AS element FROM array_table");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));
$statement = $db->query("SELECT id, array_extract(arr, 1) AS element FROM array_table");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));
$statement = $db->query("SELECT id, arr[1:2] AS elements FROM array_table");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$db->exec("CREATE TABLE x (i INTEGER, v FLOAT[3])");
$db->exec("CREATE TABLE y (i INTEGER, v FLOAT[3])");
$db->exec("INSERT INTO x VALUES (1, array_value(1.0::FLOAT, 2.0::FLOAT, 3.0::FLOAT))");
$db->exec("INSERT INTO y VALUES (1, array_value(2.1::FLOAT, 3.2::FLOAT, 4.3::FLOAT))");

$statement = $db->query("SELECT * FROM x");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));
$statement = $db->query("SELECT * FROM y");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

?>
--EXPECTF--
array(1) {
  [0]=>
  array(1) {
    ["aa"]=>
    array(3) {
      [0]=>
      int(1)
      [1]=>
      int(2)
      [2]=>
      int(3)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["aa"]=>
    int(2)
  }
}
array(1) {
  [0]=>
  array(1) {
    ["aa"]=>
    array(3) {
      [0]=>
      int(3)
      [1]=>
      int(2)
      [2]=>
      int(1)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["aa"]=>
    array(3) {
      [0]=>
      array(2) {
        [0]=>
        int(1)
        [1]=>
        int(2)
      }
      [1]=>
      array(2) {
        [0]=>
        int(3)
        [1]=>
        int(4)
      }
      [2]=>
      array(2) {
        [0]=>
        int(5)
        [1]=>
        int(6)
      }
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["aa"]=>
    array(2) {
      [0]=>
      array(2) {
        ["a"]=>
        int(1)
        ["b"]=>
        int(2)
      }
      [1]=>
      array(2) {
        ["a"]=>
        int(3)
        ["b"]=>
        int(4)
      }
    }
  }
}
array(2) {
  [0]=>
  array(2) {
    ["id"]=>
    int(10)
    ["element"]=>
    int(1)
  }
  [1]=>
  array(2) {
    ["id"]=>
    int(20)
    ["element"]=>
    int(4)
  }
}
array(2) {
  [0]=>
  array(2) {
    ["id"]=>
    int(10)
    ["element"]=>
    int(1)
  }
  [1]=>
  array(2) {
    ["id"]=>
    int(20)
    ["element"]=>
    int(4)
  }
}
array(2) {
  [0]=>
  array(2) {
    ["id"]=>
    int(10)
    ["element"]=>
    int(1)
  }
  [1]=>
  array(2) {
    ["id"]=>
    int(20)
    ["element"]=>
    int(4)
  }
}
array(2) {
  [0]=>
  array(2) {
    ["id"]=>
    int(10)
    ["elements"]=>
    array(2) {
      [0]=>
      int(1)
      [1]=>
      int(2)
    }
  }
  [1]=>
  array(2) {
    ["id"]=>
    int(20)
    ["elements"]=>
    array(2) {
      [0]=>
      int(4)
      [1]=>
      int(5)
    }
  }
}
array(1) {
  [0]=>
  array(2) {
    ["i"]=>
    int(1)
    ["v"]=>
    array(3) {
      [0]=>
      float(1)
      [1]=>
      float(2)
      [2]=>
      float(3)
    }
  }
}
array(1) {
  [0]=>
  array(2) {
    ["i"]=>
    int(1)
    ["v"]=>
    array(3) {
      [0]=>
      float(2.1)
      [1]=>
      float(3.2)
      [2]=>
      float(4.3)
    }
  }
}
