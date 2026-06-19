m4

PHP_ARG_WITH(pdo-duckdb, for DuckDB support,
[  --with-pdo-duckdb    Include DuckDB support])

PHP_ADD_INCLUDE(/lib/)
PHP_ADD_LIBRARY_WITH_PATH(duckdb, /lib/, PDO_DUCKDB_SHARED_LIBADD)


#DUCKDB_DIR="$srcdir/libs"
#PHP_ADD_INCLUDE($DUCKDB_DIR)
#PHP_ADD_LIBRARY_WITH_PATH(duckdb_static, $DUCKDB_DIR, PDO_DUCKDB_SHARED_LIBADD)
#PHP_ADD_LIBRARY(stdc++, 1, PDO_DUCKDB_SHARED_LIBADD)

#PHP_NEW_EXTENSION(pdo_duckdb,
#pdo_duckdb.c duckdb_driver.c duckdb_statement.c,
#$ext_shared,, -DZEND_ENABLE_STATIC_TSRMLS_CACHE=1)

#PHP_REQUIRE_CXX()



PHP_NEW_EXTENSION(pdo_duckdb, pdo_duckdb.c duckdb_driver.c duckdb_statement.c,
    $ext_shared,,-DZEND_ENABLE_STATIC_TSRMLS_CACHE=1)

PHP_ADD_EXTENSION_DEP(pdo_duckdb, pdo)
PHP_SUBST(PDO_DUCKDB_SHARED_LIBADD)
