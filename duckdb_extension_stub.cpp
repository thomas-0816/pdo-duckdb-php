#include "duckdb.hpp"

namespace duckdb {

class ExtensionHelper {
public:
	static void LoadAllExtensions(DuckDB &db);
	static bool TryAutoLoadExtension(DatabaseInstance &db, const std::string &extension_name) noexcept;
};

void ExtensionHelper::LoadAllExtensions(DuckDB &db) {
	ExtensionHelper::TryAutoLoadExtension(*db.instance, "core_functions");
}

} // namespace duckdb
