# Order-Product API

Un'applicazione di esempio che gestisce ordini e prodotti con un sistema di versioning ottimistico, integrazione Elasticsearch e validazione avanzata. Il progetto è strutturato come un **monolite con architettura MVC** (Model-View-Controller), offrendo un punto di partenza solido e ben organizzato per lo sviluppo di API REST.

## Descrizione del Progetto

Questa API RESTful dimostra una soluzione robusta per la gestione di un sistema di ordini e prodotti, implementando pattern moderni di sviluppo e best practice. Le funzionalità principali includono:

- **Sistema CRUD completo** per ordini e prodotti
- **Versioning ottimistico** per prevenire conflitti di concorrenza
- **Ricerca avanzata** utilizzando Elasticsearch
- **Validazione dei dati** robusta con feedback dettagliato
- **Health check** per monitorare lo stato dei servizi
- **Test automatizzati** per garantire l'affidabilità del codice

## Stack Tecnologico

- **Framework**: [Symfony 6.x](https://symfony.com/doc/6.4/index.html)
- **Database**: MySQL 8.0
- **Ricerca**: [Elasticsearch 7.17](https://www.elastic.co/guide/en/elasticsearch/reference/7.17/index.html) con [FOSElasticaBundle](https://github.com/FriendsOfSymfony/FOSElasticaBundle/blob/master/doc/index.md)
- **Messaging**: [Symfony Messenger](https://symfony.com/doc/6.4/messenger.html) per la comunicazione asincrona
- **Documentazione API**: [Nelmio API Doc](https://github.com/nelmio/NelmioApiDocBundle) con [Swagger PHP](https://zircote.github.io/swagger-php/)
- **Containerizzazione**: Docker e Docker Compose
- **Testing**: PHPUnit
- **Validazione**: Symfony Validator

## Requisiti

- Docker e Docker Compose
- Git
- PHP 8.1+ (per esecuzione locale)
- Composer (per esecuzione locale)

## Installazione

### Utilizzo con Docker (raccomandato)

1. Clona il repository:
   ```bash
   git clone https://github.com/mikmo/order-product-poc.git
   cd order-product-poc
   ```

2. Avvia i container Docker:
   ```bash
   docker-compose up -d
   ```

3. Installa le dipendenze:
   ```bash
   docker-compose exec php-fpm composer install
   ```

4. Crea il database e carica i dati iniziali:
   ```bash
   docker-compose exec php-fpm php bin/console doctrine:migrations:migrate --no-interaction
   ```

5. Carica i dati di esempio (Opzionale):
   ```bash
   docker-compose exec php-fpm php bin/console doctrine:fixtures:load --no-interaction
   ```

6. Cancella e popola l'indice Elasticsearch:
   ```bash
   docker-compose exec php-fpm php bin/console fos:elastica:delete
   docker-compose exec php-fpm php bin/console fos:elastica:populate
   ```

7. Avvia il Symfony Messenger worker per l'elaborazione asincrona dei messaggi:
   ```bash
   docker-compose exec php-fpm php bin/console messenger:consume async -vv
   ```
   Questo comando avvia il worker che si occupa dell'elaborazione dei messaggi in coda, inclusa l'indicizzazione degli ordini in Elasticsearch quando vengono creati o modificati.

## Esecuzione

L'applicazione è accessibile ai seguenti URL:

- **Documentazione API**: http://localhost:8080/api/doc
- **API Rest**: http://localhost:8080/api
- **Adminer** (gestione database): http://localhost:8081
- **Kibana** (interfaccia Elasticsearch): http://localhost:5601

## Testing Rapido

Usa lo script fornito per eseguire tutti i test:

```bash
./run-tests.sh
```

Questo comando prepara l'ambiente di test ed esegue la suite completa di test. Genera anche un report di copertura del codice nella directory `coverage-html`.

## Documentazione Dettagliata

La documentazione dettagliata del progetto è disponibile nei seguenti file:

- [**Architettura del Progetto**](docs/architecture.md) - Struttura del progetto e scelte architetturali
- [**Diagrammi di Flusso dei Servizi**](docs/service-flow-diagrams.md) - Diagrammi Mermaid che illustrano i flussi principali
- [**Riferimento API**](docs/api-reference.md) - Documentazione completa degli endpoint API
- [**Testing**](docs/testing.md) - Guida dettagliata all'esecuzione e creazione di test
- [**Roadmap di Evoluzione**](docs/evolution-roadmap.md) - Piano per l'evoluzione futura verso DDD e CQRS
