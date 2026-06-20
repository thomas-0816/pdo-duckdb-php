## PHP PDO DuckDB

DuckDB is an embedded SQL database designed for high-performance analytics (OLAP).

This repository provides a native DuckDB database driver for the PHP Data Objects (PDO) extension.

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

### Usage examples

    $duckDb = new PDO('duckdb::memory:');$duckDb = new PDO('duckdb::memory:');
    $duckDb->exec("CREATE TABLE table1 (id INTEGER, amount DECIMAL(10, 2), description VARCHAR USING COMPRESSION zstd)");

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


    $duckDb = new PDO('duckdb:/tmp/pdo_duckdb_test.db');
    $duckDb->exec("CREATE TABLE table2 (id INTEGER, text VARCHAR, data JSON)");

    $statement = $duckDb->prepare("INSERT INTO table2 VALUES (?, ?, ?)");
    $statement->execute([1, 'Hello DuckDB 🦆', json_encode(['foo' => 'bar', 'baz' => 42])]);

    $statement = $duckDb->exec("
        COPY (SELECT * FROM table2)
        TO '/tmp/pdo_duckdb_test_table2.parquet'
        (FORMAT parquet, COMPRESSION zstd, ROW_GROUP_SIZE 100_000)
    ");

    foreach ($duckDb->query("SELECT * FROM '/tmp/pdo_duckdb_test_table2.parquet'", PDO::FETCH_ASSOC) as $row) {
        print_r($row);
    }

    Array
    (
        [0] => Array
            (
                [date] => 2026-01-02 03:04:05
                [log] => log text
            )

        [1] => Array
            (
                [date] => 2026-02-03 04:05:06
                [log] => log text 2
            )

    )


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

    php run-tests.php --show-diff --show-clean -q

### AI Disclosure

    Yes for the C code
    (the tests are not written by AI)
