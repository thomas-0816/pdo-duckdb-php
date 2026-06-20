--TEST--
PDO_duckdb: Test struct
--EXTENSIONS--
pdo_duckdb
--FILE--
<?php

$db = new PDO('duckdb::memory:');
$statement = $db->query("SELECT {'birds': ['duck', 'goose', 'heron'], 'aliens': NULL, 'amphibians': ['frog', 'toad']} as struct");
$statement = $db->query("SELECT struct_pack(key1 := 'value1', key2 := 42) AS s");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {'key1': 'value1', 'key2': 42} AS s");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT d AS s FROM (SELECT 'value1' AS key1, 42 AS key2) d");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {'x': 1, 'y': 2, 'z': 3} AS s");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {'yes': 'duck', 'maybe': 'goose', 'huh': NULL, 'no': 'heron'} AS s");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {'key1': 'string', 'key2': 1, 'key3': 12.345} AS s");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {
    'birds': {'yes': 'duck', 'maybe': 'goose', 'huh': NULL, 'no': 'heron'}, 'aliens': NULL,
    'amphibians': {'yes': 'frog', 'maybe': 'salamander', 'huh': 'dragon', 'no': 'toad'}
} AS s;
");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT struct_update({'a': 1, 'b': 2}, b := 3, c := 4) AS s");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT a.x FROM (SELECT {'x': 1, 'y': 2, 'z': 3} AS a)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT a.\"x space\" FROM (SELECT {'x space': 1, 'y': 2, 'z': 3} AS a)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT a['x space'] FROM (SELECT {'x space': 1, 'y': 2, 'z': 3} AS a)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT struct_extract({'x space': 1, 'y': 2, 'z': 3}, 'x space')");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT unnest(a) FROM (SELECT {'x': 1, 'y': 2, 'z': 3} AS a)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT a.* EXCLUDE ('y') FROM (SELECT {'x': 1, 'y': 2, 'z': 3} AS a)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT struct_pack(y := a.x) AS b FROM (SELECT {'x': 42} AS a)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT row(x, x + 1, y) as a FROM (SELECT 1 AS x, 'a' AS y) AS s");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT (x, x + 1, y) AS s FROM (SELECT 1 AS x, 'a' AS y)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {'k1': 1, 'k2': 0} < {'k1': 0, 'k2': 1}");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {'k1': 1, 'k2': 0} > {'k3': 0, 'k1': 0}");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {'test': [MAP([1, 5], [42.1, 45]), MAP([1, 5], [42.1, 45])]} as struct");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT struct_pack(key1 := 'value1', key2 := 42) AS s");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {'key1': 'value1', 'key2': 42} AS s");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT d AS s FROM (SELECT 'value1' AS key1, 42 AS key2) d");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {'x': 1, 'y': 2, 'z': 3} AS s");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {'yes': 'duck', 'maybe': 'goose', 'huh': NULL, 'no': 'heron'} AS s");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {'key1': 'string', 'key2': 1, 'key3': 12.345} AS s");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {
    'birds': {'yes': 'duck', 'maybe': 'goose', 'huh': NULL, 'no': 'heron'}, 'aliens': NULL,
    'amphibians': {'yes': 'frog', 'maybe': 'salamander', 'huh': 'dragon', 'no': 'toad'}
} AS s;
");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT struct_update({'a': 1, 'b': 2}, b := 3, c := 4) AS s");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT a.x FROM (SELECT {'x': 1, 'y': 2, 'z': 3} AS a)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT a.\"x space\" FROM (SELECT {'x space': 1, 'y': 2, 'z': 3} AS a)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT a['x space'] FROM (SELECT {'x space': 1, 'y': 2, 'z': 3} AS a)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT struct_extract({'x space': 1, 'y': 2, 'z': 3}, 'x space')");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT unnest(a) FROM (SELECT {'x': 1, 'y': 2, 'z': 3} AS a)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT a.* EXCLUDE ('y') FROM (SELECT {'x': 1, 'y': 2, 'z': 3} AS a)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT struct_pack(y := a.x) AS b FROM (SELECT {'x': 42} AS a)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT row(x, x + 1, y) as a FROM (SELECT 1 AS x, 'a' AS y) AS s");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT (x, x + 1, y) AS s FROM (SELECT 1 AS x, 'a' AS y)");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {'k1': 1, 'k2': 0} < {'k1': 0, 'k2': 1}");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

$statement = $db->query("SELECT {'k1': 1, 'k2': 0} > {'k3': 0, 'k1': 0}");
var_dump($statement->fetchAll(PDO::FETCH_ASSOC));

?>
--EXPECTF--
array(1) {
  [0]=>
  array(1) {
    ["s"]=>
    array(2) {
      ["key1"]=>
      string(6) "value1"
      ["key2"]=>
      int(42)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["s"]=>
    array(2) {
      ["key1"]=>
      string(6) "value1"
      ["key2"]=>
      int(42)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["s"]=>
    array(2) {
      ["key1"]=>
      string(6) "value1"
      ["key2"]=>
      int(42)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["s"]=>
    array(3) {
      ["x"]=>
      int(1)
      ["y"]=>
      int(2)
      ["z"]=>
      int(3)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["s"]=>
    array(4) {
      ["yes"]=>
      string(4) "duck"
      ["maybe"]=>
      string(5) "goose"
      ["huh"]=>
      NULL
      ["no"]=>
      string(5) "heron"
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["s"]=>
    array(3) {
      ["key1"]=>
      string(6) "string"
      ["key2"]=>
      int(1)
      ["key3"]=>
      float(12.345)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["s"]=>
    array(3) {
      ["birds"]=>
      array(4) {
        ["yes"]=>
        string(4) "duck"
        ["maybe"]=>
        string(5) "goose"
        ["huh"]=>
        NULL
        ["no"]=>
        string(5) "heron"
      }
      ["aliens"]=>
      NULL
      ["amphibians"]=>
      array(4) {
        ["yes"]=>
        string(4) "frog"
        ["maybe"]=>
        string(10) "salamander"
        ["huh"]=>
        string(6) "dragon"
        ["no"]=>
        string(4) "toad"
      }
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["s"]=>
    array(3) {
      ["a"]=>
      int(1)
      ["b"]=>
      int(3)
      ["c"]=>
      int(4)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["x"]=>
    int(1)
  }
}
array(1) {
  [0]=>
  array(1) {
    ["x space"]=>
    int(1)
  }
}
array(1) {
  [0]=>
  array(1) {
    ["a['x space']"]=>
    int(1)
  }
}
array(1) {
  [0]=>
  array(1) {
    ["struct_extract(main.struct_pack("x space" := 1, y := 2, z := 3), 'x space')"]=>
    int(1)
  }
}
array(1) {
  [0]=>
  array(3) {
    ["x"]=>
    int(1)
    ["y"]=>
    int(2)
    ["z"]=>
    int(3)
  }
}
array(1) {
  [0]=>
  array(2) {
    ["x"]=>
    int(1)
    ["z"]=>
    int(3)
  }
}
array(1) {
  [0]=>
  array(1) {
    ["b"]=>
    array(1) {
      ["y"]=>
      int(42)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["a"]=>
    array(3) {
      [0]=>
      int(1)
      [1]=>
      int(2)
      [2]=>
      string(1) "a"
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["s"]=>
    array(3) {
      [0]=>
      int(1)
      [1]=>
      int(2)
      [2]=>
      string(1) "a"
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["(main.struct_pack(k1 := 1, k2 := 0) < main.struct_pack(k1 := 0, k2 := 1))"]=>
    bool(false)
  }
}
array(1) {
  [0]=>
  array(1) {
    ["(main.struct_pack(k1 := 1, k2 := 0) > main.struct_pack(k3 := 0, k1 := 0))"]=>
    bool(true)
  }
}
array(1) {
  [0]=>
  array(1) {
    ["struct"]=>
    array(1) {
      ["test"]=>
      array(2) {
        [0]=>
        array(2) {
          [1]=>
          float(42.1)
          [5]=>
          float(45)
        }
        [1]=>
        array(2) {
          [1]=>
          float(42.1)
          [5]=>
          float(45)
        }
      }
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["s"]=>
    array(2) {
      ["key1"]=>
      string(6) "value1"
      ["key2"]=>
      int(42)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["s"]=>
    array(2) {
      ["key1"]=>
      string(6) "value1"
      ["key2"]=>
      int(42)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["s"]=>
    array(2) {
      ["key1"]=>
      string(6) "value1"
      ["key2"]=>
      int(42)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["s"]=>
    array(3) {
      ["x"]=>
      int(1)
      ["y"]=>
      int(2)
      ["z"]=>
      int(3)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["s"]=>
    array(4) {
      ["yes"]=>
      string(4) "duck"
      ["maybe"]=>
      string(5) "goose"
      ["huh"]=>
      NULL
      ["no"]=>
      string(5) "heron"
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["s"]=>
    array(3) {
      ["key1"]=>
      string(6) "string"
      ["key2"]=>
      int(1)
      ["key3"]=>
      float(12.345)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["s"]=>
    array(3) {
      ["birds"]=>
      array(4) {
        ["yes"]=>
        string(4) "duck"
        ["maybe"]=>
        string(5) "goose"
        ["huh"]=>
        NULL
        ["no"]=>
        string(5) "heron"
      }
      ["aliens"]=>
      NULL
      ["amphibians"]=>
      array(4) {
        ["yes"]=>
        string(4) "frog"
        ["maybe"]=>
        string(10) "salamander"
        ["huh"]=>
        string(6) "dragon"
        ["no"]=>
        string(4) "toad"
      }
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["s"]=>
    array(3) {
      ["a"]=>
      int(1)
      ["b"]=>
      int(3)
      ["c"]=>
      int(4)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["x"]=>
    int(1)
  }
}
array(1) {
  [0]=>
  array(1) {
    ["x space"]=>
    int(1)
  }
}
array(1) {
  [0]=>
  array(1) {
    ["a['x space']"]=>
    int(1)
  }
}
array(1) {
  [0]=>
  array(1) {
    ["struct_extract(main.struct_pack("x space" := 1, y := 2, z := 3), 'x space')"]=>
    int(1)
  }
}
array(1) {
  [0]=>
  array(3) {
    ["x"]=>
    int(1)
    ["y"]=>
    int(2)
    ["z"]=>
    int(3)
  }
}
array(1) {
  [0]=>
  array(2) {
    ["x"]=>
    int(1)
    ["z"]=>
    int(3)
  }
}
array(1) {
  [0]=>
  array(1) {
    ["b"]=>
    array(1) {
      ["y"]=>
      int(42)
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["a"]=>
    array(3) {
      [0]=>
      int(1)
      [1]=>
      int(2)
      [2]=>
      string(1) "a"
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["s"]=>
    array(3) {
      [0]=>
      int(1)
      [1]=>
      int(2)
      [2]=>
      string(1) "a"
    }
  }
}
array(1) {
  [0]=>
  array(1) {
    ["(main.struct_pack(k1 := 1, k2 := 0) < main.struct_pack(k1 := 0, k2 := 1))"]=>
    bool(false)
  }
}
array(1) {
  [0]=>
  array(1) {
    ["(main.struct_pack(k1 := 1, k2 := 0) > main.struct_pack(k3 := 0, k1 := 0))"]=>
    bool(true)
  }
}
