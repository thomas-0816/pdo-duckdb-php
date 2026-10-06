m4

PHP_ARG_WITH(pdo-duckdb, for DuckDB support,
[  --with-pdo-duckdb    Include DuckDB support])

PHP_REQUIRE_CXX()

PHP_CXX_COMPILE_STDCXX(17, mandatory, PDO_DUCKDB_CXX_STD)
CXXFLAGS="$CXXFLAGS $PDO_DUCKDB_CXX_STD -Wall -Wextra -Werror -Wno-unused-parameter -Wsuggest-override -Wnon-virtual-dtor -Wdouble-promotion"
CFLAGS="$CFLAGS -Wall -Wextra -Werror -Wno-unused-parameter -Wdouble-promotion"

PHP_CHECK_PDO_INCLUDES

PHP_ADD_INCLUDE($ext_srcdir)

PDO_DUCKDB_ARCH_FLAGS=""
case "$host_os" in
  linux*)
    case "$host_cpu" in
      x86_64|amd64)   PDO_DUCKDB_ARCH_FLAGS="-march=x86-64-v3" ;;
      aarch64|arm64)  PDO_DUCKDB_ARCH_FLAGS="-march=armv8-a" ;;
    esac
    ;;
  darwin*)
    case "$host_cpu" in
      aarch64|arm64) PDO_DUCKDB_ARCH_FLAGS="-Xarch_arm64 -mcpu=apple-m1" ;;
    esac
    ;;
esac

PHP_NEW_EXTENSION(pdo_duckdb, pdo_duckdb.c duckdb_driver.c duckdb_statement.c duckdb_stubs.cpp duckdb_extension_stub.cpp duckdb_swoole.cpp duckdb_backtrace_stub.cpp,
    $ext_shared,, -DZEND_ENABLE_STATIC_TSRMLS_CACHE=1 $PDO_DUCKDB_ARCH_FLAGS -g0, 1)

PHP_ADD_EXTENSION_DEP(pdo_duckdb, pdo)
PHP_ADD_MAKEFILE_FRAGMENT

dnl Bundle extensions, loaded in duckdb_extension_stub.cpp
PDO_DUCKDB_ARCHIVE_FLAGS="$PDO_DUCKDB_ARCHIVE_FLAGS -Wl,$ext_srcdir/libduckdb_static.a -Wl,$ext_srcdir/libcore_functions_extension.a -Wl,$ext_srcdir/libicu_extension.a -Wl,$ext_srcdir/libjson_extension.a"
PDO_DUCKDB_ARCHIVE_FLAGS_DARWIN="$PDO_DUCKDB_ARCHIVE_FLAGS -Wl,-force_load,$ext_srcdir/libduckdb_static.a -Wl,-force_load,$ext_srcdir/libcore_functions_extension.a -Wl,-force_load,$ext_srcdir/libicu_extension.a -Wl,-force_load,$ext_srcdir/libjson_extension.a"

dnl Link duckdb with appropriate linker flags based on platform
case $host_os in
  darwin*)
    dnl macOS: use -force_load to force all symbols into the .so (equivalent to --whole-archive).
    PDO_DUCKDB_SHARED_LIBADD="$PDO_DUCKDB_ARCHIVE_FLAGS_DARWIN -lstdc++ -lc -Wl,-bind_at_load -Wl,-undefined,dynamic_lookup -Wl,-exported_symbols_list,$ext_srcdir/macos_exported_symbols"
    ;;
  *)
    dnl Linux/other: use --whole-archive to force all symbols into the .so.
    dnl On arm64, the DuckDB static lib references __aarch64_ldadd* LSE atomic
    dnl IFUNC resolvers. The GCC driver adds -lgcc_s but not -lgcc for -shared
    dnl builds, and the resolvers are only in libgcc.a, so link it explicitly.
    PDO_DUCKDB_SHARED_LIBADD="-Wl,--whole-archive $PDO_DUCKDB_ARCHIVE_FLAGS -Wl,--no-whole-archive -Wl,-Bsymbolic-functions -Wl,-lstdc++ -Wl,-lc -Wl,--no-as-needed -Wl,-lgcc -Wl,-ldl -Wl,--as-needed -Wl,-z,relro,-z,now -Wl,-z,noexecstack"
    ;;
esac
PHP_SUBST(PDO_DUCKDB_SHARED_LIBADD)

dnl For static builds, add DuckDB libraries directly to LIBS
if test "$ext_shared" = "no"; then
  case $host_os in
    darwin*)
      LIBS="$LIBS $PDO_DUCKDB_ARCHIVE_FLAGS_DARWIN -lstdc++ -lc -Wl,-bind_at_load -Wl,-undefined,dynamic_lookup"
      ;;
    *)
      LIBS="$LIBS -Wl,--whole-archive $PDO_DUCKDB_ARCHIVE_FLAGS -Wl,--no-whole-archive -Wl,-Bsymbolic-functions -Wl,-lstdc++ -Wl,-lc -Wl,--no-as-needed -Wl,-lgcc -Wl,-ldl -Wl,--as-needed -Wl,-z,relro,-z,now -Wl,-z,noexecstack"
      ;;
  esac
fi
