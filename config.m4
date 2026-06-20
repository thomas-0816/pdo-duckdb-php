m4

PHP_ARG_WITH(pdo-duckdb, for DuckDB support,
[  --with-pdo-duckdb    Include DuckDB support])

PHP_REQUIRE_CXX()

PHP_CHECK_PDO_INCLUDES

PHP_ADD_INCLUDE($ext_srcdir)

PHP_NEW_EXTENSION(pdo_duckdb, pdo_duckdb.c duckdb_driver.c duckdb_statement.c duckdb_stubs.cpp,
    $ext_shared,, -DZEND_ENABLE_STATIC_TSRMLS_CACHE=1, 1)

PHP_ADD_EXTENSION_DEP(pdo_duckdb, pdo)
PHP_ADD_MAKEFILE_FRAGMENT

dnl Link duckdb_static with --whole-archive to force all symbols into the .so
PDO_DUCKDB_SHARED_LIBADD="-Wl,--whole-archive -Wl,$ext_srcdir/libduckdb_static.a -Wl,--no-whole-archive -Wl,-lstdc++"
PHP_SUBST(PDO_DUCKDB_SHARED_LIBADD)
