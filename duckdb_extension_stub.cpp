#include "duckdb.hpp"

namespace duckdb {

class CoreFunctionsExtension : public Extension {
public:
	void Load(ExtensionLoader &loader) override;
	std::string Name() override;
	std::string Version() const override;
};

class IcuExtension : public Extension {
public:
	void Load(ExtensionLoader &loader) override;
	std::string Name() override;
	std::string Version() const override;
};

class JsonExtension : public Extension {
public:
	void Load(ExtensionLoader &loader) override;
	std::string Name() override;
	std::string Version() const override;
};

} // namespace duckdb

namespace duckdb {

class ExtensionHelper {
public:
#if DUCKDB_MAJOR_VERSION >= 2
	static void RegisterLinkedExtensions(DBConfig &config);
#else
	static void LoadAllExtensions(DuckDB &db);
#endif
};

#if DUCKDB_MAJOR_VERSION >= 2
void ExtensionHelper::RegisterLinkedExtensions(DBConfig &config) {
	config.linked_extensions.push_back(LinkedExtension {"core_functions", [](DuckDB &db) { db.LoadStaticExtension<CoreFunctionsExtension>(); }});
	config.linked_extensions.push_back(LinkedExtension {"json", [](DuckDB &db) { db.LoadStaticExtension<JsonExtension>(); }});
	config.linked_extensions.push_back(LinkedExtension {"icu", [](DuckDB &db) { db.LoadStaticExtension<IcuExtension>(); }});
}
#else
void ExtensionHelper::LoadAllExtensions(DuckDB &db) {
	db.LoadStaticExtension<CoreFunctionsExtension>();
	db.LoadStaticExtension<JsonExtension>();
	db.LoadStaticExtension<IcuExtension>();
}
#endif

} // namespace duckdb
