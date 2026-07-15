m4

PHP_ARG_WITH(pdo-duckdb, for DuckDB support,
[  --with-pdo-duckdb    Include DuckDB support])

PHP_REQUIRE_CXX()

PHP_CXX_COMPILE_STDCXX(11, mandatory, PDO_DUCKDB_CXX_STD)
CXXFLAGS="$CXXFLAGS $PDO_DUCKDB_CXX_STD"

PHP_CHECK_PDO_INCLUDES

PHP_ADD_INCLUDE($ext_srcdir)

PHP_NEW_EXTENSION(pdo_duckdb, pdo_duckdb.c duckdb_driver.c duckdb_statement.c duckdb_stubs.cpp,
    $ext_shared,, -DZEND_ENABLE_STATIC_TSRMLS_CACHE=1, 1)

PHP_ADD_EXTENSION_DEP(pdo_duckdb, pdo)
PHP_ADD_MAKEFILE_FRAGMENT

duckdb_libs=`ls "$ext_srcdir"/lib*.a 2>/dev/null`

dnl Link duckdb with appropriate linker flags based on platform
case $host_os in
  darwin*)
    dnl macOS: use -force_load to force all symbols into the .so (equivalent to --whole-archive).
    dnl On arm64, the DuckDB static lib references __aarch64_ldadd* LSE atomic
    dnl IFUNC resolvers. The GCC driver adds -lgcc_s but not -lgcc for -shared
    dnl builds, and the resolvers are only in libgcc.a, so link it explicitly.
    PDO_DUCKDB_SHARED_LIBADD=""
    for lib in $duckdb_libs; do
      PDO_DUCKDB_SHARED_LIBADD="$PDO_DUCKDB_SHARED_LIBADD -Wl,-force_load,$lib"
    done
    PDO_DUCKDB_SHARED_LIBADD="$PDO_DUCKDB_SHARED_LIBADD -lc++ -Wl,-undefined,dynamic_lookup"
    ;;
  *)
    dnl Linux/other: use --whole-archive to force all symbols into the .so.
    dnl On arm64, the DuckDB static lib references __aarch64_ldadd* LSE atomic
    dnl IFUNC resolvers. The GCC driver adds -lgcc_s but not -lgcc for -shared
    dnl builds, and the resolvers are only in libgcc.a, so link it explicitly.
    PDO_DUCKDB_SHARED_LIBADD="-Wl,--whole-archive -Wl,-z,muldefs"
    for lib in $duckdb_libs; do
      PDO_DUCKDB_SHARED_LIBADD="$PDO_DUCKDB_SHARED_LIBADD -Wl,$lib"
    done
    PDO_DUCKDB_SHARED_LIBADD="$PDO_DUCKDB_SHARED_LIBADD -Wl,--no-whole-archive -Wl,-lstdc++ -Wl,-lc -Wl,--no-as-needed -Wl,-lgcc -Wl,--as-needed"
    ;;
esac
PHP_SUBST(PDO_DUCKDB_SHARED_LIBADD)

dnl For static builds, add DuckDB libraries directly to LIBS
if test "$ext_shared" = "no"; then
  case $host_os in
    darwin*)
      for lib in $duckdb_libs; do
        LIBS="$LIBS -Wl,-force_load,$lib"
      done
      LIBS="$LIBS -lc++ -Wl,-undefined,dynamic_lookup"
      ;;
    *)
      LIBS="$LIBS -Wl,--whole-archive -Wl,-z,muldefs"
      for lib in $duckdb_libs; do
        LIBS="$LIBS -Wl,$lib"
      done
      LIBS="$LIBS -Wl,--no-whole-archive -Wl,-lstdc++ -Wl,-lc -Wl,--no-as-needed -Wl,-lgcc -Wl,--as-needed"
      ;;
  esac
fi
