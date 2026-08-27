#include "duckdb.hpp"
#include <cstring>

using namespace duckdb;

extern "C" int duckdb_has_active_transaction(duckdb_connection conn) {
	try {
		auto *conn_ptr = reinterpret_cast<duckdb::Connection *>(conn);
		return conn_ptr->context->transaction.HasActiveTransaction() ? 1 : 0;
	} catch (...) {
		return 0;
	}
}

extern "C" int duckdb_variant_to_vector(duckdb_connection conn, duckdb_vector vec, idx_t row,
                                         duckdb_vector *out_vec, duckdb_logical_type *out_type) {
	if (!out_vec || !out_type) {
		return 0;
	}
	try {
		auto *vec_ptr = reinterpret_cast<duckdb::Vector *>(vec);
		auto *conn_ptr = reinterpret_cast<duckdb::Connection *>(conn);

		duckdb::RecursiveUnifiedVectorFormat format;
		duckdb::Vector::RecursiveToUnifiedFormat(*vec_ptr, row + 1, format);

		auto map_row = format.unified.sel->get_index(row);
		if (!format.unified.validity.RowIsValid(map_row)) {
			return 0;
		}

		auto &values = UnifiedVariantVector::GetValues(format);
		auto &type_id_v = UnifiedVariantVector::GetValuesTypeId(format);
		auto &byte_offset_v = UnifiedVariantVector::GetValuesByteOffset(format);
		auto &data_v = UnifiedVariantVector::GetData(format);

		auto values_data = values.GetData<list_entry_t>(values);
		auto type_id_data = type_id_v.GetData<uint8_t>(type_id_v);
		auto byte_offset_data = byte_offset_v.GetData<uint32_t>(byte_offset_v);

		auto values_index = values.sel->get_index(map_row);
		auto entry = values_data[values_index];

		VariantLogicalType type_id =
		    static_cast<VariantLogicalType>(type_id_data[type_id_v.sel->get_index(entry.offset)]);
		uint32_t byte_offset = byte_offset_data[byte_offset_v.sel->get_index(entry.offset)];

		auto data_index = data_v.sel->get_index(map_row);
		const string_t *blob = &data_v.GetData<string_t>(data_v)[data_index];

		duckdb::Value value = vec_ptr->GetValue(row);
		if (value.IsNull()) {
			return 0;
		}

		duckdb::LogicalType target;
		switch (type_id) {
		case VariantLogicalType::BOOL_TRUE:
		case VariantLogicalType::BOOL_FALSE:
			target = LogicalType::BOOLEAN;
			break;
		case VariantLogicalType::INT8:
		case VariantLogicalType::INT16:
		case VariantLogicalType::INT32:
		case VariantLogicalType::INT64:
		case VariantLogicalType::UINT8:
		case VariantLogicalType::UINT16:
		case VariantLogicalType::UINT32:
			target = LogicalType::BIGINT;
			break;
		case VariantLogicalType::UINT64:
			target = LogicalType::UBIGINT;
			break;
		case VariantLogicalType::FLOAT:
			target = LogicalType::FLOAT;
			break;
		case VariantLogicalType::DOUBLE:
			target = LogicalType::DOUBLE;
			break;
		case VariantLogicalType::BLOB:
			target = LogicalType::BLOB;
			break;
		case VariantLogicalType::TIME_MICROS_TZ:
			target = LogicalType::TIME_TZ;
			break;
		case VariantLogicalType::TIMESTAMP_MICROS_TZ:
			target = LogicalType::TIMESTAMP_TZ;
			break;
		case VariantLogicalType::DECIMAL: {
			auto data = const_data_ptr_cast(blob->GetData()) + byte_offset;
			uint8_t width = 0;
			uint8_t shift = 0;
			uint8_t byte;
			do {
				byte = *(data++);
				width = uint8_t(uint32_t(width) | uint32_t(byte & 127) << shift);
				shift += 7;
			} while ((byte & 128) != 0);
			uint8_t scale = 0;
			shift = 0;
			do {
				byte = *(data++);
				scale = uint8_t(uint32_t(scale) | uint32_t(byte & 127) << shift);
				shift += 7;
			} while ((byte & 128) != 0);
			target = LogicalType::DECIMAL(width, scale);
			break;
		}
		case VariantLogicalType::OBJECT:
		case VariantLogicalType::ARRAY:
			target = LogicalType::JSON();
			break;
		/*
			case VariantLogicalType::VARCHAR:
			case VariantLogicalType::BITSTRING:
			case VariantLogicalType::GEOMETRY:
			case VariantLogicalType::INT128:
			case VariantLogicalType::UINT128:
			case VariantLogicalType::BIGNUM:
			case VariantLogicalType::INTERVAL:
			case VariantLogicalType::DATE:
			case VariantLogicalType::UUID:
			case VariantLogicalType::TIMESTAMP_MICROS:
			case VariantLogicalType::TIME_MICROS:
			case VariantLogicalType::TIMESTAMP_SEC:
			case VariantLogicalType::TIMESTAMP_MILIS:
			case VariantLogicalType::TIMESTAMP_NANOS:
			case VariantLogicalType::TIME_NANOS:
		*/
		default:
			target = LogicalType::VARCHAR;
			break;
		}
		auto typed = value.CastAs(*conn_ptr->context, target);
		auto *tmp_vec = new duckdb::Vector(typed);
		*out_vec = reinterpret_cast<duckdb_vector>(tmp_vec);
		*out_type = reinterpret_cast<duckdb_logical_type>(new duckdb::LogicalType(typed.type()));
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
