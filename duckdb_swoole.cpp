/*
 * Swoole interop for pdo_duckdb — see duckdb_swoole.h for the rationale.
 *
 * All Swoole entry points are referenced as weak declarations, so there is no
 * link-time dependency on Swoole and no symbol name is hardcoded in the source
 * (the compiler emits the Itanium-mangled name for the C++ async entry). Weak
 * references bind at load time, so Swoole must be loaded before this extension
 * for the async dispatch to be available; otherwise the calls run
 * synchronously.
 */

#include "php.h"
#include "php_pdo_duckdb.h"
#include "duckdb_swoole.h"
#include <cstdlib>
#include <functional>

#if defined(__GNUC__) || defined(__clang__)
    #include <sched.h>

    /* Weak declarations of the Swoole entry points. Weak references bind at
     * load time: if Swoole is loaded before this extension they resolve, and
     * stay NULL otherwise. */
    extern "C" {
        __attribute__((weak)) uint8_t swoole_coroutine_is_in(void);
        __attribute__((weak)) void swoole_coroutine_usleep(int usec);
    }

    /* The C++ symbol Swoole exports, declared weakly so the compiler generates
    * the mangled name — no hardcoded symbol string in the source. */
    namespace swoole {
        namespace coroutine {
            __attribute__((weak)) bool async(const std::function<void()> &fn);
        }
    } // namespace swoole
#endif

namespace {

/* Run `fn` on Swoole's async thread pool while yielding the current coroutine,
 * or synchronously when Swoole is not available / not inside a coroutine.
 * The connection lock is held for the whole call. On MSVC Swoole support is
 * compiled out entirely, so run inline. */
void swoole_run(void *lock, const std::function<void()> &fn) {
#if defined(__GNUC__) || defined(__clang__)
    if (swoole_coroutine_is_in == NULL || swoole_coroutine_is_in() == 0) {
        fn();
        return;
    }

    unsigned char *busy = static_cast<unsigned char *>(lock);
    while (__atomic_test_and_set(busy, __ATOMIC_ACQUIRE)) {
        /* Connection in use by another coroutine; yield instead of spinning. */
        if (swoole_coroutine_usleep != NULL) {
            swoole_coroutine_usleep(50);
        } else {
            sched_yield();
        }
    }

    if (swoole::coroutine::async != NULL) {
        swoole::coroutine::async(fn);
    } else {
        /* Async entry point missing (e.g. Swoole loaded after this extension);
         * fall back to a synchronous call. */
        fn();
    }

    __atomic_clear(busy, __ATOMIC_RELEASE);
#else
    (void)lock;
    fn();
#endif
}

} // namespace

extern "C" {

/* Whether the Swoole runtime is present (i.e. its symbols resolved at load
 * time). Used by PHP_MINFO(). */
int pdo_duckdb_swoole_loaded(void) {
#if defined(__GNUC__) || defined(__clang__)
    return swoole_coroutine_is_in != NULL;
#else
    return 0;
#endif
}

/* Per-connection serialization lock for the Swoole async dispatch. Plain C11
 * busy flag implemented with GCC/Clang __atomic builtins. Returns NULL when
 * Swoole is not loaded (or support is compiled out), marking the connection as
 * not Swoole-async capable. */
void *pdo_duckdb_thread_lock_new(void) {
#if defined(__GNUC__) || defined(__clang__)
    if (swoole_coroutine_is_in == NULL) {
        return NULL;
    }
    unsigned char *lock = (unsigned char *)malloc(sizeof(unsigned char));
    if (lock) {
        __atomic_clear(lock, __ATOMIC_RELAXED);
    }
    return lock;
#else
    return NULL;
#endif
}

duckdb_state pdo_duckdb_swoole_open_ext(
    void *lock, const char *path, duckdb_database *out_database, duckdb_config config, char **out_error) {
    duckdb_state state = DuckDBError;
    swoole_run(lock, [&]() { state = duckdb_open_ext(path, out_database, config, out_error); });
    return state;
}

duckdb_state pdo_duckdb_swoole_connect(void *lock, duckdb_database database, duckdb_connection *out_connection) {
    duckdb_state state = DuckDBError;
    swoole_run(lock, [&]() { state = duckdb_connect(database, out_connection); });
    return state;
}

duckdb_state pdo_duckdb_swoole_prepare(
    void *lock, duckdb_connection connection, const char *query, duckdb_prepared_statement *out_statement) {
    duckdb_state state = DuckDBError;
    swoole_run(lock, [&]() { state = duckdb_prepare(connection, query, out_statement); });
    return state;
}

duckdb_state pdo_duckdb_swoole_execute_prepared(
    void *lock, duckdb_connection connection, duckdb_prepared_statement statement, duckdb_result *out_result) {
    duckdb_state state = DuckDBError;
    swoole_run(lock, [&]() { state = duckdb_execute_prepared(statement, out_result); });
    return state;
}

duckdb_state pdo_duckdb_swoole_execute_prepared_streaming(
    void *lock, duckdb_connection connection, duckdb_prepared_statement statement, duckdb_result *out_result) {
    duckdb_state state = DuckDBError;
    swoole_run(lock, [&]() { state = duckdb_execute_prepared_streaming(statement, out_result); });
    return state;
}

duckdb_state pdo_duckdb_swoole_query(
    void *lock, duckdb_connection connection, const char *query, duckdb_result *out_result) {
    duckdb_state state = DuckDBError;
    swoole_run(lock, [&]() { state = duckdb_query(connection, query, out_result); });
    return state;
}

duckdb_data_chunk pdo_duckdb_swoole_fetch_chunk(void *lock, duckdb_connection connection, duckdb_result result) {
    duckdb_data_chunk chunk = NULL;
    swoole_run(lock, [&]() { chunk = duckdb_fetch_chunk(result); });
    return chunk;
}

duckdb_data_chunk pdo_duckdb_swoole_result_get_chunk(
    void *lock, duckdb_connection connection, duckdb_result result, idx_t chunk_index) {
    duckdb_data_chunk chunk = NULL;
    swoole_run(lock, [&]() { chunk = duckdb_result_get_chunk(result, chunk_index); });
    return chunk;
}

} // extern "C"
