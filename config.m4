m4

PHP_ARG_WITH(pdo-duckdb, for DuckDB support,
[  --with-pdo-duckdb    Include DuckDB support])

PHP_REQUIRE_CXX()

PHP_CXX_COMPILE_STDCXX(11, mandatory, PDO_DUCKDB_CXX_STD)
CXXFLAGS="$CXXFLAGS $PDO_DUCKDB_CXX_STD"

PHP_CHECK_PDO_INCLUDES

PHP_ADD_INCLUDE($ext_srcdir)

PHP_NEW_EXTENSION(pdo_duckdb, pdo_duckdb.c duckdb_driver.c duckdb_statement.c duckdb_stubs.cpp duckdb_extension_stub.cpp,
    $ext_shared,, -DZEND_ENABLE_STATIC_TSRMLS_CACHE=1, 1)

PHP_ADD_EXTENSION_DEP(pdo_duckdb, pdo)
PHP_ADD_MAKEFILE_FRAGMENT

dnl Link duckdb with appropriate linker flags based on platform
case $host_os in
  darwin*)
    dnl MacOS: use --whole-archive to force all symbols into the .so.
    dnl On arm64, the DuckDB static lib references __aarch64_ldadd* LSE atomic
    dnl IFUNC resolvers. The GCC driver adds -lgcc_s but not -lgcc for -shared
    dnl builds, and the resolvers are only in libgcc.a, so link it explicitly.
    PDO_DUCKDB_SHARED_LIBADD="-Wl,--whole-archive -Wl,$ext_srcdir/libduckdb_static.a -Wl,--no-whole-archive -Wl,-lstdc++ -Wl,-lc -Wl,--no-as-needed -Wl,-lgcc -Wl,--as-needed"
    ;;
  *)
    dnl Linux/other: use --whole-archive to force all symbols into the .so.
    dnl On arm64, the DuckDB static lib references __aarch64_ldadd* LSE atomic
    dnl IFUNC resolvers. The GCC driver adds -lgcc_s but not -lgcc for -shared
    dnl builds, and the resolvers are only in libgcc.a, so link it explicitly.
    #PDO_DUCKDB_SHARED_LIBADD="-Wl,--whole-archive -Wl,$ext_srcdir/libduckdb_static.a -Wl,--no-whole-archive -Wl,-lstdc++ -Wl,-lc -Wl,--no-as-needed -Wl,-lgcc -Wl,--as-needed"
    PDO_DUCKDB_SHARED_LIBADD="-Wl,--whole-archive -Wl,$ext_srcdir/libduckdb_static.a -Wl,--no-whole-archive -Wl,-lstdc++ -Wl,-lc"
    ;;
esac
PHP_SUBST(PDO_DUCKDB_SHARED_LIBADD)
