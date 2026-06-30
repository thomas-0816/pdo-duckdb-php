#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "ext/json/php_json.h"
#include "Zend/zend_smart_str.h"
#include "pdo/php_pdo.h"
#include "pdo/php_pdo_driver.h"
#include "php_pdo_duckdb.h"

/* Module globals (required by ZEND_DECLARE_MODULE_GLOBALS) */
ZEND_DECLARE_MODULE_GLOBALS(pdo_duckdb)

/* Define the TSRM cache for ZTS builds (required for dynamic extensions) */
ZEND_TSRMLS_CACHE_DEFINE()

/*
 * The forward declarations for duckdb_methods and duckdb_stmt_methods
 * are now in php_pdo_duckdb.h, so they do not appear here.
 */

/* Forward declaration (defined in duckdb_driver.c) */
int duckdb_handle_factory(pdo_dbh_t *dbh, zval *driver_options);

/* Driver definition */
static pdo_driver_t pdo_duckdb_driver = {
	PDO_DRIVER_HEADER(duckdb),
	duckdb_handle_factory
};

/* Store original PDOStatement::execute handler */
static zif_handler original_pdo_stmt_execute;

/* Override PDOStatement::execute to convert PHP arrays to JSON strings */
static void pdo_duckdb_stmt_execute_override(INTERNAL_FUNCTION_PARAMETERS)
{
	zval *params = NULL;

	if (ZEND_NUM_ARGS() == 0) {
		original_pdo_stmt_execute(INTERNAL_FUNCTION_PARAM_PASSTHRU);
		return;
	}

	if (zend_parse_parameters(1, "z", &params) == FAILURE) {
		RETURN_THROWS();
	}

	if (params && Z_TYPE_P(params) == IS_ARRAY) {
		zval new_params;
		ZVAL_ARR(&new_params, zend_array_dup(Z_ARRVAL_P(params)));

		zval *entry;
		zend_string *key;
		zend_ulong num_key;

		ZEND_HASH_FOREACH_KEY_VAL(Z_ARRVAL(new_params), num_key, key, entry) {
			if (Z_TYPE_P(entry) == IS_ARRAY || Z_TYPE_P(entry) == IS_OBJECT) {
				smart_str buf = {0};
				if (php_json_encode(&buf, entry, 0) == SUCCESS && buf.s) {
					smart_str_0(&buf);
					zval_ptr_dtor(entry);
					ZVAL_STR(entry, buf.s);
				} else {
					smart_str_free(&buf);
				}
			}
		} ZEND_HASH_FOREACH_END();

		zval *arg = ZEND_CALL_ARG(execute_data, 1);
		zval_ptr_dtor(arg);
		ZVAL_COPY(arg, &new_params);
		zval_dtor(&new_params);
	}

	original_pdo_stmt_execute(INTERNAL_FUNCTION_PARAM_PASSTHRU);
}

/* {{{ PHP_MINIT_FUNCTION */
PHP_MINIT_FUNCTION(pdo_duckdb)
{
	if (FAILURE == php_pdo_register_driver(&pdo_duckdb_driver)) {
		return FAILURE;
	}

	/* Register driver-specific class constants */
	zend_declare_class_constant_long(php_pdo_get_dbh_ce(), "DUCKDB_ATTR_UNBUFFERED", sizeof("DUCKDB_ATTR_UNBUFFERED") - 1, (zend_long)PDO_DUCKDB_ATTR_UNBUFFERED);
	zend_declare_class_constant_long(php_pdo_get_dbh_ce(), "DUCKDB_ATTR_CONFIG", sizeof("DUCKDB_ATTR_CONFIG") - 1, (zend_long)PDO_DUCKDB_ATTR_CONFIG);

	return SUCCESS;
}

/* {{{ PHP_RINIT_FUNCTION */
PHP_RINIT_FUNCTION(pdo_duckdb)
{
	ZEND_TSRMLS_CACHE_UPDATE();

	static zend_class_entry *pdo_stmt_ce = NULL;

	if (pdo_stmt_ce == NULL) {
		pdo_stmt_ce = zend_hash_str_find_ptr(CG(class_table), "pdostatement", sizeof("pdostatement") - 1);
		if (pdo_stmt_ce) {
			zend_function *func = zend_hash_str_find_ptr(&pdo_stmt_ce->function_table, "execute", sizeof("execute") - 1);
			if (func && func->internal_function.handler != (zif_handler)pdo_duckdb_stmt_execute_override) {
				original_pdo_stmt_execute = func->internal_function.handler;
				func->internal_function.handler = (zif_handler)pdo_duckdb_stmt_execute_override;
			}
		}
	}
	return SUCCESS;
}
/* }}} */
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
	PHP_RINIT(pdo_duckdb),          /* RINIT */
	NULL,                           /* RSHUTDOWN */
	PHP_MINFO(pdo_duckdb),
	PHP_PDO_DUCKDB_VERSION,
	STANDARD_MODULE_PROPERTIES
};
/* }}} */

#ifdef COMPILE_DL_PDO_DUCKDB
ZEND_GET_MODULE(pdo_duckdb)
#endif
