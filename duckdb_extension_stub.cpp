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

class ParquetExtension : public Extension {
public:
	void Load(ExtensionLoader &loader) override;
	std::string Name() override;
	std::string Version() const override;
};

class HttpfsExtension : public Extension {
public:
	void Load(ExtensionLoader &loader) override;
	std::string Name() override;
	std::string Version() const override;
};

} // namespace duckdb

namespace duckdb {

class ExtensionHelper {
public:
	static void RegisterLinkedExtensions(DBConfig &config);
};

void ExtensionHelper::RegisterLinkedExtensions(DBConfig &config) {
	config.linked_extensions.push_back(LinkedExtension {"core_functions", [](DuckDB &db) { db.LoadStaticExtension<CoreFunctionsExtension>(); }});
	config.linked_extensions.push_back(LinkedExtension {"json", [](DuckDB &db) { db.LoadStaticExtension<JsonExtension>(); }});
	config.linked_extensions.push_back(LinkedExtension {"icu", [](DuckDB &db) { db.LoadStaticExtension<IcuExtension>(); }});
	config.linked_extensions.push_back(LinkedExtension {"parquet", [](DuckDB &db) { db.LoadStaticExtension<ParquetExtension>(); }});
	config.linked_extensions.push_back(LinkedExtension {"httpfs", [](DuckDB &db) { db.LoadStaticExtension<HttpfsExtension>(); }});
}

} // namespace duckdb
