## PHP PDO DuckDB

### Setup

    git https://github.com/thomas-0816/pdo-duckdb.git
    cd pdo_duckdb

    wget https://github.com/duckdb/duckdb/releases/download/v1.5.4/libduckdb-linux-amd64.zip
    unzip libduckdb-linux-amd64.zip -d ./

    phpize
    ./configure --with-pdo-duckdb
    make -j$(nproc)
    php -d extension=$(pwd)/modules/pdo_duckdb.so -m | grep duckdb
    php -d extension=$(pwd)/modules/pdo_duckdb.so test.php

    sudo make install
    php -d extension=pdo_duckdb.so -m | grep duckdb
    php -d extension=pdo_duckdb.so test.php

### AI Disclosure

    Yes for the C code
    (the tests are not written by AI)
