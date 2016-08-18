default: test

build: ## Install the dependencies
	composer install

update: ## Update the dependencies
	composer update

test: ## Run the test suite
	vendor/bin/phpunit

coverage: ## Record the test coverage
	vendor/bin/phpunit --coverage-html=build/coverage

coverage-show: ## Show the test coverage
	view-coverage

view-coverage: ## Show the test coverage
	open build/coverage/index.html

clean: ## Cleanout the build folder
	rm -rf build/*

full-clean: ## Cleanout build and vendor
	rm -rf build/* vendor

package: ## Build a phar file for release
	php packager.php

help: ## Show help text
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
