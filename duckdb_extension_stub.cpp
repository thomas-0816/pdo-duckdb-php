#include "duckdb.hpp"

#if DUCKDB_MAJOR_VERSION >= 2

//! duckdb.hpp already carries duckdb_static_extension.h, so the descriptor layout and the registration entry point
//! below come from there. The extensions themselves only export their describe functions.

extern "C" {
int32_t duckdb_extension_core_functions_describe(duckdb_extension_descriptor *descriptor);
int32_t duckdb_extension_json_describe(duckdb_extension_descriptor *descriptor);
#ifndef PDO_DUCKDB_NO_HTTPFS
int32_t duckdb_extension_httpfs_describe(duckdb_extension_descriptor *descriptor);
int32_t duckdb_extension_inet_describe(duckdb_extension_descriptor *descriptor);
#endif
int32_t duckdb_extension_parquet_describe(duckdb_extension_descriptor *descriptor);
int32_t duckdb_extension_icu_describe(duckdb_extension_descriptor *descriptor);
}

namespace {

//! Bundled extensions register themselves with the engine from a static initializer, so before main and therefore
//! before any connection exists. The engine's own ExtensionHelper::RegisterLinkedExtensions then publishes whatever is
//! in the registry onto every DBConfig, and DuckDB's constructor loads them through LoadAllExtensions. Order below is
//! the load order, so json is registered before icu.
struct StaticExtensionRegistrar {
	StaticExtensionRegistrar() {
		duckdb_register_static_extension(duckdb_extension_core_functions_describe);
		duckdb_register_static_extension(duckdb_extension_json_describe);
#ifndef PDO_DUCKDB_NO_HTTPFS
		duckdb_register_static_extension(duckdb_extension_httpfs_describe);
		duckdb_register_static_extension(duckdb_extension_inet_describe);
#endif
		duckdb_register_static_extension(duckdb_extension_parquet_describe);
		duckdb_register_static_extension(duckdb_extension_icu_describe);
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
}

} // namespace duckdb

#endif
