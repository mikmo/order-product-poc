#!/bin/bash

# Fermati se ci sono errori
set -e

# Colori per il terminale
GREEN="\033[32m"
YELLOW="\033[33m"
RED="\033[31m"
RESET="\033[0m"

echo -e "${YELLOW}Preparazione del database di test...${RESET}"

# Esegui i comandi nel container PHP utilizzando --env=test invece di APP_ENV=test
echo -e "${YELLOW}Eliminazione database di test esistente...${RESET}"
docker-compose exec -T php-fpm bash -c "php bin/console doctrine:database:drop --if-exists --force --env=test"

echo -e "${YELLOW}Creazione database di test...${RESET}"
docker-compose exec -T php-fpm bash -c "php bin/console doctrine:database:create --env=test"

echo -e "${YELLOW}Creazione schema database...${RESET}"
docker-compose exec -T php-fpm bash -c "php bin/console doctrine:schema:create --env=test"

echo -e "${GREEN}Database di test preparato con successo!${RESET}"

# Esegui i test
# Per PHPUnit manteniamo APP_ENV=test perché è così che funziona meglio
echo -e "${YELLOW}Esecuzione dei test...${RESET}"
docker-compose exec -T php-fpm bash -c "APP_ENV=test php bin/phpunit --testdox --coverage-text --colors=always --coverage-html coverage-html $@"
