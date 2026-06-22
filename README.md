## PHP PDO DuckDB

DuckDB is an embedded SQL database designed for high-performance analytics (OLAP).

This repository provides a native DuckDB database driver for the PHP Data Objects (PDO) extension.

The build process bundles libduckdb directly into `pdo_duckdb.so`.

This extension supports all DuckDB types: Text, Numeric, Date, Time, Interval, JSON, Array, Struct, Map, List, Enum, Variant, Geometry, Union, Bitstrings, Blobs and Boolean.


### Usage examples

    $duckDb = new PDO('duckdb::memory:');
    $duckDb->exec("CREATE TABLE table1 (id INTEGER, amount DECIMAL(10, 2), description VARCHAR)");

    $statement = $duckDb->prepare("INSERT INTO table1 VALUES (?, ?, ?)");
    $statement->execute([1, 42.21, 'Hello DuckDB! 🐘 💓 🦆']);

    $statement = $duckDb->query("SELECT * FROM table1");
    print_r($statement->fetchAll(PDO::FETCH_ASSOC));

    Array
    (
        [0] => Array
            (
                [id] => 1
                [amount] => 42.21
                [description] => Hello DuckDB! 🐘 💓 🦆
            )
    )


### Open databases from disk or in-memory

    $db = new PDO('duckdb::memory:'); // open in-memory database

    $db = new PDO('duckdb:/tmp/test.db'); // open database file from disk

    // open database file as read-only
    $db = new PDO('duckdb:/tmp/test.db', null, null, [PDO::DUCKDB_ATTR_CONFIG => ['access_mode' => 'read_only']]);


### Read and write Parquet files

    $db = new PDO('duckdb::memory:');
    $db->exec("CREATE TABLE table2 (id INTEGER, text VARCHAR USING COMPRESSION zstd, data JSON)");

    $statement = $db->prepare("INSERT INTO table2 VALUES (?, ?, ?)");
    $statement->execute([1, 'Hello DuckDB 🦆', ['foo' => 'bar', 'baz' => 42]]);

    $statement = $db->exec("
        COPY (SELECT * FROM table2) TO '/tmp/table2.parquet' (COMPRESSION zstd)
    ");

    foreach ($db->query("SELECT * FROM '/tmp/table2.parquet'", PDO::FETCH_ASSOC) as $row) {
        print_r($row);
    }

    Array
    (
        [id] => 1
        [text] => Hello DuckDB 🦆
        [data] => Array
            (
                [foo] => bar
                [baz] => 42
            )
    )


### Read CSV files with SQL

    $list = [
        ['aaa', 'bbb', 'ccc'],
        ['123', '456', '789'],
        ['aaa', 'bbb', 'ccc']
    ];
    $fp = fopen('/tmp/test.csv', 'w');
    foreach ($list as $fields) {
        fputcsv($fp, $fields, ',', '"', "");
    }
    fclose($fp);

    $db = new PDO('duckdb::memory:');
    $statement = $db->query("SELECT * FROM '/tmp/test.csv'");
    print_r($statement->fetchAll(PDO::FETCH_ASSOC));

    Array
    (
        [0] => Array
            (
                [aaa] => 123
                [bbb] => 456
                [ccc] => 789
            )
        [1] => Array
            (
                [aaa] => aaa
                [bbb] => bbb
                [ccc] => ccc
            )
    )


### Read JSON files with SQL

    file_put_contents('/tmp/logs.json', json_encode(['log' => 'log text']) . PHP_EOL, FILE_APPEND);
    file_put_contents('/tmp/logs.json', json_encode(['log' => 'log text 2']) . PHP_EOL, FILE_APPEND);

    $db = new PDO('duckdb::memory:');
    $statement = $db->query("SELECT * FROM '/tmp/logs.json'");
    print_r($statement->fetchAll(PDO::FETCH_ASSOC));

    Array
    (
        [0] => Array
            (
                [log] => log text
            )
        [1] => Array
            (
                [log] => log text 2
            )
    )

    $statement = $db->exec("
        COPY (SELECT * FROM '/tmp/logs.json') TO '/tmp/logs_json.parquet' (COMPRESSION zstd)
    ");


### Use nested columns with a fixed schema

    // s is array{v: string, i: int, a: string[], d: float}

    $db = new PDO('duckdb::memory:');
    $db->exec("create table table1 (s STRUCT(v VARCHAR, i INTEGER, a VARCHAR[], d DECIMAL(10, 2)))");

    $statement = $db->prepare("INSERT INTO table1 VALUES (?)");
    $statement->execute([['v' => 'foo', 'i' => 21, 'a' => ['b', 'c'], 'd' => 42.21]]);

    $statement = $db->query("SELECT * FROM table1");
    print_r($statement->fetchAll(PDO::FETCH_ASSOC));

    Array
    (
        [s] => Array
            (
                [v] => foo
                [i] => 21
                [a] => Array
                    (
                        [0] => b
                        [1] => c
                    )
                [d] => 42.21
            )
    )

### Setup

    git clone --depth=1 --branch=main https://github.com/thomas-0816/pdo-duckdb.git
    cd pdo_duckdb

    wget https://github.com/duckdb/duckdb/releases/download/v1.5.4/libduckdb-linux-amd64.zip
    unzip -o libduckdb-linux-amd64.zip -d ./

    phpize
    ./configure --with-pdo-duckdb
    make -j$(nproc)
    NO_INTERACTION=1 make test

    php -d extension=$(pwd)/modules/pdo_duckdb.so -m | grep duckdb
    php -d extension=$(pwd)/modules/pdo_duckdb.so test.php

    sudo make install
    sudo sh -c 'echo "extension=pdo_duckdb.so" > /etc/php/8.5/mods-available/pdo_duckdb.ini'
    sudo phpenmod pdo_duckdb

    php -m | grep duckdb
    php test.php

### Docker

    docker build -t pdo_duckdb .
    docker run --rm -it pdo_duckdb php test.php

### Security

    # Disable extension loading
    SET autoload_known_extensions = false;
    SET autoinstall_known_extensions = false;
    SET allow_community_extensions = false;

    # Disable external file access, directory white listing
    SET allowed_directories = ['/tmp'];
    SET enable_external_access = false;

    # Resource limits
    SET threads = 4;
    SET memory_limit = '4GB';
    SET max_temp_directory_size = '4GB';

    https://duckdb.org/docs/lts/operations_manual/securing_duckdb/overview

### Why DuckDB?

Its main advantages include:

In-Process Architecture: Like SQLite, DuckDB embeds directly into host applications, eliminating the need for a separate server setup.

Extreme Analytical Speed: It uses columnar storage and vectorized (batch) processing, running analytics 10–100x faster than traditional row-oriented databases.

"Larger-than-Memory" Processing: DuckDB gracefully spills data to disk, allowing you to process massive datasets (e.g., 50GB+) on a machine with minimal RAM (e.g., 1GB).

File-Format Agnostic: It can query flat files (JSON, CSV, and Parquet) directly via SQL without needing to import or load the data into a database first.

No Infrastructure Cost: It brings data warehouse-level performance to your local laptop or local server.

DuckDB achieves blazing-fast analytical performance through its embedded, serverless multi-core architecture combined with columnar storage and vectorized execution.
By executing queries directly within the host application, it eliminates serialization and network overhead, processing data in batches (vectors) rather
than row-by-row for unparalleled speed.

https://duckdb.org/why_duckdb

Key Performance Advantages:

Vectorized Query Execution: Unlike row-oriented engines, DuckDB processes data in cache-friendly batches (vectors). This allows modern hardware to operate on
entire arrays of data simultaneously, drastically reducing CPU cycles per query.

Columnar Storage: Data is stored by column rather than by row. For analytical queries that only require a few metrics,
DuckDB only reads the relevant columns from disk/memory, saving massive amounts of I/O.

Zero-Copy In-Process Engine: As an in-process database, DuckDB runs directly in the memory space of your application.

Advanced Query Optimizer: DuckDB features an advanced query optimizer that handles filter pushdowns, unnesting of subqueries, and dynamic runtime filters.
This ensures queries only scan necessary data and avoids full-table sorting when possible.

Direct File Querying: You can query large datasets in open formats like Parquet and CSV directly on disk or in cloud storage (like AWS S3) without needing to import or convert the data first.

### Development

    php run-tests.php -d extension=$(pwd)/modules/pdo_duckdb.so --show-diff --show-clean -q

### AI Disclosure

The C code is written by AI, the tests are written without AI.

### License

MIT License
