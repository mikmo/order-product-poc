# Architettura del Progetto

## Struttura del Progetto

Il progetto segue una struttura Symfony standard con alcune personalizzazioni per supportare le funzionalità specifiche:

```
├── bin/                            # Eseguibili di Symfony
│   ├── console                     # CLI di Symfony
│   └── phpunit                     # Eseguibile per i test
├── config/                         # Configurazione dell'applicazione
│   ├── bundles.php                 # Registrazione dei bundle
│   ├── packages/                   # Configurazioni specifiche per pacchetti
│   │   ├── doctrine.yaml           # Configurazione database
│   │   ├── fos_elastica.yaml       # Configurazione Elasticsearch
│   │   ├── messenger.yaml          # Configurazione message bus
│   │   └── nelmio_api_doc.yaml     # Configurazione documentazione API
│   └── services.yaml               # Definizione dei servizi e dipendenze
├── docker/                         # Configurazione Docker
│   ├── nginx/                      # Configurazione server web
│   └── php/                        # Dockerfile PHP
├── migrations/                     # Migrazioni del database Doctrine
├── public/                         # Entry point pubblico
│   └── index.php                   # Front controller
├── src/                            # Codice sorgente dell'applicazione
│   ├── Command/                    # Comandi CLI personalizzati
│   │   └── ElasticsearchPopulateCommand.php # Comando per popolare Elasticsearch
│   ├── Controller/                 # API controllers
│   │   ├── HealthCheckController.php  # Endpoint di health check
│   │   ├── OrderController.php     # CRUD e ricerca per ordini
│   │   └── Dto/                    # Data Transfer Objects
│   │       ├── CreateOrderRequest.php # DTO per la creazione ordini
│   │       └── UpdateOrderRequest.php # DTO per l'aggiornamento ordini
│   ├── DataFixtures/               # Dati di esempio
│   │   ├── Order/                  # Fixture per gli ordini
│   │   └── Product/                # Fixture per i prodotti
│   ├── Entity/                     # Modelli di dati (Doctrine)
│   │   ├── Order.php               # Entità ordine
│   │   ├── OrderItem.php           # Entità item dell'ordine
│   │   └── Product.php             # Entità prodotto
│   ├── Event/                      # Eventi di dominio
│   │   └── Order/                  # Eventi specifici per ordini
│   ├── Message/                    # Messaggi per Symfony Messenger
│   │   └── OrderIndexMessage.php   # Messaggio per indicizzare ordini
│   ├── MessageHandler/             # Handler per i messaggi
│   │   └── OrderIndexMessageHandler.php # Handler per l'indicizzazione
│   ├── Repository/                 # Repository per l'accesso ai dati
│   │   ├── OrderRepository.php     # Operazioni sul database per ordini
│   │   └── ProductRepository.php   # Operazioni sul database per prodotti
│   └── Service/                    # Servizi di business logic
│       ├── ElasticsearchOrderService.php # Servizio per la ricerca ordini
│       ├── Order/                  # Servizi per la gestione ordini
│       │   └── OrderManagementService.php # Logica CRUD per ordini
│       ├── Product/                # Servizi per la gestione prodotti
│       │   ├── ProductStockManager.php           # Gestione stock prodotti
│       │   └── ProductStockManagerInterface.php  # Interfaccia per inversione dipendenze
│       └── Request/                # Servizi per la gestione delle richieste
│           └── RequestProcessor.php # Validazione e processamento richieste
├── templates/                      # Template Twig (non utilizzati nelle API)
├── tests/                          # Test automatizzati
│   ├── ApiTestCase.php             # Classe base per i test API
│   ├── Functional/                 # Test funzionali
│   │   └── Controller/             # Test dei controller
│   │       ├── HealthCheckControllerTest.php # Test health check
│   │       └── OrderControllerTest.php       # Test CRUD ordini
│   └── Unit/                       # Test unitari
│       └── Service/                # Test dei servizi
```

Questa struttura di progetto è organizzata seguendo i principi delle Clean Architecture, con una chiara separazione tra:

- **Presentation Layer**: Controller, DTO e API endpoints
- **Application Layer**: Servizi che orchestrano le operazioni
- **Domain Layer**: Entità, eventi di dominio e logica di business
- **Infrastructure Layer**: Repository, implementazioni concrete dei servizi e integrazioni

## Architettura estensibile e Inversione delle Dipendenze

Il progetto utilizza il pattern dell'inversione delle dipendenze per garantire flessibilità e scalabilità. Un esempio chiave è il sistema di gestione dei prodotti:

- L'interfaccia `ProductStockManagerInterface` definisce un contratto per la gestione degli stock dei prodotti
- L'implementazione corrente `ProductStockManager` utilizza il database locale per gestire i prodotti
- Grazie all'iniezione delle dipendenze configurata in `services.yaml`, è possibile sostituire facilmente l'implementazione

```yaml
# config/services.yaml
App\Service\Product\ProductStockManagerInterface: '@App\Service\Product\ProductStockManager'
```

Per rendere il sistema ancora più robusto e orientato alla persistenza dei dati storici, l'architettura implementa un pattern di "snapshot dei prodotti". Quando un prodotto viene aggiunto a un ordine, l'entità `OrderItem` salva non solo il riferimento al prodotto ma anche una copia completa dei dati rilevanti del prodotto (nome, prezzo, ecc.). Questo approccio garantisce che:

- Gli ordini mantengano uno storico dei dati dei prodotti anche se questi vengono in seguito modificati o eliminati
- Sia possibile generare report e statistiche accurate basate sui dati al momento dell'ordine
- Il sistema rimanga resiliente ai cambiamenti esterni nella gestione dei prodotti

Questo design orientato alla storicizzazione, combinato con l'inversione delle dipendenze, permette di:
- Creare implementazioni alternative che si interfacciano con API esterne
- Sostituire il sistema di gestione prodotti senza modificare il codice che lo utilizza
- Supportare facilmente microservizi distribuiti dove i prodotti sono gestiti da un altro sistema
- Implementare strategie di cache o proxy per ottimizzare le prestazioni
- Mantenere l'integrità dei dati storici per esigenze di business intelligence e reportistica
