# docker build --pull --no-cache -f Dockerfile.php -t pdo_duckdb_php .
# docker run --rm -it pdo_duckdb_php

# php 8.2
FROM php:8.2-cli
ENV TERM="xterm-256color"
ENV LC_ALL="C.UTF-8"
RUN <<EOF
    set -euxo pipefail
    apt-get -y update
    apt-get -y --no-install-recommends install unzip
    curl -fsSL -o /tmp/pie https://github.com/php/pie/releases/latest/download/pie.phar
    php /tmp/pie install --no-build-tools-check thomas-0816/pdo-duckdb-php
    php -m | grep duckdb
    php -r 'print_r((new PDO("duckdb::memory:"))->query("SELECT 42 as n")->fetch(PDO::FETCH_ASSOC));'
    apt-get upgrade -y && apt-get clean
    rm -rf /tmp/* /var/lib/apt/lists/* /var/cache/apt/archives/*
EOF

# php 8.3
FROM php:8.3-cli
ENV TERM="xterm-256color"
ENV LC_ALL="C.UTF-8"
RUN <<EOF
    set -euxo pipefail
    apt-get -y update
    apt-get -y --no-install-recommends install unzip
    curl -fsSL -o /tmp/pie https://github.com/php/pie/releases/latest/download/pie.phar
    php /tmp/pie install --no-build-tools-check thomas-0816/pdo-duckdb-php
    php -m | grep duckdb
    php -r 'print_r((new PDO("duckdb::memory:"))->query("SELECT 42 as n")->fetch(PDO::FETCH_ASSOC));'
    apt-get upgrade -y && apt-get clean
    rm -rf /tmp/* /var/lib/apt/lists/* /var/cache/apt/archives/*
EOF

# php 8.4
FROM php:8.4-cli
ENV TERM="xterm-256color"
ENV LC_ALL="C.UTF-8"
RUN <<EOF
    set -euxo pipefail
    apt-get -y update
    apt-get -y --no-install-recommends install unzip
    curl -fsSL -o /tmp/pie https://github.com/php/pie/releases/latest/download/pie.phar
    php /tmp/pie install --no-build-tools-check thomas-0816/pdo-duckdb-php
    php -m | grep duckdb
    php -r 'print_r((new PDO("duckdb::memory:"))->query("SELECT 42 as n")->fetch(PDO::FETCH_ASSOC));'
    apt-get upgrade -y && apt-get clean
    rm -rf /tmp/* /var/lib/apt/lists/* /var/cache/apt/archives/*
EOF

# php 8.5
FROM php:8.5-cli
ENV TERM="xterm-256color"
ENV LC_ALL="C.UTF-8"
RUN <<EOF
    set -euxo pipefail
    apt-get -y update
    apt-get -y --no-install-recommends install unzip
    curl -fsSL -o /tmp/pie https://github.com/php/pie/releases/latest/download/pie.phar
    php /tmp/pie install --no-build-tools-check thomas-0816/pdo-duckdb-php
    php -m | grep duckdb
    php -r 'print_r((new PDO("duckdb::memory:"))->query("SELECT 42 as n")->fetch(PDO::FETCH_ASSOC));'
    apt-get upgrade -y && apt-get clean
    rm -rf /tmp/* /var/lib/apt/lists/* /var/cache/apt/archives/*
EOF
