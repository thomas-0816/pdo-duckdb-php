<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$basePath = rtrim($_SERVER['argv'][1] ?? '', '/');
if (empty($basePath) || !is_dir($basePath)) {
	echo 'usage: php create_duckdb_hpp.php <duckdb-src-dir>', PHP_EOL;
	exit(1);
}

$includePaths = [
	$basePath . '/src/include',
	$basePath . '/third_party/fmt',
	$basePath . '/third_party/fsst',
	$basePath . '/third_party/miniz',
	$basePath . '/third_party/re2',
	$basePath . '/third_party/hyperloglog',
	$basePath . '/third_party/skiplist',
	$basePath . '/third_party/fastpforlib',
	$basePath . '/third_party/utf8proc',
	$basePath . '/third_party/mbedtls',
	$basePath . '/third_party/yyjson',
	$basePath . '/third_party/zstd',
	$basePath . '/third_party/jemalloc',
];
$mainHeaderFiles = [
	$basePath . '/src/include/duckdb.hpp',
	$basePath . '/src/include/duckdb/common/adbc/adbc.h',
	$basePath . '/src/include/duckdb/common/adbc/adbc.hpp',
	$basePath . '/src/include/duckdb/common/arrow/arrow.hpp',
	$basePath . '/src/include/duckdb/common/arrow/arrow_converter.hpp',
	$basePath . '/src/include/duckdb/common/arrow/arrow_wrapper.hpp',
	$basePath . '/src/include/duckdb/common/types/blob.hpp',
	$basePath . '/src/include/duckdb/common/types/decimal.hpp',
	$basePath . '/src/include/duckdb/common/types/time.hpp',
	$basePath . '/src/include/duckdb/common/types/uuid.hpp',
	$basePath . '/src/include/duckdb/common/serializer/memory_stream.hpp',
	$basePath . '/src/include/duckdb/parser/parsed_data/create_copy_function_info.hpp',
	$basePath . '/src/include/duckdb/parser/parsed_data/create_table_function_info.hpp',
];

echo 'Writing duckdb.hpp', PHP_EOL;

$writtenFiles = [];
$contents = '#pragma once' . PHP_EOL;
$contents .= '#define DUCKDB_MAJOR_VERSION 2' . PHP_EOL;
$contents .= '#define DUCKDB_MINOR_VERSION 0' . PHP_EOL;

foreach ($mainHeaderFiles as $file) {
	$contents .= getContent($file, $writtenFiles, $includePaths);
}
file_put_contents(__DIR__ . '/duckdb.hpp', $contents);


function getIncludes(string $text, array $includePaths): array
{
	$statements = [];
	$files = [];
	preg_match_all('/^[\t ]*[#][\t ]*include[\t ]+"([^"]+)"/m', $text, $matches, PREG_SET_ORDER);
	foreach ($matches as $x) {
		$statement = $x[0];
		$includedFile = $x[1];
		if (in_array($statement, $statements, true)) {
			continue;
		}
		$statements[] = $statement;
		$found = false;
		foreach ($includePaths as $includePath) {
			$file = $includePath . '/' . $includedFile;
			if (file_exists($file)) {
				$files[] = $file;
				$found = true;
				break;
			}
		}
		if (! $found) {
			throw new Exception('Could not find ' . $includedFile);
		}
	}

	return [$statements, $files];
}

function getContent(string $currentFile, array &$writtenFiles, array $includePaths): string
{
	if (array_key_exists($currentFile, $writtenFiles)) {
		return '';
	}
	$writtenFiles[$currentFile] = true;
	$text = file_get_contents($currentFile);
	[$statements, $includes] = getIncludes($text, $includePaths);
	foreach ($includes as $key => $include) {
		$text = str_replace($statements[$key], getContent($include, $writtenFiles, $includePaths), $text);
	}

	return str_replace('#pragma once', '', $text);
}
