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

/* Helper: fetch the next data chunk (handles both streaming and non-streaming) */
static int fetch_next_chunk(pdo_duckdb_stmt *S)
{
	duckdb_result *res = &S->result;

	if (S->is_streaming) {
		/* For streaming results, retrieve the next chunk and advance */
		S->chunk = duckdb_fetch_chunk(*res);
	} else {
		/* Non-streaming: access the chunk at current index, then increment */
		if (S->next_chunk_index >= duckdb_result_chunk_count(*res)) {
			S->chunk = NULL;
		} else {
			S->chunk = duckdb_result_get_chunk(*res, S->next_chunk_index++);
		}
	}

	if (S->chunk == NULL) {
		S->chunk_size = 0;
		S->done = 1;
		return 0;
	}

	S->chunk_size = duckdb_data_chunk_get_size(S->chunk);
	S->chunk_idx = 0;
	return 1;
}

/* ---------------- statement execute ---------------- */
static int duckdb_stmt_execute(pdo_stmt_t *stmt)
{
	pdo_duckdb_stmt *S = (pdo_duckdb_stmt *) stmt->driver_data;
	pdo_duckdb_db_handle *H = (pdo_duckdb_db_handle *) stmt->dbh->driver_data;

	/* Parameter binding already done by param_hook before this call */

	duckdb_state state = duckdb_execute_prepared(S->stmt, &S->result);
	if (state != DuckDBSuccess) {
		const char *err = duckdb_result_error(&S->result);
		strncpy(H->error_msg, err ? err : "execute error", sizeof(H->error_msg) - 1);
		return 0;
	}

	S->result_set = 1;
	S->done = 0;
	S->is_streaming = duckdb_result_is_streaming(S->result);
	S->next_chunk_index = 0;
	S->chunk = NULL;
	S->chunk_idx = 0;
	S->chunk_size = 0;

	/* For statements that return a result set (SELECT, etc.), set column count.
	   For INSERT/UPDATE, there are no columns and we are done immediately. */
	stmt->column_count = duckdb_column_count(&S->result);
	if (stmt->column_count == 0) {
		S->done = 1;
	}

	stmt->row_count = 0;               /* number of rows affected (unknown for SELECT) */
	return 1;
}

/* ---------------- get column data (called by PDO after fetch) ---------------- */
static int duckdb_stmt_get_col(pdo_stmt_t *stmt, int colno, zval *result, enum pdo_param_type *type)
{
	pdo_duckdb_stmt *S = (pdo_duckdb_stmt *) stmt->driver_data;
	duckdb_result *res = &S->result;
	idx_t row_idx = S->chunk_idx;

	duckdb_vector vec = duckdb_data_chunk_get_vector(S->chunk, colno);
	uint64_t *validity = duckdb_vector_get_validity(vec);
	bool is_null = !duckdb_validity_row_is_valid(validity, row_idx);

	if (is_null) {
		ZVAL_NULL(result);
		return 1;
	}

	duckdb_type col_type = duckdb_column_type(res, colno);
	switch (col_type) {
		case DUCKDB_TYPE_BOOLEAN: {
			bool val = ((bool *)duckdb_vector_get_data(vec))[row_idx];
			ZVAL_BOOL(result, val);
			break;
		}
		case DUCKDB_TYPE_TINYINT:
		case DUCKDB_TYPE_SMALLINT:
		case DUCKDB_TYPE_INTEGER: {
			int32_t val = ((int32_t *)duckdb_vector_get_data(vec))[row_idx];
			ZVAL_LONG(result, (zend_long)val);
			break;
		}
		case DUCKDB_TYPE_BIGINT: {
			int64_t val = ((int64_t *)duckdb_vector_get_data(vec))[row_idx];
			ZVAL_LONG(result, (zend_long)val);
			break;
		}
		case DUCKDB_TYPE_FLOAT: {
			float val = ((float *)duckdb_vector_get_data(vec))[row_idx];
			ZVAL_DOUBLE(result, (double)val);
			break;
		}
		case DUCKDB_TYPE_DOUBLE: {
			double val = ((double *)duckdb_vector_get_data(vec))[row_idx];
			ZVAL_DOUBLE(result, val);
			break;
		}
		case DUCKDB_TYPE_VARCHAR: {
			duckdb_string_t str = ((duckdb_string_t *)duckdb_vector_get_data(vec))[row_idx];
			ZVAL_STRINGL(result, duckdb_string_t_data(&str), duckdb_string_t_length(str));
			break;
		}
		case DUCKDB_TYPE_BLOB: {
			duckdb_string_t blob = ((duckdb_string_t *)duckdb_vector_get_data(vec))[row_idx];
			ZVAL_STRINGL(result, duckdb_string_t_data(&blob), duckdb_string_t_length(blob));
			break;
		}
		case DUCKDB_TYPE_DATE: {
			duckdb_date date = ((duckdb_date *)duckdb_vector_get_data(vec))[row_idx];
			duckdb_date_struct ds = duckdb_from_date(date);
			char buf[11];
			snprintf(buf, sizeof(buf), "%04d-%02d-%02d", ds.year, ds.month, ds.day);
			ZVAL_STRING(result, buf);
			break;
		}
		case DUCKDB_TYPE_TIME: {
			duckdb_time time_val = ((duckdb_time *)duckdb_vector_get_data(vec))[row_idx];
			duckdb_time_struct ts = duckdb_from_time(time_val);
			char buf[16];
			snprintf(buf, sizeof(buf), "%02d:%02d:%02d", ts.hour, ts.min, ts.sec);
			ZVAL_STRING(result, buf);
			break;
		}
		case DUCKDB_TYPE_TIMESTAMP: {
			duckdb_timestamp ts = ((duckdb_timestamp *)duckdb_vector_get_data(vec))[row_idx];
			duckdb_timestamp_struct tss = duckdb_from_timestamp(ts);
			char buf[32];
			snprintf(buf, sizeof(buf), "%04d-%02d-%02d %02d:%02d:%02d",
			         tss.date.year, tss.date.month, tss.date.day,
			         tss.time.hour, tss.time.min, tss.time.sec);
			ZVAL_STRING(result, buf);
			break;
		}
		default: {
			duckdb_string_t str = ((duckdb_string_t *)duckdb_vector_get_data(vec))[row_idx];
			ZVAL_STRINGL(result, duckdb_string_t_data(&str), duckdb_string_t_length(str));
			break;
		}
	}

	return 1;
}

/* ---------------- statement fetch (single row) ---------------- */
static int duckdb_stmt_fetch(pdo_stmt_t *stmt, enum pdo_fetch_orientation ori, zend_long offset)
{
	pdo_duckdb_stmt *S = (pdo_duckdb_stmt *) stmt->driver_data;

	if (!S->result_set || S->done) {
		return 0;
	}

	/* Advance to next row */
	S->chunk_idx++;

	/* If the current chunk is exhausted, try to load the next one */
	if (S->chunk == NULL || S->chunk_idx >= S->chunk_size) {
		if (!fetch_next_chunk(S)) {
			return 0;   /* no more rows */
		}
	}

	return 1;
}

/* ---------------- describe column (called by PDO after execute) ---------------- */
static int duckdb_stmt_describe_col(pdo_stmt_t *stmt, int colno)
{
	pdo_duckdb_stmt *S = (pdo_duckdb_stmt *) stmt->driver_data;
	duckdb_result *res = &S->result;
	const char *name = duckdb_column_name(res, colno);
	duckdb_type type = duckdb_column_type(res, colno);

	/* Allocate and set column name */
	if (stmt->columns[colno].name == NULL) {
		stmt->columns[colno].name = zend_string_init(name, strlen(name), 0);
	} else {
		zend_string_release(stmt->columns[colno].name);
		stmt->columns[colno].name = zend_string_init(name, strlen(name), 0);
	}

	/* Set reasonable defaults */
	stmt->columns[colno].maxlen = 0;
	stmt->columns[colno].precision = 0;

	return 1;
}

/* ---------------- get column meta (optional) ---------------- */
static int duckdb_stmt_get_col_meta(pdo_stmt_t *stmt, zend_long colno, zval *return_value)
{
	pdo_duckdb_stmt *S = (pdo_duckdb_stmt *) stmt->driver_data;
	duckdb_result *res = &S->result;
	if (colno < 0 || colno >= stmt->column_count) {
		return FAILURE;
	}

	array_init(return_value);

	duckdb_type type = duckdb_column_type(res, colno);
	const char *type_str = NULL;
	switch (type) {
		case DUCKDB_TYPE_BOOLEAN: type_str = "boolean"; break;
		case DUCKDB_TYPE_TINYINT: type_str = "tinyint"; break;
		case DUCKDB_TYPE_SMALLINT: type_str = "smallint"; break;
		case DUCKDB_TYPE_INTEGER: type_str = "integer"; break;
		case DUCKDB_TYPE_BIGINT: type_str = "bigint"; break;
		case DUCKDB_TYPE_FLOAT: type_str = "float"; break;
		case DUCKDB_TYPE_DOUBLE: type_str = "double"; break;
		case DUCKDB_TYPE_VARCHAR: type_str = "varchar"; break;
		case DUCKDB_TYPE_BLOB: type_str = "blob"; break;
		case DUCKDB_TYPE_DATE: type_str = "date"; break;
		case DUCKDB_TYPE_TIME: type_str = "time"; break;
		case DUCKDB_TYPE_TIMESTAMP: type_str = "timestamp"; break;
		default: type_str = "unknown";
	}

	add_assoc_string(return_value, "native_type", (char *)type_str);
	add_assoc_string(return_value, "driver:decl_type", (char *)type_str);
	add_assoc_string(return_value, "name", (char *)duckdb_column_name(res, colno));
	add_assoc_long(return_value, "len", 0);
	add_assoc_long(return_value, "precision", 0);
	add_assoc_long(return_value, "pdo_type", PDO_PARAM_STR);

	return SUCCESS;
}

/* ---------------- parameter hook (binding before execution) ---------------- */
static int duckdb_stmt_param_hook(pdo_stmt_t *stmt, struct pdo_bound_param_data *param,
                                   enum pdo_param_event event_type)
{
	pdo_duckdb_stmt *S = (pdo_duckdb_stmt *) stmt->driver_data;

	if (event_type == PDO_PARAM_EVT_EXEC_PRE) {
		duckdb_state state = DuckDBError;
		/* param->paramno is 0-based in PDO, but DuckDB expects 1-based index */
		idx_t idx = param->paramno + 1;

		switch (PDO_PARAM_TYPE(param->param_type)) {
			case PDO_PARAM_NULL:
				state = duckdb_bind_null(S->stmt, idx);
				break;
			case PDO_PARAM_BOOL:
				state = duckdb_bind_boolean(S->stmt, idx, zval_is_true(&param->parameter) ? 1 : 0);
				break;
			case PDO_PARAM_INT:
				state = duckdb_bind_int32(S->stmt, idx, (int32_t)zval_get_long(&param->parameter));
				break;
			case PDO_PARAM_STR: {
				zend_string *str = zval_get_string(&param->parameter);
				state = duckdb_bind_varchar_length(S->stmt, idx, ZSTR_VAL(str), ZSTR_LEN(str));
				zend_string_release(str);
				break;
			}
			case PDO_PARAM_LOB: {
				zend_string *str = zval_get_string(&param->parameter);
				state = duckdb_bind_blob(S->stmt, idx, ZSTR_VAL(str), ZSTR_LEN(str));
				zend_string_release(str);
				break;
			}
			default:
				{
					zend_string *str = zval_get_string(&param->parameter);
					state = duckdb_bind_varchar_length(S->stmt, idx, ZSTR_VAL(str), ZSTR_LEN(str));
					zend_string_release(str);
				}
				break;
		}

		if (state != DuckDBSuccess) {
			pdo_duckdb_db_handle *H = (pdo_duckdb_db_handle *) stmt->dbh->driver_data;
			strncpy(H->error_msg, "parameter binding failed", sizeof(H->error_msg) - 1);
			return 0;
		}
	}

	return 1;
}

/* ---------------- cursor closer (free result & prepared statement) ---------------- */
static int duckdb_stmt_cursor_closer(pdo_stmt_t *stmt)
{
	pdo_duckdb_stmt *S = (pdo_duckdb_stmt *) stmt->driver_data;
	if (S) {
		if (S->result_set) {
			/* Destroy data chunks? The API says we must destroy only the result,
			   and the chunks are owned by the result. */
			duckdb_destroy_result(&S->result);
			S->result_set = 0;
		}
		if (S->chunk) {
			/* Not owned separately, just nullify */
			S->chunk = NULL;
		}
		if (S->stmt) {
			duckdb_destroy_prepare(&S->stmt);
			S->stmt = NULL;
		}
		efree(S);
		stmt->driver_data = NULL;
	}
	return 1;
}

/* ---------------- statement method table ---------------- */
struct pdo_stmt_methods duckdb_stmt_methods = {
	NULL,                         /* dtor */
	duckdb_stmt_execute,          /* executer */
	duckdb_stmt_fetch,            /* fetcher */
	duckdb_stmt_describe_col,     /* describer */
	duckdb_stmt_get_col,          /* get_col */
	duckdb_stmt_param_hook,       /* param_hook */
	NULL,                         /* set_attribute */
	NULL,                         /* get_attribute */
	duckdb_stmt_get_col_meta,     /* get_column_meta */
	NULL,                         /* next_rowset */
	duckdb_stmt_cursor_closer     /* cursor_closer */
};
