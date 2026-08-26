#include "duckdb.hpp"
#include <cstring>

extern "C" int duckdb_has_active_transaction(duckdb_connection conn) {
	try {
		auto *conn_ptr = reinterpret_cast<duckdb::Connection *>(conn);
		return conn_ptr->context->transaction.HasActiveTransaction() ? 1 : 0;
	} catch (...) {
		return 0;
	}
}

// Forward-declare VariantUtils::ConvertVariantToValue (omitted from amalgamation header)
namespace duckdb {
struct VariantUtils {
	static Value ConvertVariantToValue(const UnifiedVariantVectorData &variant, idx_t row, uint32_t values_idx);
};
}

extern "C" int duckdb_variant_to_vector(duckdb_vector vec, idx_t row,
                                         duckdb_vector *out_vec, duckdb_logical_type *out_type) {
	try {
		auto *vec_ptr = reinterpret_cast<duckdb::Vector *>(vec);

		duckdb::RecursiveUnifiedVectorFormat format;
		duckdb::Vector::RecursiveToUnifiedFormat(*vec_ptr, 1, format);
		duckdb::UnifiedVariantVectorData variant_data(format);

		auto value = duckdb::VariantUtils::ConvertVariantToValue(variant_data, row, 0);
		if (value.IsNull()) return 0;

		auto type = value.type();
		auto *tmp_vec = new duckdb::Vector(value);
		*out_vec = reinterpret_cast<duckdb_vector>(tmp_vec);
		*out_type = reinterpret_cast<duckdb_logical_type>(new duckdb::LogicalType(type));
		return 1;
	} catch (...) {
		return 0;
	}
}

extern "C" void duckdb_free_vector(duckdb_vector vec) {
	if (vec) {
		delete reinterpret_cast<duckdb::Vector *>(vec);
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
