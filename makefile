# colors
NO_COLOR=\x1b[0m
OK_COLOR=\x1b[32;01m
ERROR_COLOR=\x1b[31;01m
WARN_COLOR=\x1b[33;01m

PROJECT = "Animus REST API App"

all: clear install hint

clear: ;@echo "Clear ${PROJECT}....."; \
	rm -rf vendor

install: ;@echo "Install ${PROJECT}....."; \
	composer install

hint: ;@echo "$(WARN_COLOR)\nUpdate app/config/parameters.yml. For more details have a look at https://github.com/sydev/animus-api \n$(NO_COLOR)";
