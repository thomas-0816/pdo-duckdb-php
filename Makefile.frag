PHP_TEST_SHARED_EXTENSIONS = ` \
	set -- $(EXTENSION_DIR)/pdo.so; \
	if test -f "$$1"; then \
		echo -n " -d extension=$$1"; \
	fi; \
	set -- $(EXTENSION_DIR)/pdo-*.so; \
	if test -f "$$1"; then \
		echo -n " -d extension=$$1"; \
	fi; \
	set -- $(EXTENSION_DIR)/swoole.so; \
	if test -f "$$1"; then \
		echo -n " -d extension=$$1"; \
	fi; \
	set -- $(EXTENSION_DIR)/swoole-*.so; \
	if test -f "$$1"; then \
		echo -n " -d extension=$$1"; \
	fi; \
	set -- $(EXTENSION_DIR)/mysqlnd.so; \
	if test -f "$$1"; then \
		echo -n " -d extension=$$1"; \
	fi; \
	set -- $(EXTENSION_DIR)/mysqlnd-*.so; \
	if test -f "$$1"; then \
		echo -n " -d extension=$$1"; \
	fi; \
	set -- $(EXTENSION_DIR)/curl.so; \
	if test -f "$$1"; then \
		echo -n " -d extension=$$1"; \
	fi; \
	set -- $(EXTENSION_DIR)/curl-*.so; \
	if test -f "$$1"; then \
		echo -n " -d extension=$$1"; \
	fi; \
	if test "x$(PHP_MODULES)" != "x"; then \
		for i in $(PHP_MODULES)""; do \
			. $$i; \
			if test "x$$dlname" != "xdl_test.so"; then \
				$(top_srcdir)/build/shtool echo -n -- " -d extension=$$dlname"; \
			fi; \
		done; \
	fi`
