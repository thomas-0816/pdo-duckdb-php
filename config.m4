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
    dnl macOS: link against libduckdb.dylib (shared library)
    PDO_DUCKDB_SHARED_LIBADD="$ext_srcdir/libduckdb.dylib -Wl,-undefined,dynamic_lookup -lc++"
    ;;
  *)
    dnl Linux/other: use --whole-archive to force all symbols into the .so
    dnl Prefer static link of libatomic for arm64 outline atomic ifunc resolvers
    dnl (e.g. __aarch64_ldadd4_rel) to avoid runtime libatomic.so.1 dependency.
    PDO_DUCKDB_SHARED_LIBADD="-Wl,--whole-archive -Wl,$ext_srcdir/libduckdb_static.a -Wl,--no-whole-archive -Wl,-lstdc++"
    LIBATOMIC_A=`$CXX -print-file-name=libatomic.a 2>/dev/null`
    AS_IF([test -f "$LIBATOMIC_A"], [
      PDO_DUCKDB_SHARED_LIBADD="$PDO_DUCKDB_SHARED_LIBADD -l:libatomic.a"
    ], [
      PDO_DUCKDB_SHARED_LIBADD="$PDO_DUCKDB_SHARED_LIBADD -latomic"
    ])
    ;;
esac
PHP_SUBST(PDO_DUCKDB_SHARED_LIBADD)
