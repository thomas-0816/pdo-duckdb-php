TEST_EXTENSIONS = $(shell echo "$${TEST_EXTENSIONS}")
PHP_TEST_SHARED_EXTENSIONS = ` \
	set -- $(EXTENSION_DIR)/pdo.so; \
	if test -f "$$1"; then \
		echo -n " -d extension=$$1"; \
	fi; \
	set -- $(EXTENSION_DIR)/pdo-*.so; \
	if test -f "$$1"; then \
		echo -n " -d extension=$$1"; \
	fi; \
	for ext in $(TEST_EXTENSIONS); do \
		set -- $(EXTENSION_DIR)/$$ext.so; \
		if test -f "$$1"; then \
			echo -n " -d extension=$$1"; \
		fi; \
		set -- $(EXTENSION_DIR)/$$ext-*.so; \
		if test -f "$$1"; then \
			echo -n " -d extension=$$1"; \
		fi; \
	done; \
	if test "x$(PHP_MODULES)" != "x"; then \
		for i in $(PHP_MODULES)""; do \
			. $$i; \
			if test "x$$dlname" != "xdl_test.so"; then \
				$(top_srcdir)/build/shtool echo -n -- " -d extension=$$dlname"; \
			fi; \
		done; \
	fi`
