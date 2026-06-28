#ifndef PHP_PDO_DUCKDB_INT_H
#define PHP_PDO_DUCKDB_INT_H

/* Version information */
#define PHP_PDO_DUCKDB_VERSION "1.0.0"

#ifdef ZTS
#include "TSRM.h"
#endif

/* Include the DuckDB C API header */
#include <duckdb.h>

/* Include PDO headers (this brings in pdo_dbh_methods, pdo_stmt_methods, etc.) */
#include "pdo/php_pdo.h"
#include "pdo/php_pdo_driver.h"

/* Module globals – required by ZEND_DECLARE_MODULE_GLOBALS */
ZEND_BEGIN_MODULE_GLOBALS(pdo_duckdb)
	int dummy;
ZEND_END_MODULE_GLOBALS(pdo_duckdb)

/* Forward declarations of the driver and statement method tables.
   Their implementations are in duckdb_driver.c and duckdb_statement.c */
extern struct pdo_dbh_methods   duckdb_methods;
extern struct pdo_stmt_methods  duckdb_stmt_methods;

/* Driver‑specific attributes (mapped to PDO constants) */
enum {
	PDO_DUCKDB_ATTR_UNBUFFERED = PDO_ATTR_DRIVER_SPECIFIC,
	PDO_DUCKDB_ATTR_CONFIG
};

/* Platform thread handle type */
#ifdef _WIN32
typedef HANDLE pdo_duckdb_thread_t;
#else
#include <pthread.h>
typedef pthread_t pdo_duckdb_thread_t;
#endif

/* Connection data – one per PDO handle */
typedef struct _pdo_duckdb_db_handle {
	duckdb_database    db;                /* main database object */
	duckdb_connection  conn;              /* active connection */
	unsigned int       attr_flags;        /* internal flag storage */
	char               error_msg[256];    /* last error message */
	int                auto_commit;       /* PDO::ATTR_AUTOCOMMIT */
	int                unbuffered;        /* PDO::DUCKDB_ATTR_UNBUFFERED */
	int                query_timeout_ms;  /* PDO::ATTR_TIMEOUT in ms (0 = no timeout) */
	pdo_duckdb_thread_t timeout_thread;   /* thread handle (HANDLE on Win32, pthread_t on POSIX) */
	volatile int       timeout_running;   /* flag to signal timeout thread to stop */
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
} pdo_duckdb_stmt;

/* Helpers implemented in duckdb_stubs.cpp */
char *duckdb_get_json_string(duckdb_vector vector, idx_t row);
char *duckdb_get_string(duckdb_vector vec, idx_t row);

/* Timeout helpers (defined in duckdb_driver.c) */
void pdo_duckdb_start_timeout(pdo_duckdb_db_handle *H);
void pdo_duckdb_stop_timeout(pdo_duckdb_db_handle *H);

#endif /* PHP_PDO_DUCKDB_INT_H */
