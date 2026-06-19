/*
  +----------------------------------------------------------------------+
  | PDO_DuckDB - DuckDB driver for PDO                                    |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2025 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author: PDO_DuckDB Contributors                                       |
  +----------------------------------------------------------------------+
 */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "pdo/php_pdo.h"
#include "pdo/php_pdo_driver.h"
#include "php_pdo_duckdb_int.h"

/* Module globals (required by ZEND_DECLARE_MODULE_GLOBALS) */
ZEND_DECLARE_MODULE_GLOBALS(pdo_duckdb)

/*
 * The forward declarations for duckdb_methods and duckdb_stmt_methods
 * are now in php_pdo_duckdb_int.h, so they do not appear here.
 */

/* Forward declaration (defined in duckdb_driver.c) */
int duckdb_handle_factory(pdo_dbh_t *dbh, zval *driver_options);

/* Driver definition */
static pdo_driver_t pdo_duckdb_driver = {
	PDO_DRIVER_HEADER(duckdb),
	duckdb_handle_factory
};

/* {{{ PHP_MINIT_FUNCTION */
PHP_MINIT_FUNCTION(pdo_duckdb)
{
	if (FAILURE == php_pdo_register_driver(&pdo_duckdb_driver)) {
		return FAILURE;
	}

	/* Register driver-specific class constants */
	zend_declare_class_constant_long(php_pdo_get_dbh_ce(), "DUCKDB_ATTR_OPEN_FLAGS", sizeof("DUCKDB_ATTR_OPEN_FLAGS") - 1, (zend_long)PDO_DUCKDB_ATTR_OPEN_FLAGS);
	zend_declare_class_constant_long(php_pdo_get_dbh_ce(), "DUCKDB_ATTR_READONLY", sizeof("DUCKDB_ATTR_READONLY") - 1, (zend_long)PDO_DUCKDB_ATTR_READONLY);

	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MSHUTDOWN_FUNCTION */
PHP_MSHUTDOWN_FUNCTION(pdo_duckdb)
{
	php_pdo_unregister_driver(&pdo_duckdb_driver);
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION */
PHP_MINFO_FUNCTION(pdo_duckdb)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "PDO Driver for DuckDB", "enabled");
	php_info_print_table_row(2, "DuckDB Library Version", duckdb_library_version());
	php_info_print_table_end();
}
/* }}} */

/* {{{ pdo_duckdb_module_entry */
zend_module_entry pdo_duckdb_module_entry = {
	STANDARD_MODULE_HEADER,
	"pdo_duckdb",
	NULL,                           /* functions */
	PHP_MINIT(pdo_duckdb),
	PHP_MSHUTDOWN(pdo_duckdb),
	NULL,                           /* RINIT */
	NULL,                           /* RSHUTDOWN */
	PHP_MINFO(pdo_duckdb),
	PHP_PDO_DUCKDB_VERSION,
	STANDARD_MODULE_PROPERTIES
};
/* }}} */

#ifdef COMPILE_DL_PDO_DUCKDB
ZEND_GET_MODULE(pdo_duckdb)
#endif
