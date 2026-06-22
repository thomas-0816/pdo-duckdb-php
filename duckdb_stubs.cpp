#include "duckdb.hpp"
#include <cstring>

extern "C" char *duckdb_variant_get_string(duckdb_vector vec, idx_t row) {
	if (!vec) return NULL;

	auto *variant_vec = reinterpret_cast<duckdb::Vector *>(vec);
	auto value = variant_vec->GetValue(row);

	if (value.IsNull()) return NULL;

	auto json_str = value.DefaultCastAs(duckdb::LogicalType::JSON()).ToString();

	// If the JSON result starts with '"', the variant contained a VARCHAR value
	// (DuckDB wraps VARCHAR content in a JSON string). Fall back to VARCHAR cast
	// to preserve the raw content for php_json_decode_ex in the caller.
	if (!json_str.empty() && json_str[0] == '"') {
		json_str = value.DefaultCastAs(duckdb::LogicalType::VARCHAR).ToString();
	}

	auto *result = (char *)duckdb_malloc(json_str.size() + 1);
	if (result) {
		memcpy(result, json_str.c_str(), json_str.size());
		result[json_str.size()] = '\0';
	}

	return result;
}

extern "C" char *duckdb_interval_get_string(duckdb_vector vec, idx_t row) {
	if (!vec) return NULL;

	auto *vec_ptr = reinterpret_cast<duckdb::Vector *>(vec);
	auto value = vec_ptr->GetValue(row);

	if (value.IsNull()) return NULL;

	auto str = value.DefaultCastAs(duckdb::LogicalType::VARCHAR).ToString();

	auto *result = (char *)duckdb_malloc(str.size() + 1);
	if (result) {
		memcpy(result, str.c_str(), str.size());
		result[str.size()] = '\0';
	}

	return result;
}

extern "C" char *duckdb_geometry_get_string(duckdb_vector vec, idx_t row) {
	if (!vec) return NULL;

	auto *geom_vec = reinterpret_cast<duckdb::Vector *>(vec);
	auto value = geom_vec->GetValue(row);

	if (value.IsNull()) return NULL;

	auto str = value.DefaultCastAs(duckdb::LogicalType::VARCHAR).ToString();

	auto *result = (char *)duckdb_malloc(str.size() + 1);
	if (result) {
		memcpy(result, str.c_str(), str.size());
		result[str.size()] = '\0';
	}

	return result;
}
