#ifndef PHP_PDO_DUCKDB_INT_H
#define PHP_PDO_DUCKDB_INT_H

/* Version information */
#define PHP_PDO_DUCKDB_VERSION "1.0.0"

PHPAPI extern zend_module_entry pdo_duckdb_module_entry;
#define phpext_pdo_duckdb_ptr &pdo_duckdb_module_entry

#ifdef ZTS
#include "TSRM.h"
ZEND_TSRMLS_CACHE_EXTERN()
#endif

/* Include the DuckDB C API header */
#include "duckdb.h"

/* DuckDB v2.x added new type enum values not present in v1.5.x.
   Define them here so the extension compiles against both versions. */
#ifndef DUCKDB_TYPE_TIMESTAMP_TZ_NS
#define DUCKDB_TYPE_TIMESTAMP_TZ_NS 42
#endif

/* Include PDO headers (this brings in pdo_dbh_methods, pdo_stmt_methods, etc.) */
#include "ext/pdo/php_pdo.h"
#include "ext/pdo/php_pdo_driver.h"

/* Forward declarations of the driver and statement method tables.
   Their implementations are in duckdb_driver.c and duckdb_statement.c */
extern struct pdo_dbh_methods   duckdb_methods;
extern struct pdo_stmt_methods  duckdb_stmt_methods;

/* Driver‑specific attributes (mapped to PDO constants) */
enum {
	PDO_DUCKDB_ATTR_UNBUFFERED = PDO_ATTR_DRIVER_SPECIFIC,
	PDO_DUCKDB_ATTR_CONFIG,
	PDO_DUCKDB_ATTR_INIT_COMMAND
};

/* Connection data – one per PDO handle */
typedef struct _pdo_duckdb_db_handle {
	duckdb_database    db;                /* main database object */
	duckdb_connection  conn;              /* active connection */
	unsigned int       attr_flags;        /* internal flag storage */
	char               error_msg[256];    /* last error message */
	int                auto_commit;       /* PDO::ATTR_AUTOCOMMIT */
	int                unbuffered;        /* PDO::DUCKDB_ATTR_UNBUFFERED */
	void              *thread_lock;       /* per-connection async serialization lock */
} pdo_duckdb_db_handle;

/* Statement data – one per PDOStatement handle */
typedef struct _pdo_duckdb_stmt {
	duckdb_prepared_statement stmt;       /* prepared statement */
	duckdb_result             result;     /* result set (after execution) */
	duckdb_data_chunk         chunk;      /* current chunk being read */
	idx_t                     chunk_idx;  /* row offset within current chunk */
	idx_t                     chunk_size; /* number of rows in current chunk */
	int                       done;       /* TRUE when all rows have been fetched */
	int                       result_set; /* TRUE if execute() returned a result set */
	int                       is_streaming; /* TRUE if result is streaming */
	idx_t                     next_chunk_index; /* for non‑streaming results, index of next chunk */
	idx_t                     total_rows;       /* total rows consumed from previous chunks */
	void                     *thread_lock;      /* per-connection async serialization lock */
} pdo_duckdb_stmt;

/* Helpers implemented in duckdb_stubs.cpp */
char *duckdb_get_string(duckdb_connection conn, duckdb_vector vec, idx_t row);
int duckdb_variant_to_vector(duckdb_connection conn, duckdb_vector vec, idx_t row,
                             duckdb_vector *out_vec, duckdb_logical_type *out_type);
void duckdb_free_vector(duckdb_vector vec);
char *duckdb_logical_type_to_string(duckdb_logical_type logical_type);

/* Implemented in duckdb_statement.c */
void duckdb_val_from_vector(duckdb_connection conn, duckdb_vector vec,
	duckdb_logical_type logical_type, idx_t row_idx, zval *result);

#ifdef __cplusplus
extern "C" {
#endif
/* Swoole interop: DuckDB's blocking C API is offloaded to Swoole's async
 * thread pool while inside a coroutine. All Swoole entry points are declared
 * weak (no link-time dependency); weak references bind at load time, so Swoole
 * must load before this extension or calls run synchronously. */
int pdo_duckdb_swoole_loaded(void);              /* 1 iff Swoole loaded before this extension */
void *pdo_duckdb_thread_lock_new(void);          /* new busy-flag lock, or NULL when Swoole unavailable */
/* DuckDB calls serialized through `lock` via Swoole's async thread pool. */
duckdb_state pdo_duckdb_swoole_open_ext(void *lock, const char *path, duckdb_database *out_database, duckdb_config config, char **out_error);
duckdb_state pdo_duckdb_swoole_connect(void *lock, duckdb_database database, duckdb_connection *out_connection);
duckdb_state pdo_duckdb_swoole_prepare(void *lock, duckdb_connection connection, const char *query, duckdb_prepared_statement *out_statement);
duckdb_state pdo_duckdb_swoole_execute_prepared(void *lock, duckdb_connection connection, duckdb_prepared_statement statement, duckdb_result *out_result);
duckdb_state pdo_duckdb_swoole_execute_prepared_streaming(void *lock, duckdb_connection connection, duckdb_prepared_statement statement, duckdb_result *out_result);
duckdb_state pdo_duckdb_swoole_query(void *lock, duckdb_connection connection, const char *query, duckdb_result *out_result);
duckdb_data_chunk pdo_duckdb_swoole_fetch_chunk(void *lock, duckdb_connection connection, duckdb_result result);
duckdb_data_chunk pdo_duckdb_swoole_result_get_chunk(void *lock, duckdb_connection connection, duckdb_result result, idx_t chunk_index);
#ifdef __cplusplus
}
#endif

#endif /* PHP_PDO_DUCKDB_INT_H */
