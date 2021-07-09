# -- Setup ——
SHELL         = bash
PROJECT       = app
EXEC_PHP      = php -d memory_limit=-1
SYMFONY       = $(EXEC_PHP) bin/console
COMPOSER      = composer
GIT           = git
GULP          = gulp
YARN          = yarn
DOCKER        = docker-compose
DOCKER_COMPOSE= $$( if [ -f docker-compose.local.yml ]; then \
		echo docker-compose.local.yml; \
	else \
		echo docker-compose.yml; \
	fi )
.DEFAULT_GOAL = help

.PHONY: assets

## —— Gogocarto Makefile ——
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Composer ————————————
composer-install: composer.lock ## Install Composer vendors according to the current composer.lock file
	$(COMPOSER) install

composer-update: composer.json ## Update vendors according to the composer.json file
	$(COMPOSER) update

## —— Symfony —————————————
sf: ## List all Symfony commands
	$(SYMFONY)

cc: ## Clear the cache
	$(SYMFONY) ca:cl

warmup: ## Warmump the cache
	$(SYMFONY) cache:warmup

fix-perms: ## Fix permissions of all var files
	chmod 777 -R var/ web/uploads/

install-assets: ## Install the assets
	$(SYMFONY) assets:install web/ --symlink

purge: ## Purge cache
	rm -rf var/cache/* 

## —— Yarn —————————————————
yarn-install: yarn.lock ## Install npm vendors according to the current yarn.lock file
	$(YARN) install

build: ## Build the assets
	$(YARN) encore dev
	$(GULP) build	

watch:
	$(GULP) watch &
	$(YARN) encore dev --watch &

## —— Docker ——————————————
docker-build: ## Build Docker images
	$(DOCKER) -f $(DOCKER_COMPOSE) build --pull

up: ## Start the Docker hub
	$(DOCKER) -f $(DOCKER_COMPOSE) up -d

stop: ## Stop the Docker hub
	$(DOCKER) -f $(DOCKER_COMPOSE) stop

down: ## Down the Docker hub
	$(DOCKER) -f $(DOCKER_COMPOSE) down --remove-orphans

shell: ## Start shell inside Docker
	$(DOCKER) -f $(DOCKER_COMPOSE) exec gogocarto $(SHELL)

## —— Project —————————————
commands: ## Display all commands in the project namespace
	$(SYMFONY) list $(PROJECT)

init: install assets load-fixtures purge fix-perms ## Initialize the project

install: composer-install yarn-install ## Install vendors

assets: install-assets build ## Install and build the assets

load-fixtures: ## Create the DB schema, generate DB classes and load fixtures
	$(SYMFONY) doctrine:mongodb:schema:create
	$(SYMFONY) doctrine:mongodb:generate:hydrators
	$(SYMFONY) doctrine:mongodb:generate:proxies
	$(SYMFONY) doctrine:mongodb:fixtures:load -n

update-hydrator-proxies:
	$(SYMFONY) doctrine:mongodb:generate:hydrators
	$(SYMFONY) doctrine:mongodb:generate:proxies

## —— Tests ———————————————
test: phpunit.xml ## Launch unit tests
	./bin/phpunit --stop-on-failure

## —— Coding Standards ————
cs-fix: ## Run php-cs-fixer and fix the code
	./vendor/bin/php-cs-fixer fix src/

show-deploy-message:
	mv web/index.php web/index_backup.php
	cp web/error.php web/index.php
	
hide-deploy-message:
	mv web/index_backup.php web/index.php

log-commands-error:
	tail -n 400 var/log/commands.log | grep -B 1 ERROR

## —— Deploy & Prod ———————

change-remote: ## Update the remote to new gitlab server url (migrated on May 2021)
	git remote set-url origin https://gitlab.com/seballot/gogocarto.git

gogo-update: ## Update a PROD server to the lastest version of gogocarto
	make change-remote
	sudo /usr/bin/systemctl stop cron
	$(GIT) reset --hard master
	$(GIT) pull origin master
	make show-deploy-message
	COMPOSER_MEMORY_LIMIT=-1 $(COMPOSER) install
	$(YARN) install
	$(YARN) encore production	
	$(GULP) build
	$(GULP) production	
	make purge	
	$(SYMFONY) db:migrate	
	make hide-deploy-message
	sudo /usr/bin/systemctl start cron

gogo-quick-update:
	make change-remote
	sudo /usr/bin/systemctl stop cron
	$(GIT) reset --hard master
	$(GIT) pull origin master
	make show-deploy-message
	make purge
	make hide-deploy-message
	sudo /usr/bin/systemctl start cron
