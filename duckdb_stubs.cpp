#include "duckdb.hpp"
#include <cstring>

using namespace duckdb;

extern "C" int duckdb_has_active_transaction(duckdb_connection conn)
{
	try {
		auto *conn_ptr = reinterpret_cast<duckdb::Connection *>(conn);
		return conn_ptr->context->transaction.HasActiveTransaction() ? 1 : 0;
	} catch (...) {
		return 0;
	}
}

extern "C" int duckdb_variant_to_vector(duckdb_connection conn, duckdb_vector vec, idx_t row,
										duckdb_vector *out_vec, duckdb_logical_type *out_type)
{
	(void) conn;
	try {
		// Vector::GetValue() returns the still-wrapped VARIANT value.
		// VariantValue::GetValue converts it to the concrete inner type (INTEGER, DECIMAL(width,scale), ...).
		auto raw = reinterpret_cast<duckdb::Vector *>(vec)->GetValue(row);
		if (raw.IsNull()) {
			return 0;
		}
		auto value = VariantValue::GetValue(raw);
		*out_vec = reinterpret_cast<duckdb_vector>(new duckdb::Vector(value, count_t(1)));
		*out_type = reinterpret_cast<duckdb_logical_type>(new duckdb::LogicalType(value.type()));
		return 1;
	} catch (...) {
		return 0;
	}
}

extern "C" void duckdb_free_vector(duckdb_vector vec)
{
	if (vec) {
		delete reinterpret_cast<duckdb::Vector *>(vec);
	}
}

extern "C" char *duckdb_get_string(duckdb_connection conn, duckdb_vector vec, idx_t row)
{
	if (!vec) return NULL;

	try {
		auto *vec_ptr = reinterpret_cast<duckdb::Vector *>(vec);
		auto value = vec_ptr->GetValue(row);

		if (value.IsNull()) return NULL;

		auto *conn_ptr = reinterpret_cast<duckdb::Connection *>(conn);
		auto str = value.CastAs(*conn_ptr->context, duckdb::LogicalType::VARCHAR).ToString();

		auto *result = (char *) duckdb_malloc(str.size() + 1);
		if (result) {
			memcpy(result, str.c_str(), str.size());
			result[str.size()] = '\0';
		}

		return result;
	} catch (...) {
		return NULL;
	}
}

extern "C" char *duckdb_logical_type_to_string(duckdb_logical_type logical_type)
{
	if (!logical_type) return NULL;

	try {
		auto *type_ptr = reinterpret_cast<duckdb::LogicalType *>(logical_type);
		auto str = type_ptr->ToString();

		auto *result = (char *) duckdb_malloc(str.size() + 1);
		if (result) {
			memcpy(result, str.c_str(), str.size());
			result[str.size()] = '\0';
		}

		return result;
	} catch (...) {
		return NULL;
	}
}
