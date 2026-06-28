# docker build --no-cache -f Dockerfile -t pdo_duckdb .
# docker run --rm -it pdo_duckdb

FROM debian:trixie-slim AS base

ENV TERM="xterm-256color"
ENV LC_ALL="C.UTF-8"
ENV TZ="Europe/Berlin"

RUN <<EOF
    set -euxo pipefail
    apt-get -y update
    apt-get -y --no-install-recommends install apt-transport-https curl ca-certificates unzip build-essential autoconf libtool pkg-config tzdata
    echo "deb https://packages.sury.org/php/ trixie main" >/etc/apt/sources.list.d/ondrej-php.list
    curl -s https://packages.sury.org/php/apt.gpg >/etc/apt/trusted.gpg.d/php.gpg
    apt-get -y update
    echo ${TZ} > /etc/timezone
    ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime
    apt-get upgrade -y && apt-get clean
    rm -rf /tmp/* /var/lib/apt/lists/* /var/cache/apt/archives/*
EOF

COPY ./ /pdo-duckdb

WORKDIR /pdo-duckdb

RUN <<EOF
    make clean
    unzip -o static-libs-linux-amd64.zip -d ./
EOF

# php 8.2
FROM base
RUN <<EOF
    set -euxo pipefail
    apt-get -y update
    apt-get -y --no-install-recommends install php8.2-cli php8.2-dev
    phpize
    ./configure --with-pdo-duckdb
    make
    php -d extension=$(pwd)/modules/pdo_duckdb.so -m | grep duckdb
    php -d extension=$(pwd)/modules/pdo_duckdb.so test.php
    NO_INTERACTION=1 TEST_PHP_ARGS=" --show-diff --show-clean -q" make test
    make install
    echo "extension=pdo_duckdb.so" > /etc/php/8.2/mods-available/pdo_duckdb.ini
    phpenmod pdo_duckdb
    php -m | grep duckdb
    php test.php
    make clean
EOF

# php 8.3
FROM base
RUN <<EOF
    set -euxo pipefail
    apt-get -y update
    apt-get -y --no-install-recommends install php8.3-cli php8.3-dev
    phpize
    ./configure --with-pdo-duckdb
    make
    php -d extension=$(pwd)/modules/pdo_duckdb.so -m | grep duckdb
    php -d extension=$(pwd)/modules/pdo_duckdb.so test.php
    NO_INTERACTION=1 TEST_PHP_ARGS=" --show-diff --show-clean -q" make test
    make install
    echo "extension=pdo_duckdb.so" > /etc/php/8.3/mods-available/pdo_duckdb.ini
    phpenmod pdo_duckdb
    php -m | grep duckdb
    php test.php
    make clean
EOF

# php 8.4
FROM base
RUN <<EOF
    set -euxo pipefail
    apt-get -y update
    apt-get -y --no-install-recommends install php8.4-cli php8.4-dev
    phpize
    ./configure --with-pdo-duckdb
    make
    php -d extension=$(pwd)/modules/pdo_duckdb.so -m | grep duckdb
    php -d extension=$(pwd)/modules/pdo_duckdb.so test.php
    NO_INTERACTION=1 TEST_PHP_ARGS=" --show-diff --show-clean -q" make test
    make install
    echo "extension=pdo_duckdb.so" > /etc/php/8.4/mods-available/pdo_duckdb.ini
    phpenmod pdo_duckdb
    php -m | grep duckdb
    php test.php
    make clean
EOF

# php 8.5
FROM base
RUN <<EOF
    set -euxo pipefail
    apt-get -y update
    apt-get -y --no-install-recommends install php8.5-cli php8.5-dev
    phpize
    ./configure --with-pdo-duckdb
    make
    php -d extension=$(pwd)/modules/pdo_duckdb.so -m | grep duckdb
    php -d extension=$(pwd)/modules/pdo_duckdb.so test.php
    NO_INTERACTION=1 TEST_PHP_ARGS=" --show-diff --show-clean -q" make test
    make install
    echo "extension=pdo_duckdb.so" > /etc/php/8.5/mods-available/pdo_duckdb.ini
    phpenmod pdo_duckdb
    php -m | grep duckdb
    php test.php
    make clean
EOF
