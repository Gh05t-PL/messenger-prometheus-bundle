DOCKER_COMPOSE ?= docker compose
PHP_VERSION ?= 8.3

export HOST_UID := $(shell id -u)
export HOST_GID := $(shell id -g)
export PHP_VERSION

.PHONY: build install update shell validate test php clean

build:
	$(DOCKER_COMPOSE) build php

install:
	$(DOCKER_COMPOSE) run --rm php composer install

update:
	$(DOCKER_COMPOSE) run --rm php composer update

shell:
	$(DOCKER_COMPOSE) run --rm php bash

validate:
	$(DOCKER_COMPOSE) run --rm php composer validate --strict

test:
	$(DOCKER_COMPOSE) run --rm php composer test

php:
	$(DOCKER_COMPOSE) run --rm php php -v

clean:
	$(DOCKER_COMPOSE) down --remove-orphans
