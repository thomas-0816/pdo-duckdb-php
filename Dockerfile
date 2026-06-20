# docker build -t pdo_duckdb .
# docker run --rm -it pdo_duckdb

FROM debian:trixie-slim

ENV TERM="xterm-256color"
ENV LC_ALL="C.UTF-8"

RUN <<EOF
    set -euxo pipefail
    apt-get -y update
    DEBIAN_FRONTEND=noninteractive apt-get -y --no-install-recommends install apt-transport-https curl ca-certificates wget unzip git build-essential autoconf libtool pkg-config
    echo "deb https://packages.sury.org/php/ trixie main" >/etc/apt/sources.list.d/ondrej-php.list
    curl -s https://packages.sury.org/php/apt.gpg >/etc/apt/trusted.gpg.d/php.gpg
    apt-get -y update
    DEBIAN_FRONTEND=noninteractive apt-get -y --no-install-recommends install php8.5-cli php8.5-dev
    wget -q https://github.com/duckdb/duckdb/releases/download/v1.5.4/libduckdb-linux-amd64.zip
    apt-get upgrade -y && apt-get clean
    rm -rf /tmp/* /var/lib/apt/lists/* /var/cache/apt/archives/*
EOF

RUN <<EOF
    git clone --depth=1 --branch=main https://github.com/thomas-0816/pdo-duckdb.git
    cd pdo-duckdb

    unzip /libduckdb-linux-amd64.zip -d ./

    phpize
    ./configure --with-pdo-duckdb
    make -j$(nproc)
    php -d extension=$(pwd)/modules/pdo_duckdb.so -m | grep duckdb
    php -d extension=$(pwd)/modules/pdo_duckdb.so test.php
    make install
    echo "extension=pdo_duckdb.so" > /etc/php/8.5/mods-available/pdo_duckdb.ini
    phpenmod pdo_duckdb
    php -m | grep duckdb
    php test.php
    php -m | grep duckdb
    php test.php
EOF

WORKDIR /pdo-duckdb
