/*
 * Swoole integration for pdo_duckdb.
 *
 * DuckDB itself only provides a blocking C API. When the Swoole extension is
 * loaded and the current code runs inside a coroutine, the blocking DuckDB
 * calls are offloaded to Swoole's async thread pool while the calling
 * coroutine yields, in the same spirit as Swoole's own PDO sqlite
 * hooks.
 *
 * pdo_duckdb never gets a link-time dependency on Swoole. All Swoole entry
 * points — the C symbols swoole_coroutine_is_in / swoole_coroutine_usleep (from
 * swoole_coroutine_api.h) and the C++ swoole::coroutine::async (which has no C
 * export, referenced via a weak declaration so the compiler emits the mangled
 * name) — are declared weak. Weak references bind at load time, so Swoole must
 * be loaded before this extension for the async dispatch to be available;
 * otherwise the calls forward to DuckDB synchronously.
 */

#ifndef PHP_PDO_DUCKDB_SWOOLE_H
#define PHP_PDO_DUCKDB_SWOOLE_H

#include "php_pdo_duckdb.h"

#ifdef __cplusplus
extern "C" {
#endif

/* Per-connection serialization lock: a plain busy flag implemented with
 * GCC/Clang __atomic builtins (malloc'd; caller frees with free()).
 * Returns NULL when Swoole support is compiled out or Swoole is not loaded
 * (i.e. the connection is not Swoole-async capable). */
void *pdo_duckdb_thread_lock_new(void);

/* Per-connection serialization lock.
 *
 * DuckDB connections are not thread-safe. The async thread pool may run two
 * queries on the same connection concurrently (coroutine A yields while its
 * query runs on a worker thread, coroutine B dispatches another query on the
 * same connection). The lock guards the connection for the duration of each
 * call. It is a non-blocking "busy" flag: a coroutine that finds the
 * connection busy yields to the event loop and retries, so other coroutines
 * (and Swoole I/O) keep running while waiting.
 */
duckdb_state pdo_duckdb_swoole_open_ext(
    void *lock, const char *path, duckdb_database *out_database, duckdb_config config, char **out_error);
duckdb_state pdo_duckdb_swoole_connect(void *lock, duckdb_database database, duckdb_connection *out_connection);
duckdb_state pdo_duckdb_swoole_prepare(
    void *lock, duckdb_connection connection, const char *query, duckdb_prepared_statement *out_statement);
duckdb_state pdo_duckdb_swoole_execute_prepared(
    void *lock, duckdb_connection connection, duckdb_prepared_statement statement, duckdb_result *out_result);
duckdb_state pdo_duckdb_swoole_execute_prepared_streaming(
    void *lock, duckdb_connection connection, duckdb_prepared_statement statement, duckdb_result *out_result);
duckdb_state pdo_duckdb_swoole_query(
    void *lock, duckdb_connection connection, const char *query, duckdb_result *out_result);
duckdb_data_chunk pdo_duckdb_swoole_fetch_chunk(void *lock, duckdb_connection connection, duckdb_result result);
duckdb_data_chunk pdo_duckdb_swoole_result_get_chunk(
    void *lock, duckdb_connection connection, duckdb_result result, idx_t chunk_index);

#ifdef __cplusplus
}
#endif

#endif /* PHP_PDO_DUCKDB_SWOOLE_H */
