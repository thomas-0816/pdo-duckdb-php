#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "ext/pdo/php_pdo.h"
#include "ext/pdo/php_pdo_driver.h"
#include "Zend/zend_exceptions.h"
#include "php_pdo_duckdb.h"
#include <math.h>
#include "ext/json/php_json.h"
#include "Zend/zend_smart_str.h"

/* Helper: fetch the next data chunk (handles both streaming and non-streaming) */
static int fetch_next_chunk(pdo_duckdb_stmt *S)
{
	duckdb_result *res = &S->result;

	/* Accumulate total rows consumed before replacing the current chunk */
	if (S->chunk) {
		S->total_rows += S->chunk_size;
		duckdb_destroy_data_chunk(&S->chunk);
		S->chunk = NULL;
	}

	if (S->is_streaming) {
		S->chunk = duckdb_fetch_chunk(*res);
	} else {
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

	if (S->result_set) {
		duckdb_destroy_result(&S->result);
		S->result_set = 0;
	}
	if (S->chunk) {
		duckdb_destroy_data_chunk(&S->chunk);
		S->chunk = NULL;
	}

	duckdb_state state;
	if (H->unbuffered) {
		state = duckdb_execute_prepared_streaming(S->stmt, &S->result);
	} else {
		state = duckdb_execute_prepared(S->stmt, &S->result);
	}
	if (state != DuckDBSuccess) {
		const char *err = duckdb_result_error(&S->result);
		zend_throw_exception_ex(php_pdo_get_exception(), 0,
			"SQLSTATE[HY000]: %s", err ? err : "execute error");
		duckdb_destroy_result(&S->result);
		return 0;
	}

	S->result_set = 1;
	S->done = 0;
	S->is_streaming = duckdb_result_is_streaming(S->result);
	S->next_chunk_index = 0;
	S->chunk = NULL;
	S->chunk_idx = 0;
	S->chunk_size = 0;
	S->total_rows = 0;

	/* For statements that return a result set (SELECT, etc.), set column count.
	   For INSERT/UPDATE, there are no columns and we are done immediately. */
	stmt->column_count = duckdb_column_count(&S->result);
	if (stmt->column_count == 0) {
		S->done = 1;
	}

	stmt->row_count = duckdb_rows_changed(&S->result);
	return 1;
}

/* Convert a zval to a string representation for use as a MAP key.
   Arrays are serialized by joining their elements with ", ". */
static zend_string *zval_to_map_key(zval *zv)
{
	if (Z_TYPE_P(zv) != IS_ARRAY) {
		return zval_get_string(zv);
	}

	zend_string *result = NULL;
	zval *val;

	ZEND_HASH_FOREACH_VAL(Z_ARRVAL_P(zv), val) {
		zend_string *part = Z_TYPE_P(val) == IS_ARRAY
			? zval_to_map_key(val)
			: zval_get_string(val);

		if (result == NULL) {
			result = part;
		} else {
			zend_string *prev = result;
			result = zend_string_concat3(
				ZSTR_VAL(prev), ZSTR_LEN(prev),
				", ", 2,
				ZSTR_VAL(part), ZSTR_LEN(part)
			);
			zend_string_release(prev);
			zend_string_release(part);
		}
	} ZEND_HASH_FOREACH_END();

	if (result == NULL) {
		result = zend_string_init("", 0, 0);
	}

	return result;
}

/* Recursively convert a value from a DuckDB vector to a PHP zval.
   The logical_type is used to determine the type and to access child types
   for nested/complex types (struct, list, map). */
static void duckdb_val_from_vector(duckdb_connection conn, duckdb_vector vec, duckdb_logical_type logical_type, idx_t row_idx, zval *result)
{
	duckdb_type col_type = duckdb_get_type_id(logical_type);
	uint64_t *validity = duckdb_vector_get_validity(vec);

	if (!duckdb_validity_row_is_valid(validity, row_idx)) {
		ZVAL_NULL(result);
		return;
	}

	switch (col_type) {
		case DUCKDB_TYPE_INVALID:
		case DUCKDB_TYPE_ANY: {
			ZVAL_NULL(result);
			break;
		}
		case DUCKDB_TYPE_BOOLEAN: {
			bool val = ((bool *)duckdb_vector_get_data(vec))[row_idx];
			ZVAL_BOOL(result, val);
			break;
		}
		case DUCKDB_TYPE_TINYINT: {
			int8_t val = ((int8_t *)duckdb_vector_get_data(vec))[row_idx];
			ZVAL_LONG(result, (zend_long)val);
			break;
		}
		case DUCKDB_TYPE_SMALLINT: {
			int16_t val = ((int16_t *)duckdb_vector_get_data(vec))[row_idx];
			ZVAL_LONG(result, (zend_long)val);
			break;
		}
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
		case DUCKDB_TYPE_UTINYINT: {
			uint8_t val = ((uint8_t *)duckdb_vector_get_data(vec))[row_idx];
			ZVAL_LONG(result, (zend_long)val);
			break;
		}
		case DUCKDB_TYPE_USMALLINT: {
			uint16_t val = ((uint16_t *)duckdb_vector_get_data(vec))[row_idx];
			ZVAL_LONG(result, (zend_long)val);
			break;
		}
		case DUCKDB_TYPE_UINTEGER: {
			uint32_t val = ((uint32_t *)duckdb_vector_get_data(vec))[row_idx];
			ZVAL_LONG(result, (zend_long)val);
			break;
		}
		case DUCKDB_TYPE_UBIGINT: {
			uint64_t val = ((uint64_t *)duckdb_vector_get_data(vec))[row_idx];
			if (val > (uint64_t)ZEND_LONG_MAX) {
				char buf[24];
				snprintf(buf, sizeof(buf), "%" PRIu64, val);
				ZVAL_STRING(result, buf);
			} else {
				ZVAL_LONG(result, (zend_long)val);
			}
			break;
		}
		case DUCKDB_TYPE_FLOAT: {
			float val = ((float *)duckdb_vector_get_data(vec))[row_idx];
			if (isnan(val)) {
				ZVAL_DOUBLE(result, NAN);
			} else if (isinf(val)) {
				ZVAL_DOUBLE(result, val > 0 ? INFINITY : -INFINITY);
			} else {
				char buf[32];
				snprintf(buf, sizeof(buf), "%.7g", (double)val);
				ZVAL_DOUBLE(result, zend_strtod(buf, NULL));
			}
			break;
		}
		case DUCKDB_TYPE_DOUBLE: {
			double val = ((double *)duckdb_vector_get_data(vec))[row_idx];
			if (isnan(val)) {
				ZVAL_DOUBLE(result, NAN);
			} else if (isinf(val)) {
				ZVAL_DOUBLE(result, val > 0 ? INFINITY : -INFINITY);
			} else {
				ZVAL_DOUBLE(result, val);
			}
			break;
		}
		case DUCKDB_TYPE_VARCHAR: {
			char *alias = duckdb_logical_type_get_alias(logical_type);
			int is_json = (alias && strcmp(alias, "JSON") == 0);
			duckdb_free(alias);
			if (is_json) {
				duckdb_string_t str = ((duckdb_string_t *)duckdb_vector_get_data(vec))[row_idx];
				const char *str_data = duckdb_string_t_data(&str);
				size_t str_len = duckdb_string_t_length(str);
				char *json_copy = emalloc(str_len + 1);
				memcpy(json_copy, str_data, str_len);
				json_copy[str_len] = '\0';
				if (php_json_decode_ex(result, json_copy, str_len, PHP_JSON_OBJECT_AS_ARRAY | PHP_JSON_BIGINT_AS_STRING, 512) != SUCCESS) {
					ZVAL_STRINGL(result, json_copy, str_len);
				}
				efree(json_copy);
			} else {
				duckdb_string_t str = ((duckdb_string_t *)duckdb_vector_get_data(vec))[row_idx];
				ZVAL_STRINGL(result, duckdb_string_t_data(&str), duckdb_string_t_length(str));
			}
			break;
		}
		case DUCKDB_TYPE_BLOB: {
			duckdb_string_t blob = ((duckdb_string_t *)duckdb_vector_get_data(vec))[row_idx];
			ZVAL_STRINGL(result, duckdb_string_t_data(&blob), duckdb_string_t_length(blob));
			break;
		}
		case DUCKDB_TYPE_DECIMAL: {
			uint8_t width = duckdb_decimal_width(logical_type);
			uint8_t scale = duckdb_decimal_scale(logical_type);
			duckdb_type internal_type = duckdb_decimal_internal_type(logical_type);

			duckdb_hugeint hugeint_val;
			switch (internal_type) {
				case DUCKDB_TYPE_SMALLINT: {
					int16_t val = ((int16_t *)duckdb_vector_get_data(vec))[row_idx];
					hugeint_val.lower = (uint64_t)(int64_t)val;
					hugeint_val.upper = val < 0 ? -1 : 0;
					break;
				}
				case DUCKDB_TYPE_INTEGER: {
					int32_t val = ((int32_t *)duckdb_vector_get_data(vec))[row_idx];
					hugeint_val.lower = (uint64_t)(int64_t)val;
					hugeint_val.upper = val < 0 ? -1 : 0;
					break;
				}
				case DUCKDB_TYPE_BIGINT: {
					int64_t val = ((int64_t *)duckdb_vector_get_data(vec))[row_idx];
					hugeint_val.lower = (uint64_t)val;
					hugeint_val.upper = val < 0 ? -1 : 0;
					break;
				}
				default: {
					hugeint_val = ((duckdb_hugeint *)duckdb_vector_get_data(vec))[row_idx];
					break;
				}
			}

			duckdb_decimal dec_val;
			dec_val.width = width;
			dec_val.scale = scale;
			dec_val.value = hugeint_val;

			ZVAL_DOUBLE(result, duckdb_decimal_to_double(dec_val));
			break;
		}
		case DUCKDB_TYPE_LIST: {
			duckdb_list_entry entry = ((duckdb_list_entry *)duckdb_vector_get_data(vec))[row_idx];
			duckdb_vector child_vec = duckdb_list_vector_get_child(vec);
			duckdb_logical_type child_type = duckdb_list_type_child_type(logical_type);

			array_init(result);
			for (idx_t i = entry.offset; i < entry.offset + entry.length; i++) {
				zval child_val;
				duckdb_val_from_vector(conn, child_vec, child_type, i, &child_val);
				add_next_index_zval(result, &child_val);
			}

			duckdb_destroy_logical_type(&child_type);
			break;
		}
		case DUCKDB_TYPE_STRUCT: {
			idx_t child_count = duckdb_struct_type_child_count(logical_type);

			array_init(result);
			for (idx_t i = 0; i < child_count; i++) {
				const char *name = duckdb_struct_type_child_name(logical_type, i);
				duckdb_logical_type child_type = duckdb_struct_type_child_type(logical_type, i);
				duckdb_vector child_vec = duckdb_struct_vector_get_child(vec, i);

				zval child_val;
				duckdb_val_from_vector(conn, child_vec, child_type, row_idx, &child_val);
				if (name && name[0]) {
					add_assoc_zval(result, name, &child_val);
				} else {
					add_next_index_zval(result, &child_val);
				}

				duckdb_free((void *)name);
				duckdb_destroy_logical_type(&child_type);
			}
			break;
		}
		case DUCKDB_TYPE_MAP: {
			duckdb_list_entry entry = ((duckdb_list_entry *)duckdb_vector_get_data(vec))[row_idx];
			duckdb_vector list_child_vec = duckdb_list_vector_get_child(vec);

			duckdb_logical_type key_type = duckdb_map_type_key_type(logical_type);
			duckdb_logical_type val_type = duckdb_map_type_value_type(logical_type);
			duckdb_vector key_vec = duckdb_struct_vector_get_child(list_child_vec, 0);
			duckdb_vector val_vec = duckdb_struct_vector_get_child(list_child_vec, 1);

			array_init(result);
			for (idx_t i = entry.offset; i < entry.offset + entry.length; i++) {
				zval key_val, val_val;
				duckdb_val_from_vector(conn, key_vec, key_type, i, &key_val);
				duckdb_val_from_vector(conn, val_vec, val_type, i, &val_val);

				zend_string *key_str = zval_to_map_key(&key_val);
				add_assoc_zval(result, ZSTR_VAL(key_str), &val_val);
				zend_string_release(key_str);
				zval_ptr_dtor(&key_val);
			}

			duckdb_destroy_logical_type(&key_type);
			duckdb_destroy_logical_type(&val_type);
			break;
		}
		case DUCKDB_TYPE_UNION: {
			duckdb_vector tag_vec = duckdb_struct_vector_get_child(vec, 0);
			uint8_t tag = ((uint8_t *)duckdb_vector_get_data(tag_vec))[row_idx];

			const char *member_name = duckdb_union_type_member_name(logical_type, tag);
			duckdb_logical_type member_type = duckdb_union_type_member_type(logical_type, tag);
			duckdb_vector member_vec = duckdb_struct_vector_get_child(vec, tag + 1);

			zval member_val;
			duckdb_val_from_vector(conn, member_vec, member_type, row_idx, &member_val);

			array_init(result);
			add_assoc_zval(result, member_name, &member_val);

			duckdb_free((void *)member_name);
			duckdb_destroy_logical_type(&member_type);
			break;
		}
		case DUCKDB_TYPE_ARRAY: {
			duckdb_vector child_vec = duckdb_array_vector_get_child(vec);
			duckdb_logical_type child_type = duckdb_array_type_child_type(logical_type);
			idx_t array_size = duckdb_array_type_array_size(logical_type);

			array_init(result);
			for (idx_t i = 0; i < array_size; i++) {
				zval child_val;
				duckdb_val_from_vector(conn, child_vec, child_type, row_idx * array_size + i, &child_val);
				add_next_index_zval(result, &child_val);
			}

			duckdb_destroy_logical_type(&child_type);
			break;
		}
		default: {
			/*
				case DUCKDB_TYPE_ENUM:
				case DUCKDB_TYPE_UUID:
				case DUCKDB_TYPE_BIT:
				case DUCKDB_TYPE_HUGEINT:
				case DUCKDB_TYPE_UHUGEINT:
				case DUCKDB_TYPE_INTERVAL:
				case DUCKDB_TYPE_BIGNUM:
				case DUCKDB_TYPE_DATE:
				case DUCKDB_TYPE_TIME:
				case DUCKDB_TYPE_TIME_TZ:
				case DUCKDB_TYPE_TIME_NS:
				case DUCKDB_TYPE_TIMESTAMP:
				case DUCKDB_TYPE_TIMESTAMP_TZ:
				case DUCKDB_TYPE_TIMESTAMP_S:
				case DUCKDB_TYPE_TIMESTAMP_MS:
				case DUCKDB_TYPE_TIMESTAMP_NS:
			*/
			char *str = duckdb_get_string(conn, vec, row_idx);
			if (str == NULL) {
				ZVAL_NULL(result);
			} else {
				ZVAL_STRING(result, str);
				duckdb_free(str);
			}
			break;
		}
	}
}

/* ---------------- get column data (called by PDO after fetch) ---------------- */
static int duckdb_stmt_get_col(pdo_stmt_t *stmt, int colno, zval *result, enum pdo_param_type *type)
{
	(void)type;
	pdo_duckdb_stmt *S = (pdo_duckdb_stmt *) stmt->driver_data;
	pdo_duckdb_db_handle *H = (pdo_duckdb_db_handle *) stmt->dbh->driver_data;
	duckdb_result *res = &S->result;
	idx_t row_idx = S->chunk_idx;

	duckdb_vector vec = duckdb_data_chunk_get_vector(S->chunk, colno);
	duckdb_logical_type logical_type = duckdb_column_logical_type(res, colno);

	duckdb_val_from_vector(H->conn, vec, logical_type, row_idx, result);

	duckdb_destroy_logical_type(&logical_type);

	if (stmt->dbh->stringify && Z_TYPE_P(result) != IS_NULL) {
		if (Z_TYPE_P(result) == IS_ARRAY || Z_TYPE_P(result) == IS_OBJECT) {
			smart_str buf = {0};
			if (php_json_encode(&buf, result, 0) == SUCCESS && buf.s) {
				smart_str_0(&buf);
				zval_ptr_dtor(result);
				ZVAL_STR(result, buf.s);
			} else {
				smart_str_free(&buf);
			}
		} else {
			convert_to_string(result);
		}
	}

	return 1;
}

/* ---------------- statement fetch (single row) ---------------- */
static int duckdb_stmt_fetch(pdo_stmt_t *stmt, enum pdo_fetch_orientation ori, zend_long offset)
{
	(void)ori; (void)offset;
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

/* ---------------- get column meta ---------------- */
static int duckdb_stmt_get_col_meta(pdo_stmt_t *stmt, zend_long colno, zval *return_value)
{
	pdo_duckdb_stmt *S = (pdo_duckdb_stmt *) stmt->driver_data;
	duckdb_result *res = &S->result;

	if (!S->result_set) {
		return FAILURE;
	}
	if (colno < 0 || colno >= stmt->column_count) {
		return FAILURE;
	}

	array_init(return_value);

	zval flags;
	array_init(&flags);

	duckdb_logical_type logical_type = duckdb_column_logical_type(res, colno);
	duckdb_type type = duckdb_get_type_id(logical_type);
	const char *type_str = NULL;
	enum pdo_param_type pdo_type = PDO_PARAM_STR;

	switch (type) {
		case DUCKDB_TYPE_BOOLEAN: type_str = "boolean"; pdo_type = PDO_PARAM_BOOL; break;
		case DUCKDB_TYPE_TINYINT: type_str = "tinyint"; pdo_type = PDO_PARAM_INT; break;
		case DUCKDB_TYPE_SMALLINT: type_str = "smallint"; pdo_type = PDO_PARAM_INT; break;
		case DUCKDB_TYPE_INTEGER: type_str = "integer"; pdo_type = PDO_PARAM_INT; break;
		case DUCKDB_TYPE_BIGINT: type_str = "bigint"; pdo_type = PDO_PARAM_INT; break;
		case DUCKDB_TYPE_UTINYINT: type_str = "utinyint"; pdo_type = PDO_PARAM_INT; break;
		case DUCKDB_TYPE_USMALLINT: type_str = "usmallint"; pdo_type = PDO_PARAM_INT; break;
		case DUCKDB_TYPE_UINTEGER: type_str = "uinteger"; pdo_type = PDO_PARAM_INT; break;
		case DUCKDB_TYPE_UBIGINT: type_str = "ubigint"; pdo_type = PDO_PARAM_INT; break;
		case DUCKDB_TYPE_HUGEINT: type_str = "hugeint"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_UHUGEINT: type_str = "uhugeint"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_FLOAT: type_str = "float"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_DOUBLE: type_str = "double"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_DECIMAL: type_str = "decimal"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_BLOB: type_str = "blob"; pdo_type = PDO_PARAM_LOB; break;
		case DUCKDB_TYPE_DATE: type_str = "date"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_TIME: type_str = "time"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_TIME_TZ: type_str = "timetz"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_TIME_NS: type_str = "time_ns"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_TIMESTAMP: type_str = "timestamp"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_TIMESTAMP_S: type_str = "timestamp_s"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_TIMESTAMP_MS: type_str = "timestamp_ms"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_TIMESTAMP_NS: type_str = "timestamp_ns"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_TIMESTAMP_TZ: type_str = "timestamptz"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_LIST: type_str = "list"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_STRUCT: type_str = "struct"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_MAP: type_str = "map"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_ENUM: type_str = "enum"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_UNION: type_str = "union"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_UUID: type_str = "uuid"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_INTERVAL: type_str = "interval"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_BIT: type_str = "bit"; pdo_type = PDO_PARAM_STR; break;
		case DUCKDB_TYPE_BIGNUM: type_str = "bignum"; pdo_type = PDO_PARAM_STR; break;
		default: type_str = "unknown"; pdo_type = PDO_PARAM_STR; break;
	}

	/* Check for JSON alias on VARCHAR (DuckDB may report JSON as VARCHAR with "JSON" alias) */
	if (type == DUCKDB_TYPE_VARCHAR) {
		char *alias = duckdb_logical_type_get_alias(logical_type);
		if (alias && strcmp(alias, "JSON") == 0) {
			type_str = "json";
			duckdb_free(alias);
		} else {
			type_str = "varchar";
			duckdb_free(alias);
		}
		pdo_type = PDO_PARAM_STR;
	}

	add_assoc_string(return_value, "native_type", (char *)type_str);
	add_assoc_long(return_value, "pdo_type", pdo_type);
	add_assoc_string(return_value, "duckdb:decl_type", (char *)type_str);
	add_assoc_zval(return_value, "flags", &flags);

	duckdb_destroy_logical_type(&logical_type);
	return SUCCESS;
}

/* Convert a PHP string (e.g. "101010") to a duckdb_bit struct for binding.
   The returned duckdb_bit.data must be freed with duckdb_free() when no longer needed. */

/* Resolve a named parameter to a DuckDB 1-based positional index.
 * Returns 0 on failure. */
static idx_t duckdb_resolve_named_param(duckdb_prepared_statement stmt, const char *name)
{
	/* DuckDB stores parameter names internally without any prefix — the $ is
	   SQL syntax, not part of the name. Strip leading ':' or '$' before looking up. */
	const char *bare = name;
	while (*bare == ':' || *bare == '$') {
		bare++;
	}

	idx_t idx;
	duckdb_state state = duckdb_bind_parameter_index(stmt, &idx, bare);
	if (state != DuckDBSuccess) {
		return 0;
	}
	return idx;
}

/* ---------------- parameter hook (binding before execution) ---------------- */
static int duckdb_stmt_param_hook(pdo_stmt_t *stmt, struct pdo_bound_param_data *param,
                                   enum pdo_param_event event_type)
{
	pdo_duckdb_stmt *S = (pdo_duckdb_stmt *) stmt->driver_data;

	if (event_type == PDO_PARAM_EVT_EXEC_PRE) {
		duckdb_state state = DuckDBError;
		idx_t idx;

		if (param->paramno >= 0) {
			/* param->paramno is 0-based in PDO, but DuckDB expects 1-based index */
			idx = param->paramno + 1;
		} else {
			idx = duckdb_resolve_named_param(S->stmt, ZSTR_VAL(param->name));
			if (idx == 0) {
				zend_throw_exception_ex(php_pdo_get_exception(), 0,
					"SQLSTATE[HY000]: could not resolve named parameter '%s'", ZSTR_VAL(param->name));
				return 0;
			}
		}

		if (Z_ISNULL(param->parameter)) {
			state = duckdb_bind_null(S->stmt, idx);
		} else if (Z_TYPE_P(&param->parameter) == IS_ARRAY || Z_TYPE_P(&param->parameter) == IS_OBJECT) {
			smart_str buf = {0};
			if (php_json_encode(&buf, &param->parameter, 0) == SUCCESS && buf.s) {
				smart_str_0(&buf);
				state = duckdb_bind_varchar_length(S->stmt, idx, ZSTR_VAL(buf.s), ZSTR_LEN(buf.s));
			} else {
				state = DuckDBError;
			}
			smart_str_free(&buf);
		} else switch (PDO_PARAM_TYPE(param->param_type)) {
			case PDO_PARAM_NULL:
				state = duckdb_bind_null(S->stmt, idx);
				break;
			case PDO_PARAM_BOOL:
				state = duckdb_bind_boolean(S->stmt, idx, zval_is_true(&param->parameter) ? 1 : 0);
				break;
			case PDO_PARAM_INT:
				state = duckdb_bind_int64(S->stmt, idx, (int64_t)zval_get_long(&param->parameter));
				break;
			case PDO_PARAM_STR: {
				zend_string *zstr = zval_get_string(&param->parameter);
				state = duckdb_bind_varchar_length(S->stmt, idx, ZSTR_VAL(zstr), ZSTR_LEN(zstr));
				zend_string_release(zstr);
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
			zend_throw_exception_ex(php_pdo_get_exception(), 0,
				"SQLSTATE[HY000]: parameter binding failed for parameter '%s'",
				param->name ? ZSTR_VAL(param->name) : "");
			return 0;
		}
	}

	return 1;
}

/* ---------------- cursor closer (free result & chunk, keep prepared statement) ---------------- */
static int duckdb_stmt_cursor_closer(pdo_stmt_t *stmt)
{
	pdo_duckdb_stmt *S = (pdo_duckdb_stmt *) stmt->driver_data;
	if (S) {
		if (S->chunk) {
			duckdb_destroy_data_chunk(&S->chunk);
			S->chunk = NULL;
		}
		if (S->result_set) {
			duckdb_destroy_result(&S->result);
			S->result_set = 0;
		}
	}
	return 1;
}

/* ---------------- statement destructor (free everything) ---------------- */
static int duckdb_stmt_dtor(pdo_stmt_t *stmt)
{
	pdo_duckdb_stmt *S = (pdo_duckdb_stmt *) stmt->driver_data;
	if (S) {
		if (S->chunk) {
			duckdb_destroy_data_chunk(&S->chunk);
			S->chunk = NULL;
		}
		if (S->result_set) {
			duckdb_destroy_result(&S->result);
			S->result_set = 0;
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
	duckdb_stmt_dtor,             /* dtor */
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
