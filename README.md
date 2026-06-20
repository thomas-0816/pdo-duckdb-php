## PHP PDO DuckDB

### Setup

    git clone --depth=1 --branch=main https://github.com/thomas-0816/pdo-duckdb.git
    cd pdo_duckdb

    wget https://github.com/duckdb/duckdb/releases/download/v1.5.4/libduckdb-linux-amd64.zip
    unzip -o libduckdb-linux-amd64.zip -d ./

    phpize
    ./configure --with-pdo-duckdb
    make -j$(nproc)
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

### AI Disclosure

    Yes for the C code
    (the tests are not written by AI)
