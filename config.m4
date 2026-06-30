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
    dnl macOS: use -force_load to force all symbols into the .so (equivalent to --whole-archive).
    dnl On arm64, the DuckDB static lib references __aarch64_ldadd* LSE atomic
    dnl IFUNC resolvers. The GCC driver adds -lgcc_s but not -lgcc for -shared
    dnl builds, and the resolvers are only in libgcc.a, so link it explicitly.
    PDO_DUCKDB_SHARED_LIBADD="-Wl,-force_load,$ext_srcdir/libduckdb_static.a -lstdc++ -lc -Wl,-undefined,dynamic_lookup"
    ;;
  mingw*)
    dnl Windows/MinGW: statically link DuckDB and its extension libs.
    dnl Extract static-libs-windows-mingw.zip into $ext_srcdir to provide these.
    DUCKDB_WIN32_LIBS="$ext_srcdir/libduckdb_static.a \
      $ext_srcdir/libjson_extension.a \
      $ext_srcdir/libicu_extension.a \
      $ext_srcdir/libparquet_extension.a \
      $ext_srcdir/libautocomplete_extension.a \
      $ext_srcdir/libcore_functions_extension.a \
      $ext_srcdir/libtpch_extension.a \
      $ext_srcdir/libtpcds_extension.a \
      $ext_srcdir/libduckdb_zstd.a \
      $ext_srcdir/libduckdb_fmt.a \
      $ext_srcdir/libduckdb_mbedtls.a \
      $ext_srcdir/libduckdb_re2.a \
      $ext_srcdir/libduckdb_miniz.a \
      $ext_srcdir/libduckdb_pg_query.a \
      $ext_srcdir/libduckdb_utf8proc.a \
      $ext_srcdir/libduckdb_yyjson.a \
      $ext_srcdir/libduckdb_fastpforlib.a \
      $ext_srcdir/libduckdb_fsst.a \
      $ext_srcdir/libduckdb_hyperloglog.a \
      $ext_srcdir/libduckdb_skiplistlib.a \
      $ext_srcdir/libduckdb_generated_extension_loader.a"
    PDO_DUCKDB_SHARED_LIBADD="-Wl,--whole-archive $DUCKDB_WIN32_LIBS -Wl,--no-whole-archive -lstdc++ -lc"
    ;;
  *)
    dnl Linux/other: use --whole-archive to force all symbols into the .so.
    dnl On arm64, the DuckDB static lib references __aarch64_ldadd* LSE atomic
    dnl IFUNC resolvers. The GCC driver adds -lgcc_s but not -lgcc for -shared
    dnl builds, and the resolvers are only in libgcc.a, so link it explicitly.
    PDO_DUCKDB_SHARED_LIBADD="-Wl,--whole-archive -Wl,$ext_srcdir/libduckdb_static.a -Wl,--no-whole-archive -Wl,-lstdc++ -Wl,-lc -Wl,--no-as-needed -Wl,-lgcc -Wl,--as-needed"
    ;;
esac
PHP_SUBST(PDO_DUCKDB_SHARED_LIBADD)
