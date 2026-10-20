#include "duckdb.hpp"

#if DUCKDB_MAJOR_VERSION >= 2

extern "C" int32_t duckdb_extension_core_functions_describe(duckdb_extension_descriptor *descriptor);
extern "C" int32_t duckdb_extension_loadable_extensions_describe(duckdb_extension_descriptor *descriptor);
extern "C" int32_t duckdb_extension_httplib_describe(duckdb_extension_descriptor *descriptor);
extern "C" int32_t duckdb_extension_json_describe(duckdb_extension_descriptor *descriptor);
extern "C" int32_t duckdb_extension_icu_describe(duckdb_extension_descriptor *descriptor);
extern "C" int32_t duckdb_extension_parquet_describe(duckdb_extension_descriptor *descriptor);

namespace {

struct StaticExtensionRegistrar {
	StaticExtensionRegistrar() {
		duckdb_register_static_extension(duckdb_extension_core_functions_describe);
		duckdb_register_static_extension(duckdb_extension_loadable_extensions_describe);
		duckdb_register_static_extension(duckdb_extension_httplib_describe);
		duckdb_register_static_extension(duckdb_extension_json_describe);
		duckdb_register_static_extension(duckdb_extension_icu_describe);
		duckdb_register_static_extension(duckdb_extension_parquet_describe);
	}
};

const StaticExtensionRegistrar static_extension_registrar;

} // namespace

#else

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

class ExtensionHelper {
public:
	static void LoadAllExtensions(DuckDB &db);
};

void ExtensionHelper::LoadAllExtensions(DuckDB &db) {
	db.LoadStaticExtension<CoreFunctionsExtension>();
	db.LoadStaticExtension<JsonExtension>();
	db.LoadStaticExtension<IcuExtension>();
	db.LoadStaticExtension<ParquetExtension>();
}

} // namespace duckdb

#endif
