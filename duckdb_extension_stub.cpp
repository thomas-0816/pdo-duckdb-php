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
	static void LoadAllExtensions(DuckDB &db);
};

void ExtensionHelper::LoadAllExtensions(DuckDB &db) {
	db.LoadStaticExtension<CoreFunctionsExtension>();
	db.LoadStaticExtension<JsonExtension>();
	db.LoadStaticExtension<IcuExtension>();
}

} // namespace duckdb
