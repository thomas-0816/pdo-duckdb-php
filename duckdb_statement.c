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
#include <math.h>
#include "ext/json/php_json.h"

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
		zend_throw_exception_ex(php_pdo_get_exception(), 0,
			"SQLSTATE[HY000]: %s", err ? err : "execute error");
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
static void duckdb_val_from_vector(duckdb_vector vec, duckdb_logical_type logical_type, idx_t row_idx, zval *result)
{
	duckdb_type col_type = duckdb_get_type_id(logical_type);
	uint64_t *validity = duckdb_vector_get_validity(vec);

	if (!duckdb_validity_row_is_valid(validity, row_idx)) {
		ZVAL_NULL(result);
		return;
	}

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
		case DUCKDB_TYPE_HUGEINT: {
			duckdb_hugeint val = ((duckdb_hugeint *)duckdb_vector_get_data(vec))[row_idx];
			unsigned __int128 v;
			int neg = 0;
			if (val.upper < 0) {
				neg = 1;
				v = ~((unsigned __int128)(uint64_t)val.upper << 64 | val.lower) + 1;
			} else {
				v = (unsigned __int128)(uint64_t)val.upper << 64 | val.lower;
			}
			char buf[40];
			if (v == 0) {
				snprintf(buf, sizeof(buf), "0");
			} else {
				char tmp[40];
				int i = 0;
				while (v > 0) {
					tmp[i++] = '0' + (char)(v % 10);
					v /= 10;
				}
				int pos = 0;
				if (neg) buf[pos++] = '-';
				while (i > 0) buf[pos++] = tmp[--i];
				buf[pos] = '\0';
			}
			ZVAL_STRING(result, buf);
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
		case DUCKDB_TYPE_DATE: {
			duckdb_date date = ((duckdb_date *)duckdb_vector_get_data(vec))[row_idx];
			if (!duckdb_is_finite_date(date)) {
				ZVAL_STRING(result, date.days < 0 ? "-infinity" : "infinity");
			} else {
				duckdb_date_struct ds = duckdb_from_date(date);
				char buf[11];
				snprintf(buf, sizeof(buf), "%04d-%02d-%02d", ds.year, ds.month, ds.day);
				ZVAL_STRING(result, buf);
			}
			break;
		}
		case DUCKDB_TYPE_TIME: {
			duckdb_time time_val = ((duckdb_time *)duckdb_vector_get_data(vec))[row_idx];
			duckdb_time_struct ts = duckdb_from_time(time_val);
			char buf[32];
			int len = snprintf(buf, sizeof(buf), "%02d:%02d:%02d", ts.hour, ts.min, ts.sec);
			if (ts.micros) {
				len += snprintf(buf + len, sizeof(buf) - len, ".%06d", ts.micros);
			}
			ZVAL_STRING(result, buf);
			break;
		}
		case DUCKDB_TYPE_TIME_TZ: {
			duckdb_time_tz time_tz_val = ((duckdb_time_tz *)duckdb_vector_get_data(vec))[row_idx];
			duckdb_time_tz_struct tts = duckdb_from_time_tz(time_tz_val);
			char buf[48];
			int len = snprintf(buf, sizeof(buf), "%02d:%02d:%02d", tts.time.hour, tts.time.min, tts.time.sec);
			if (tts.time.micros) {
				len += snprintf(buf + len, sizeof(buf) - len, ".%06d", tts.time.micros);
			}
			int32_t offset_abs = tts.offset < 0 ? -tts.offset : tts.offset;
			len += snprintf(buf + len, sizeof(buf) - len, "%c%02d:%02d",
			                tts.offset >= 0 ? '+' : '-',
			                offset_abs / 3600, (offset_abs % 3600) / 60);
			ZVAL_STRING(result, buf);
			break;
		}
		case DUCKDB_TYPE_TIME_NS: {
			duckdb_time_ns time_ns_val = ((duckdb_time_ns *)duckdb_vector_get_data(vec))[row_idx];
			int64_t remaining = time_ns_val.nanos;
			int hour = (int)(remaining / 3600000000000LL);
			remaining %= 3600000000000LL;
			int min = (int)(remaining / 60000000000LL);
			remaining %= 60000000000LL;
			int sec = (int)(remaining / 1000000000LL);
			int nanos = (int)(remaining % 1000000000LL);
			char buf[32];
			int len = snprintf(buf, sizeof(buf), "%02d:%02d:%02d", hour, min, sec);
			if (nanos) {
				len += snprintf(buf + len, sizeof(buf) - len, ".%09d", nanos);
			}
			ZVAL_STRING(result, buf);
			break;
		}
		case DUCKDB_TYPE_TIMESTAMP: {
			duckdb_timestamp ts = ((duckdb_timestamp *)duckdb_vector_get_data(vec))[row_idx];
			if (!duckdb_is_finite_timestamp(ts)) {
				ZVAL_STRING(result, ts.micros < 0 ? "-infinity" : "infinity");
			} else {
				duckdb_timestamp_struct tss = duckdb_from_timestamp(ts);
				char buf[64];
				int len = snprintf(buf, sizeof(buf), "%04d-%02d-%02d %02d:%02d:%02d",
				                   tss.date.year, tss.date.month, tss.date.day,
				                   tss.time.hour, tss.time.min, tss.time.sec);
				if (tss.time.micros) {
					len += snprintf(buf + len, sizeof(buf) - len, ".%06d", tss.time.micros);
				}
				ZVAL_STRING(result, buf);
			}
			break;
		}
		case DUCKDB_TYPE_TIMESTAMP_TZ: {
			duckdb_timestamp ts = ((duckdb_timestamp *)duckdb_vector_get_data(vec))[row_idx];
			if (!duckdb_is_finite_timestamp(ts)) {
				ZVAL_STRING(result, ts.micros < 0 ? "-infinity" : "infinity");
			} else {
				time_t secs = (time_t)(ts.micros / 1000000);
				int64_t usec = ts.micros % 1000000;
				if (usec < 0) {
					secs--;
					usec += 1000000;
				}
				struct tm tm;
				localtime_r(&secs, &tm);
				int offs = (int)tm.tm_gmtoff;
				char sign = offs >= 0 ? '+' : '-';
				if (offs < 0) offs = -offs;
				int offs_hours = offs / 3600;
				int offs_mins = (offs % 3600) / 60;
				char buf[64];
				int len = snprintf(buf, sizeof(buf), "%04d-%02d-%02d %02d:%02d:%02d",
				                   tm.tm_year + 1900, tm.tm_mon + 1, tm.tm_mday,
				                   tm.tm_hour, tm.tm_min, tm.tm_sec);
				if (usec) {
					len += snprintf(buf + len, sizeof(buf) - len, ".%06d", (int)usec);
				}
				len += snprintf(buf + len, sizeof(buf) - len, "%c%02d:%02d", sign, offs_hours, offs_mins);
				ZVAL_STRING(result, buf);
			}
			break;
		}
		case DUCKDB_TYPE_TIMESTAMP_S: {
			duckdb_timestamp_s ts = ((duckdb_timestamp_s *)duckdb_vector_get_data(vec))[row_idx];
			if (!duckdb_is_finite_timestamp_s(ts)) {
				ZVAL_STRING(result, ts.seconds < 0 ? "-infinity" : "infinity");
			} else {
				duckdb_timestamp ts_us;
				ts_us.micros = ts.seconds * 1000000;
				duckdb_timestamp_struct tss = duckdb_from_timestamp(ts_us);
				char buf[64];
				snprintf(buf, sizeof(buf), "%04d-%02d-%02d %02d:%02d:%02d",
				         tss.date.year, tss.date.month, tss.date.day,
				         tss.time.hour, tss.time.min, tss.time.sec);
				ZVAL_STRING(result, buf);
			}
			break;
		}
		case DUCKDB_TYPE_TIMESTAMP_MS: {
			duckdb_timestamp_ms ts = ((duckdb_timestamp_ms *)duckdb_vector_get_data(vec))[row_idx];
			if (!duckdb_is_finite_timestamp_ms(ts)) {
				ZVAL_STRING(result, ts.millis < 0 ? "-infinity" : "infinity");
			} else {
				duckdb_timestamp ts_us;
				ts_us.micros = ts.millis * 1000;
				duckdb_timestamp_struct tss = duckdb_from_timestamp(ts_us);
				char buf[64];
				int len = snprintf(buf, sizeof(buf), "%04d-%02d-%02d %02d:%02d:%02d",
				                   tss.date.year, tss.date.month, tss.date.day,
				                   tss.time.hour, tss.time.min, tss.time.sec);
				int ms = ts.millis % 1000;
				if (ms) {
					len += snprintf(buf + len, sizeof(buf) - len, ".%03d", ms);
				}
				ZVAL_STRING(result, buf);
			}
			break;
		}
		case DUCKDB_TYPE_TIMESTAMP_NS: {
			duckdb_timestamp_ns ts = ((duckdb_timestamp_ns *)duckdb_vector_get_data(vec))[row_idx];
			if (!duckdb_is_finite_timestamp_ns(ts)) {
				ZVAL_STRING(result, ts.nanos < 0 ? "-infinity" : "infinity");
			} else {
				int64_t micros_part = ts.nanos / 1000;
				int64_t nanos_remainder = ts.nanos % 1000;
				if (nanos_remainder < 0) {
					micros_part--;
					nanos_remainder += 1000;
				}
				duckdb_timestamp ts_us;
				ts_us.micros = micros_part;
				duckdb_timestamp_struct tss = duckdb_from_timestamp(ts_us);
				char buf[64];
				int len = snprintf(buf, sizeof(buf), "%04d-%02d-%02d %02d:%02d:%02d",
				                   tss.date.year, tss.date.month, tss.date.day,
				                   tss.time.hour, tss.time.min, tss.time.sec);
				if (tss.time.micros || nanos_remainder) {
					len += snprintf(buf + len, sizeof(buf) - len, ".%06d%03ld", tss.time.micros, (long)nanos_remainder);
				}
				ZVAL_STRING(result, buf);
			}
			break;
		}
		case DUCKDB_TYPE_LIST: {
			duckdb_list_entry entry = ((duckdb_list_entry *)duckdb_vector_get_data(vec))[row_idx];
			duckdb_vector child_vec = duckdb_list_vector_get_child(vec);
			duckdb_logical_type child_type = duckdb_list_type_child_type(logical_type);

			array_init(result);
			for (idx_t i = entry.offset; i < entry.offset + entry.length; i++) {
				zval child_val;
				duckdb_val_from_vector(child_vec, child_type, i, &child_val);
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
				duckdb_val_from_vector(child_vec, child_type, row_idx, &child_val);
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
				duckdb_val_from_vector(key_vec, key_type, i, &key_val);
				duckdb_val_from_vector(val_vec, val_type, i, &val_val);

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
			duckdb_val_from_vector(member_vec, member_type, row_idx, &member_val);

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
				duckdb_val_from_vector(child_vec, child_type, row_idx * array_size + i, &child_val);
				add_next_index_zval(result, &child_val);
			}

			duckdb_destroy_logical_type(&child_type);
			break;
		}
		case DUCKDB_TYPE_ENUM: {
			duckdb_type internal_type = duckdb_enum_internal_type(logical_type);
			uint64_t enum_idx;
			switch (internal_type) {
				case DUCKDB_TYPE_UTINYINT:
					enum_idx = ((uint8_t *)duckdb_vector_get_data(vec))[row_idx];
					break;
				case DUCKDB_TYPE_USMALLINT:
					enum_idx = ((uint16_t *)duckdb_vector_get_data(vec))[row_idx];
					break;
				case DUCKDB_TYPE_UINTEGER:
					enum_idx = ((uint32_t *)duckdb_vector_get_data(vec))[row_idx];
					break;
				case DUCKDB_TYPE_UBIGINT:
					enum_idx = ((uint64_t *)duckdb_vector_get_data(vec))[row_idx];
					break;
				default: {
					char *val = duckdb_enum_dictionary_value(logical_type, 0);
					ZVAL_STRING(result, val);
					duckdb_free(val);
					return;
				}
			}
			uint32_t dict_size = duckdb_enum_dictionary_size(logical_type);
			if (enum_idx < dict_size) {
				char *val = duckdb_enum_dictionary_value(logical_type, enum_idx);
				ZVAL_STRING(result, val);
				duckdb_free(val);
			} else {
				ZVAL_NULL(result);
			}
			break;
		}
		case DUCKDB_TYPE_UUID: {
			duckdb_hugeint val = ((duckdb_hugeint *)duckdb_vector_get_data(vec))[row_idx];
			uint64_t upper = (uint64_t)val.upper;
			uint64_t lower = val.lower;
			char buf[37];
			snprintf(buf, sizeof(buf), "%08x-%04x-%04x-%04x-%012lx",
			         (unsigned)(upper >> 32),
			         (unsigned)((upper >> 16) & 0xFFFF),
			         (unsigned)(upper & 0xFFFF),
			         (unsigned)(lower >> 48),
			         (unsigned long)(lower & 0xFFFFFFFFFFFFULL));
			ZVAL_STRING(result, buf);
			break;
		}
		case DUCKDB_TYPE_INTERVAL: {
			duckdb_interval interval_val = ((duckdb_interval *)duckdb_vector_get_data(vec))[row_idx];
			char buf[128];
			int pos = 0;
			int32_t months = interval_val.months;
			int32_t days = interval_val.days;
			int64_t micros = interval_val.micros;
			int has_prev = 0;
			if (months != 0) {
				int32_t years = months / 12;
				months = months % 12;
				if (years != 0) {
					pos += snprintf(buf + pos, sizeof(buf) - pos, "%d year%s", years, years == 1 ? "" : "s");
					has_prev = 1;
				}
				if (months != 0) {
					if (has_prev) buf[pos++] = ' ';
					pos += snprintf(buf + pos, sizeof(buf) - pos, "%d month%s", months, months == 1 ? "" : "s");
					has_prev = 1;
				}
			}
			if (days != 0) {
				if (has_prev) buf[pos++] = ' ';
				pos += snprintf(buf + pos, sizeof(buf) - pos, "%d day%s", days, days == 1 ? "" : "s");
				has_prev = 1;
			}
			if (micros != 0 || !has_prev) {
				if (has_prev) buf[pos++] = ' ';
				int64_t remaining = micros;
				int neg = remaining < 0;
				if (neg) remaining = -remaining;
				int64_t hours = remaining / 3600000000LL;
				remaining %= 3600000000LL;
				int64_t mins = remaining / 60000000LL;
				remaining %= 60000000LL;
				int64_t secs = remaining / 1000000LL;
				int64_t usecs = remaining % 1000000LL;
				if (neg) {
					pos += snprintf(buf + pos, sizeof(buf) - pos, "-%02lld:%02lld:%02lld", (long long)hours, (long long)mins, (long long)secs);
				} else {
					pos += snprintf(buf + pos, sizeof(buf) - pos, "%02lld:%02lld:%02lld", (long long)hours, (long long)mins, (long long)secs);
				}
				if (usecs) {
					pos += snprintf(buf + pos, sizeof(buf) - pos, ".%06lld", (long long)usecs);
				}
			}
			ZVAL_STRINGL(result, buf, pos);
			break;
		}
		case DUCKDB_TYPE_VARIANT: {
			duckdb_string_t str = ((duckdb_string_t *)duckdb_vector_get_data(vec))[row_idx];
			const char *str_data = duckdb_string_t_data(&str);
			size_t str_len = duckdb_string_t_length(str);
			if (php_json_decode(result, str_data, str_len, 1, 512) != SUCCESS) {
				ZVAL_STRINGL(result, str_data, str_len);
			}
			break;
		}
		case DUCKDB_TYPE_BIT: {
			duckdb_string_t str = ((duckdb_string_t *)duckdb_vector_get_data(vec))[row_idx];
			const char *str_data = duckdb_string_t_data(&str);
			size_t str_len = duckdb_string_t_length(str);
			if (str_len == 0) {
				ZVAL_STRING(result, "");
				break;
			}
			uint8_t padding = (uint8_t)str_data[0];
			size_t bit_count = (str_len - 1) * 8 - padding;
			char *buf = emalloc(bit_count + 1);
			for (size_t i = 0; i < bit_count; i++) {
				size_t byte_idx = 1 + i / 8;
				size_t bit_offset = 7 - padding - (i % 8);
				buf[i] = ((str_data[byte_idx] >> bit_offset) & 1) ? '1' : '0';
			}
			buf[bit_count] = '\0';
			ZVAL_STRING(result, buf);
			efree(buf);
			break;
		}
		default: {
			duckdb_string_t str = ((duckdb_string_t *)duckdb_vector_get_data(vec))[row_idx];
			ZVAL_STRINGL(result, duckdb_string_t_data(&str), duckdb_string_t_length(str));
			break;
		}
	}
}

/* ---------------- get column data (called by PDO after fetch) ---------------- */
static int duckdb_stmt_get_col(pdo_stmt_t *stmt, int colno, zval *result, enum pdo_param_type *type)
{
	pdo_duckdb_stmt *S = (pdo_duckdb_stmt *) stmt->driver_data;
	duckdb_result *res = &S->result;
	idx_t row_idx = S->chunk_idx;

	duckdb_vector vec = duckdb_data_chunk_get_vector(S->chunk, colno);
	duckdb_logical_type logical_type = duckdb_column_logical_type(res, colno);
	duckdb_type col_type = duckdb_get_type_id(logical_type);

	/* Handle JSON type: DuckDB may report it as VARIANT (newer versions)
	   or as VARCHAR with "JSON" alias (older versions). */
	if (col_type == DUCKDB_TYPE_VARIANT) {
		duckdb_string_t str = ((duckdb_string_t *)duckdb_vector_get_data(vec))[row_idx];
		const char *str_data = duckdb_string_t_data(&str);
		size_t str_len = duckdb_string_t_length(str);
		if (php_json_decode(result, str_data, str_len, 1, 512) != SUCCESS) {
			ZVAL_STRINGL(result, str_data, str_len);
		}
		duckdb_destroy_logical_type(&logical_type);
		return 1;
	}
	if (col_type == DUCKDB_TYPE_VARCHAR) {
		char *alias = duckdb_logical_type_get_alias(logical_type);
		int is_json = (alias && strcmp(alias, "JSON") == 0);
		duckdb_free(alias);
		if (is_json) {
			duckdb_string_t str = ((duckdb_string_t *)duckdb_vector_get_data(vec))[row_idx];
			const char *str_data = duckdb_string_t_data(&str);
			size_t str_len = duckdb_string_t_length(str);
			if (php_json_decode(result, str_data, str_len, 1, 512) != SUCCESS) {
				ZVAL_STRINGL(result, str_data, str_len);
			}
			duckdb_destroy_logical_type(&logical_type);
			return 1;
		}
	}

	duckdb_val_from_vector(vec, logical_type, row_idx, result);

	duckdb_destroy_logical_type(&logical_type);
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
		case DUCKDB_TYPE_HUGEINT: type_str = "hugeint"; break;
		case DUCKDB_TYPE_FLOAT: type_str = "float"; break;
		case DUCKDB_TYPE_DOUBLE: type_str = "double"; break;
		case DUCKDB_TYPE_VARCHAR: type_str = "varchar"; break;
		case DUCKDB_TYPE_BLOB: type_str = "blob"; break;
		case DUCKDB_TYPE_DATE: type_str = "date"; break;
		case DUCKDB_TYPE_TIME: type_str = "time"; break;
		case DUCKDB_TYPE_TIME_TZ: type_str = "timetz"; break;
		case DUCKDB_TYPE_TIME_NS: type_str = "time_ns"; break;
		case DUCKDB_TYPE_TIMESTAMP: type_str = "timestamp"; break;
		case DUCKDB_TYPE_TIMESTAMP_S: type_str = "timestamp_s"; break;
		case DUCKDB_TYPE_TIMESTAMP_MS: type_str = "timestamp_ms"; break;
		case DUCKDB_TYPE_TIMESTAMP_NS: type_str = "timestamp_ns"; break;
		case DUCKDB_TYPE_TIMESTAMP_TZ: type_str = "timestamptz"; break;
		case DUCKDB_TYPE_DECIMAL: type_str = "decimal"; break;
		case DUCKDB_TYPE_LIST: type_str = "list"; break;
		case DUCKDB_TYPE_STRUCT: type_str = "struct"; break;
		case DUCKDB_TYPE_MAP: type_str = "map"; break;
		case DUCKDB_TYPE_ENUM: type_str = "enum"; break;
		case DUCKDB_TYPE_UNION: type_str = "union"; break;
		case DUCKDB_TYPE_UUID: type_str = "uuid"; break;
		case DUCKDB_TYPE_INTERVAL: type_str = "interval"; break;
		case DUCKDB_TYPE_VARIANT: type_str = "json"; break;
		case DUCKDB_TYPE_BIT: type_str = "bit"; break;
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

/* Convert a PHP string (e.g. "101010") to a duckdb_bit struct for binding.
   The returned duckdb_bit.data must be freed with duckdb_free() when no longer needed. */
static duckdb_bit php_str_to_duckdb_bit(const char *str, size_t len)
{
	duckdb_bit bit;
	bit.size = len;
	uint8_t padding = (8 - (len % 8)) % 8;
	size_t byte_count = (len + 7) / 8;
	bit.data = duckdb_malloc(1 + byte_count);
	if (!bit.data) {
		bit.size = 0;
		return bit;
	}
	bit.data[0] = padding;
	memset(bit.data + 1, 0, byte_count);
	for (size_t i = 0; i < len; i++) {
		if (str[i] == '1') {
			size_t byte_idx = 1 + i / 8;
			size_t bit_offset = 7 - (i % 8);
			bit.data[byte_idx] |= (1 << bit_offset);
		}
	}
	if (padding > 0) {
		bit.data[byte_count] |= (0xFF << (8 - padding)) & 0xFF;
	}
	return bit;
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
				zend_string *zstr = zval_get_string(&param->parameter);
				state = duckdb_bind_varchar_length(S->stmt, idx, ZSTR_VAL(zstr), ZSTR_LEN(zstr));
				if (state != DuckDBSuccess) {
					duckdb_bit bit = php_str_to_duckdb_bit(ZSTR_VAL(zstr), ZSTR_LEN(zstr));
					if (bit.data) {
						duckdb_value val = duckdb_create_bit(bit);
						state = duckdb_bind_value(S->stmt, idx, val);
						duckdb_destroy_value(&val);
						duckdb_free(bit.data);
					}
				}
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
