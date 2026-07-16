#include "duckdb.hpp"
#include <cstring>

namespace duckdb {
class DuckDB;
class ExtensionHelper {
public:
	static void LoadAllExtensions(DuckDB &db);
};
void ExtensionHelper::LoadAllExtensions(DuckDB &) {}
}

extern "C" char *duckdb_get_json_string(duckdb_connection conn, duckdb_vector vec, idx_t row) {
	if (!vec) return NULL;

	try {
		auto *conn_ptr = reinterpret_cast<duckdb::Connection *>(conn);
		auto &context = *conn_ptr->context;

		auto *vec_ptr = reinterpret_cast<duckdb::Vector *>(vec);
		auto value = vec_ptr->GetValue(row);

		if (value.IsNull()) return NULL;

		auto json_str = value.CastAs(context, duckdb::LogicalType::JSON()).ToString();

		// If the JSON result starts with '"', the variant contained a VARCHAR value
		// (DuckDB wraps VARCHAR content in a JSON string). Fall back to VARCHAR cast
		// to preserve the raw content for php_json_decode_ex in the caller.
		if (!json_str.empty() && json_str[0] == '"') {
			json_str = value.CastAs(context, duckdb::LogicalType::VARCHAR).ToString();
		}

		auto *result = (char *)duckdb_malloc(json_str.size() + 1);
		if (result) {
			memcpy(result, json_str.c_str(), json_str.size());
			result[json_str.size()] = '\0';
		}

		return result;
	} catch (...) {
		return NULL;
	}
}

extern "C" char *duckdb_get_string(duckdb_connection conn, duckdb_vector vec, idx_t row) {
	if (!vec) return NULL;

	try {
		auto *vec_ptr = reinterpret_cast<duckdb::Vector *>(vec);
		auto value = vec_ptr->GetValue(row);

		if (value.IsNull()) return NULL;

		auto *conn_ptr = reinterpret_cast<duckdb::Connection *>(conn);
		auto str = value.CastAs(*conn_ptr->context, duckdb::LogicalType::VARCHAR).ToString();

		auto *result = (char *)duckdb_malloc(str.size() + 1);
		if (result) {
			memcpy(result, str.c_str(), str.size());
			result[str.size()] = '\0';
		}

		return result;
	} catch (...) {
		return NULL;
	}
}
