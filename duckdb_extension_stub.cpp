#include "duckdb.hpp"

namespace duckdb {

class ExtensionHelper {
public:
	static void LoadAllExtensions(DuckDB &db);
};

void ExtensionHelper::LoadAllExtensions(DuckDB &) {
}

} // namespace duckdb
