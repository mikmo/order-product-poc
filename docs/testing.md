# Testing

Il progetto include una suite completa di test automatizzati:

- **Test unitari**: Testano i componenti individuali
- **Test funzionali**: Testano i controller e l'integrazione tra componenti
- **Test API**: Verificano il comportamento degli endpoint

## Esecuzione di tutti i test

Il modo più semplice per eseguire tutti i test è utilizzare lo script `run-tests.sh`:

```bash
./run-tests.sh
```

Questo script prepara l'ambiente di test, configura il database di test e esegue tutti i test nel container PHP. Inoltre, genera automaticamente un report di copertura del codice in formato HTML nella cartella `coverage-html`, che può essere consultato aprendo il file `coverage-html/index.html` in un browser per visualizzare in modo dettagliato quali parti del codice sono coperte dai test.

## Esecuzione di test specifici

In alternativa, puoi accedere direttamente al container PHP per eseguire test specifici:

```bash
# Accedi al container PHP
docker docker-compose exec php-fpm bash

# Esegui tutti i test nell'ambiente test
php bin/phpunit --env=test

# Esegui solo i test di un controller specifico
php bin/phpunit --env=test tests/Functional/Controller/OrderControllerTest.php

# Esegui un singolo test
php bin/phpunit --env=test --filter testCreateOrder tests/Functional/Controller/OrderControllerTest.php
```

## Preparazione manuale dell'ambiente di test

Prima di eseguire i test in questo modo, puoi preparare manualmente il database di test eseguendo:

```bash
php bin/console doctrine:database:drop --if-exists --force --env=test
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:create --env=test
php bin/console doctrine:fixtures:load --no-interaction --env=test
```
