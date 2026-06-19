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
#include "Zend/zend_exceptions.h"
#include "php_pdo_duckdb_int.h"

/* Forward declaration of statement methods (defined in duckdb_statement.c) */
extern struct pdo_stmt_methods duckdb_stmt_methods;

/* ---------------- error handling helper ---------------- */
static void pdo_duckdb_error(pdo_dbh_t *dbh)
{
	pdo_duckdb_db_handle *H = (pdo_duckdb_db_handle *) dbh->driver_data;
	/* DuckDB doesn't have per-connection error retrieval, so we pull
	   from the last operation if we stored it. In practice we’d keep
	   a buffer in H. For simplicity, we report a generic error. */
	strncpy(H->error_msg, "unknown error", sizeof(H->error_msg) - 1);
	memcpy(dbh->error_code, "HY000", sizeof(pdo_error_type));
}

/* ---------------- connection factory ---------------- */
int duckdb_handle_factory(pdo_dbh_t *dbh, zval *driver_options)
{
	pdo_duckdb_db_handle *H;
	const char *data_source = dbh->data_source;
	char *dbname = NULL;
	duckdb_state state;

	H = ecalloc(1, sizeof(pdo_duckdb_db_handle));
	H->error_msg[0] = '\0';
	dbh->driver_data = H;

	/* Extract path — PDO passes the part after the first colon */
	dbname = estrdup(data_source);
	if (strcmp(dbname, ":memory:") == 0) {
		/* in‑memory database */
		state = duckdb_open(NULL, &H->db);
	} else {
		state = duckdb_open(dbname, &H->db);
	}
	efree(dbname);

	if (state != DuckDBSuccess) {
		zend_throw_exception_ex(php_pdo_get_exception(), 0,
			"Could not open DuckDB database");
		efree(H);
		return 0;
	}

	if (duckdb_connect(H->db, &H->conn) != DuckDBSuccess) {
		duckdb_close(&H->db);
		efree(H);
		zend_throw_exception_ex(php_pdo_get_exception(), 0,
			"Could not create DuckDB connection");
		return 0;
	}

	/* Assign db handle methods */
	dbh->methods = &duckdb_methods;

	/* Process driver options (if any) */
	if (driver_options) {
		/* e.g., access mode, threads, etc. */
		/* For now a minimal example: if PDO::ATTR_AUTOCOMMIT is passed,
		   we do nothing because DuckDB is always autocommit. */
	}

	return 1;
}

/* ---------------- connection closer ---------------- */
static void duckdb_handle_closer(pdo_dbh_t *dbh)
{
	pdo_duckdb_db_handle *H = (pdo_duckdb_db_handle *) dbh->driver_data;
	if (H) {
		if (H->conn) duckdb_disconnect(&H->conn);
		if (H->db) duckdb_close(&H->db);
		efree(H);
		dbh->driver_data = NULL;
	}
}

/* ---------------- preparer ---------------- */
static bool duckdb_handle_preparer(pdo_dbh_t *dbh, zend_string *sql,
                                  pdo_stmt_t *stmt, zval *driver_options)
{
	pdo_duckdb_db_handle *H = (pdo_duckdb_db_handle *) dbh->driver_data;
	pdo_duckdb_stmt *S;

	S = ecalloc(1, sizeof(pdo_duckdb_stmt));
	stmt->driver_data = S;
	S->stmt = NULL;
	S->result_set = 0;
	S->done = 0;
	S->chunk = NULL;
	S->chunk_idx = 0;
	S->chunk_size = 0;

	duckdb_state state = duckdb_prepare(H->conn, ZSTR_VAL(sql), &S->stmt);
	if (state != DuckDBSuccess) {
		const char *err = duckdb_prepare_error(S->stmt);
		zend_throw_exception_ex(php_pdo_get_exception(), 0,
			"SQLSTATE[HY000]: %s", err ? err : "prepare error");
		duckdb_destroy_prepare(&S->stmt);
		efree(S);
		return false;
	}

	stmt->methods = &duckdb_stmt_methods;
	stmt->supports_placeholders = PDO_PLACEHOLDER_POSITIONAL | PDO_PLACEHOLDER_NAMED;
	return true;
}

/* ---------------- exec (direct, non‑prepared) ---------------- */
static zend_long duckdb_handle_doer(pdo_dbh_t *dbh, const zend_string *sql)
{
	pdo_duckdb_db_handle *H = (pdo_duckdb_db_handle *) dbh->driver_data;
	duckdb_result result;
	duckdb_state state = duckdb_query(H->conn, ZSTR_VAL(sql), &result);
	if (state != DuckDBSuccess) {
		const char *err = duckdb_result_error(&result);
		zend_throw_exception_ex(php_pdo_get_exception(), 0,
			"SQLSTATE[HY000]: %s", err ? err : "query error");
		duckdb_destroy_result(&result);
		return -1;
	}
	idx_t rows = duckdb_rows_changed(&result);
	duckdb_destroy_result(&result);
	return (zend_long) rows;
}

/* ---------------- quoter ---------------- */
static zend_string* duckdb_handle_quoter(pdo_dbh_t *dbh, const zend_string *unquoted, enum pdo_param_type param_type)
{
	zend_string *q = zend_string_alloc(ZSTR_LEN(unquoted) * 2 + 3, 0);
	char *p = ZSTR_VAL(q);
	size_t i, j = 1;
	p[0] = '\'';
	for (i = 0; i < ZSTR_LEN(unquoted); i++) {
		if (ZSTR_VAL(unquoted)[i] == '\'') {
			p[j++] = '\'';
		}
		p[j++] = ZSTR_VAL(unquoted)[i];
	}
	p[j++] = '\'';
	p[j] = '\0';
	ZSTR_LEN(q) = j;
	return q;
}

/* ---------------- transaction support (via SQL commands) ---------------- */
static bool duckdb_handle_begin(pdo_dbh_t *dbh)
{
	pdo_duckdb_db_handle *H = (pdo_duckdb_db_handle *) dbh->driver_data;
	duckdb_result res;
	duckdb_state st = duckdb_query(H->conn, "BEGIN TRANSACTION", &res);
	duckdb_destroy_result(&res);
	if (st != DuckDBSuccess) {
		pdo_duckdb_error(dbh);
		return false;
	}
	return true;
}

static bool duckdb_handle_commit(pdo_dbh_t *dbh)
{
	pdo_duckdb_db_handle *H = (pdo_duckdb_db_handle *) dbh->driver_data;
	duckdb_result res;
	duckdb_state st = duckdb_query(H->conn, "COMMIT", &res);
	duckdb_destroy_result(&res);
	if (st != DuckDBSuccess) {
		pdo_duckdb_error(dbh);
		return false;
	}
	return true;
}

static bool duckdb_handle_rollback(pdo_dbh_t *dbh)
{
	pdo_duckdb_db_handle *H = (pdo_duckdb_db_handle *) dbh->driver_data;
	duckdb_result res;
	duckdb_state st = duckdb_query(H->conn, "ROLLBACK", &res);
	duckdb_destroy_result(&res);
	if (st != DuckDBSuccess) {
		pdo_duckdb_error(dbh);
		return false;
	}
	return true;
}

/* ---------------- last insert id ---------------- */
static zend_string* duckdb_handle_last_id(pdo_dbh_t *dbh, const zend_string *name)
{
	/* DuckDB doesn't maintain a global last rowid; we can use sequences
	   if the user creates them. This is a placeholder. */
	return zend_string_init("0", 1, 0);
}

/* ---------------- fetch error information ---------------- */
static void duckdb_fetch_error(pdo_dbh_t *dbh, pdo_stmt_t *stmt, zval *info)
{
	pdo_duckdb_db_handle *H = (pdo_duckdb_db_handle *) dbh->driver_data;
	if (H == NULL || H->error_msg[0] == '\0') {
		add_next_index_string(info, "00000");
		add_next_index_null(info);
		add_next_index_string(info, "no error");
	} else {
		add_next_index_string(info, dbh->error_code ? dbh->error_code : "HY000");
		add_next_index_null(info);
		add_next_index_string(info, H->error_msg);
	}
}

/* ---------------- set/get driver attributes ---------------- */
static bool duckdb_set_attribute(pdo_dbh_t *dbh, zend_long attr, zval *val)
{
	pdo_duckdb_db_handle *H = (pdo_duckdb_db_handle *) dbh->driver_data;

	switch (attr) {
		case PDO_ATTR_AUTOCOMMIT:
			H->auto_commit = zval_get_long(val) ? 1 : 0;
			return true;
		default:
			return false;
	}
}

static int duckdb_get_attribute(pdo_dbh_t *dbh, zend_long attr, zval *val)
{
	pdo_duckdb_db_handle *H = (pdo_duckdb_db_handle *) dbh->driver_data;

	switch (attr) {
		case PDO_ATTR_CLIENT_VERSION:
			ZVAL_STRING(val, duckdb_library_version());
			return 1;
		case PDO_ATTR_AUTOCOMMIT:
			ZVAL_LONG(val, H->auto_commit);
			return 1;
		default:
			return 0;
	}
	return 0;
}

/* ---------------- check liveness ---------------- */
static int duckdb_check_liveness(pdo_dbh_t *dbh)
{
	pdo_duckdb_db_handle *H = (pdo_duckdb_db_handle *) dbh->driver_data;
	/* A simple query to test connection */
	duckdb_result res;
	duckdb_state st = duckdb_query(H->conn, "SELECT 1", &res);
	if (st != DuckDBSuccess) {
		duckdb_destroy_result(&res);
		return FAILURE;
	}
	duckdb_destroy_result(&res);
	return SUCCESS;
}

/* ---------------- driver method table ---------------- */
struct pdo_dbh_methods duckdb_methods = {
	duckdb_handle_closer,        /* closer */
	duckdb_handle_preparer,      /* preparer */
	duckdb_handle_doer,          /* doer */
	duckdb_handle_quoter,        /* quoter */
	duckdb_handle_begin,         /* begin */
	duckdb_handle_commit,        /* commit */
	duckdb_handle_rollback,      /* rollback */
	duckdb_set_attribute,        /* set_attribute */
	duckdb_handle_last_id,       /* last_id */
	duckdb_fetch_error,          /* fetch_err */
	duckdb_get_attribute,        /* get_attribute */
	duckdb_check_liveness,       /* check_liveness */
	NULL,                         /* get_driver_methods */
	NULL,                         /* persistent_shutdown */
	NULL,                         /* in_transaction */
	NULL,                         /* get_gc */
	NULL                          /* scanner */
};
