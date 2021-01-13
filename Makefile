# Test for modules/foo
ifneq ("$(wildcard ./../ambassador/Makefile)","")
	MOD_PATH = $(abspath ./../ambassador)
	BIN_PATH = $(abspath ./../../vendor/bin)
endif

# Test for vendor/foo/bar
ifneq ("$(wildcard ./../../ontap/ambassador/Makefile)","")
	MOD_PATH = $(abspath ./../../ontap/ambassador)
	BIN_PATH = $(abspath ./../../bin)
endif

# Test for app/code/Vendor/Module
ifneq ("$(wildcard ./../../../../vendor/ontap/ambassador/Makefile)","")
	MOD_PATH = $(abspath ./../../../../vendor/ontap/ambassador)
	BIN_PATH = $(abspath ./../../../../vendor/bin)
endif

include $(MOD_PATH)/Makefile

dist: ## Create a distributable archive
	git archive HEAD -o ./module-mastercard.zip
