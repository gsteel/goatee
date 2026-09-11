# Most Laminas & Mezzio Projects use PHP 8.2 as the current minimum PHP Version
# You can re-define the default PHP version in your own Makefile
PHP_VERSION ?= 8.4
# Any PHP extensions should be a space separated list of extension names without prefixes such as "ext-"
# Do not quote this string if you re-define it
PHP_EXTENSIONS ?= mbstring json xdebug
# All of the make targets that execute PHP code run in Docker, and an image is built using the PHP version desired
# This image should be named to something specific to the project (Normally the repo name such as "laminas/laminas-whatever").
DOCKER_IMAGE_NAME ?= gsteel/goatee
# This is the image ID of the built Docker image
DOCKER_IMAGE_ID := $(shell docker images -q ${DOCKER_IMAGE_NAME} | xargs)
DOCKER_DEFAULT_SECURITY_OPS=--cap-drop=ALL --security-opt="no-new-privileges=true" --user="`id -u`:`id -g`"
DOCKER_COMMON_OPS:=-v "`pwd`:`pwd`" -w "`pwd`" -v "`pwd`/.git:`pwd`/.git:ro" --ulimit nofile=1000000
DOCKER_RUN:=docker run --rm -it ${DOCKER_DEFAULT_SECURITY_OPS} ${DOCKER_COMMON_OPS}
# Sets the shell for running make targets
SHELL ?= /bin/bash
# The default Makefile target
.DEFAULT_GOAL ?= help
# The parent directory of _this_ Makefile
_MAKEFILE_DIR := $(dir $(abspath $(lastword $(MAKEFILE_LIST))))
# The Dockerfile to build
DOCKERFILE ?= "${_MAKEFILE_DIR}Dockerfile"
# Docs & Markdown
MARKDOWN_FILE_PATTERN ?= *.md docs/**/*.md
# This is where we can download the Laminas org's Markdown lint configuration for linting local Markdown files
MDLINT_CONFIG_FILE ?= https://raw.githubusercontent.com/laminas/laminas-continuous-integration-action/e321dbdcc74e665512b5d2e8fd9012b3432df897/setup/markdownlint/markdownlint.json
# This is a Docker image for Markdown Lint CLI v2
MDLINT_IMAGE ?= davidanson/markdownlint-cli2:v0.23.2

# Formatting Macros
MK_BLUE = echo -e "\033[34m"$(1)"\033[0m"
MK_GREEN = echo -e "\033[32m"$(1)"\033[0m"
MK_RED = echo -e "\e[31m"$(1)"\e[0m"
MK_INFO = @$(call MK_BLUE, $1)
MK_SUCCESS = @$(call MK_GREEN, $1)
MK_ERROR = @$(call MK_RED, $1)

help: ## shows this help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_\-\.]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
.PHONY: help

# Makefile targets and variables for linting Markdown

docs-lint: .markdownlint.json ## Lint documentation
	@$(call MK_INFO, "Linting documentation files")
	@$(DOCKER_RUN) ${MDLINT_IMAGE} ${MARKDOWN_FILE_PATTERN}
.PHONY: docs-lint

.markdownlint.json: ## Fetch the most recent settings for Markdown lint
	@$(call MK_INFO,"Fetching markdown lint configuration")
	@curl -s -o .markdownlint.json ${MDLINT_CONFIG_FILE}

remove-mdlint-config: ## Remove the downloaded markdown lint config file
ifneq ("$(wildcard .markdownlint.json)", "")
	@rm .markdownlint.json
endif
.PHONY: remove-mdlint-config

build-php-image: ## Build the PHP image if not already built
ifeq ($(strip $(DOCKER_IMAGE_ID)),)
build-php-image: build-php-image-unconditionally
endif
.PHONY: build-php-image

build-php-image-unconditionally: ## Build the PHP image with necessary dependencies
	@$(call MK_INFO,"Building the PHP Docker Image")
	@echo "${PHP_EXTENSIONS}"
	@docker build \
	-f ${DOCKERFILE} \
	--build-arg PHP_VERSION="${PHP_VERSION}" \
	--build-arg PHP_EXTENSIONS="${PHP_EXTENSIONS}" \
	-t ${DOCKER_IMAGE_NAME} .
.PHONY: build-php-image-unconditionally

remove-php-image: ## Remove the PHP image
ifneq ($(strip $(DOCKER_IMAGE_ID)),)
	@$(call MK_INFO,"Removing the PHP Docker Image")
	docker image rm ${DOCKER_IMAGE_ID}
endif
.PHONY: remove-php-image

shell: build-php-image ## Get a shell running in the PHP Docker container
	@$(DOCKER_RUN) ${DOCKER_IMAGE_ID} bash
.PHONY: shell;

test: install ## Run PHPUnit tests
	@$(DOCKER_RUN) ${DOCKER_IMAGE_ID} vendor/bin/phpunit
.PHONY: test

clear-phpunit-cache: ## Clear the PHPUnit Cache
	$(eval PHPUNIT_CACHE_DIR := $(shell xpath -q -e 'string(//phpunit/@cacheDirectory)' phpunit.xml.dist))
ifneq ($(wildcard ${PHPUNIT_CACHE_DIR}), "")
	@$(call MK_INFO, "Clearing PHPUnit Cache")
	@rm -rf ${PHPUNIT_CACHE_DIR}
endif
.PHONY: clear-phpunit-cache

install: build-php-image ## Install composer dependencies from composer.json
ifeq ("$(wildcard vendor)","")
	@$(call MK_INFO,"Installing PHP Dependencies")
	@$(DOCKER_RUN) ${DOCKER_IMAGE_ID} composer install
endif
.PHONY: install

update: install ## Update composer dependencies
	@$(call MK_INFO,"Updating PHP Dependencies")
	@$(DOCKER_RUN) ${DOCKER_IMAGE_ID} composer update
.PHONY: update

outdated: install ## Show outdated dependencies
	@$(DOCKER_RUN) ${DOCKER_IMAGE_ID} composer outdated
.PHONY: outdated

bump-dev: update ## Bump development composer dependencies
	@$(call MK_INFO,"Bumping Development Dependencies")
	@$(DOCKER_RUN) ${DOCKER_IMAGE_ID} composer bump -D
	@$(DOCKER_RUN) ${DOCKER_IMAGE_ID} composer update
.PHONY: bump-dev

composer-validate:
	@$(DOCKER_RUN) ${DOCKER_IMAGE_ID} composer validate --check-lock --strict
.PHONY: composer-validate

composer-autoload:
	@$(DOCKER_RUN) ${DOCKER_IMAGE_ID} composer dump-autoload --optimize --strict-psr
.PHONY: composer-autoload

uninstall:
ifneq ("$(wildcard vendor)","")
	@$(call MK_INFO,"Removing PHP Dependencies")
	rm -rf vendor
endif
.PHONY: uninstall

analyse: install ## Static analysis with Mago
	@$(DOCKER_RUN) ${DOCKER_IMAGE_ID} vendor/bin/mago analyse
.PHONY: analyse

watch: install ## Run Mago SA watcher
	@$(DOCKER_RUN) ${DOCKER_IMAGE_ID} vendor/bin/mago analyse --watch
.PHONY: watch

lint: install ## Linting with Mago
	@$(DOCKER_RUN) ${DOCKER_IMAGE_ID} vendor/bin/mago lint
.PHONY: lint

fmtcheck: install ## CS Checks with Mago
	@$(DOCKER_RUN) ${DOCKER_IMAGE_ID} vendor/bin/mago fmt --check
.PHONY: lint

fmt: install ## Fix CS with Mago
	@$(DOCKER_RUN) ${DOCKER_IMAGE_ID} vendor/bin/mago fmt
.PHONY: fmt

infection: install ## Run mutation tests
	@$(DOCKER_RUN) ${DOCKER_IMAGE_ID} vendor/bin/infection
.PHONY: infection

clean: remove-mdlint-config clear-phpunit-cache uninstall remove-php-image  ## Clean up caches and documentation artifacts
.PHONY: clean

qa: analyse lint fmtcheck test docs-lint composer-validate ## Run all QA targets
.PHONY: qa
