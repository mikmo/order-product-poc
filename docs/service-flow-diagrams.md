# Flussi di Servizio e Diagrammi

Di seguito sono riportati i principali flussi di interazione tra i servizi dell'applicazione, rappresentati attraverso diagrammi Mermaid.

## Flusso di Creazione degli Ordini

```mermaid
sequenceDiagram
    participant C as Controller
    participant OMS as OrderManagementService
    participant PSM as ProductStockManager
    participant DB as Database
    participant MB as MessageBus
    participant EH as EventHandler
    participant ES as Elasticsearch

    C->>OMS: createOrder(orderData)
    Note over OMS: Inizia transazione
    OMS->>PSM: findProductById(productId)
    PSM->>DB: SELECT da products
    DB-->>PSM: Dati prodotto
    PSM-->>OMS: Dati prodotto

    OMS->>PSM: decreaseStock(product, quantity)
    PSM->>DB: UPDATE products SET stock = stock - quantity
    
    OMS->>DB: INSERT INTO orders
    OMS->>DB: INSERT INTO order_items
    Note over OMS: Commit transazione
    
    OMS->>MB: dispatch(OrderIndexMessage)
    MB-->>OMS: Messaggio inserito in coda
    
    MB->>EH: OrderIndexMessageHandler::__invoke()
    EH->>DB: SELECT ordine completo
    DB-->>EH: Dettagli ordine
    EH->>ES: Indicizza ordine
    
    OMS-->>C: Ordine creato
```

## Architettura del Sistema di Elaborazione Ordini

```mermaid
flowchart TB
    subgraph "Controller Layer"
        OC[OrderController]
    end
    
    subgraph "Service Layer"
        OMS[OrderManagementService]
        PSM[ProductStockManager]
        EOS[ElasticsearchOrderService]
    end
    
    subgraph "Messaging & Events"
        MB[MessageBus]
        EH[EventHandler]
    end
    
    subgraph "Data Layer"
        SQL[(MySQL)]
        ES[(Elasticsearch)]
    end
    
    OC -->|"1. Richiesta API"| OMS
    OMS -->|"2. Verifica disponibilità"| PSM
    PSM -->|"3. Query/Update"| SQL
    OMS -->|"4. Salva ordine"| SQL
    OMS -->|"5. Dispatch evento"| MB
    MB -->|"6. Async processing"| EH
    EH -->|"7. Indicizza"| ES
    
    OC -->|"Ricerca ordini"| EOS
    EOS -->|"Query"| ES
```

## Versioning Ottimistico

```mermaid
sequenceDiagram
    participant C1 as Client 1
    participant C2 as Client 2
    participant API as API
    participant DB as Database
    
    C1->>API: GET /orders/123
    API->>DB: SELECT * FROM orders WHERE id = 123
    DB-->>API: Order(id:123, version:1)
    API-->>C1: Order(id:123, version:1)
    
    C2->>API: GET /orders/123
    API->>DB: SELECT * FROM orders WHERE id = 123
    DB-->>API: Order(id:123, version:1)
    API-->>C2: Order(id:123, version:1)
    
    C1->>API: PUT /orders/123?version=1 {name:"New Name"}
    API->>DB: UPDATE orders SET name="New Name", version=2 WHERE id=123 AND version=1
    DB-->>API: 1 row affected
    API-->>C1: Order(id:123, version=2)
    
    C2->>API: PUT /orders/123?version=1 {name:"Another Name"}
    API->>DB: UPDATE orders SET name="Another Name", version=2 WHERE id=123 AND version=1
    DB-->>API: 0 rows affected
    API-->>C2: 409 Conflict - Order was updated by someone else
```

## Flusso di Indicizzazione Elasticsearch via Messenger

```mermaid
flowchart LR
    subgraph "Transazione Principale"
        A[App] -->|"1. Crea/Modifica Ordine"| B[Database]
        A -->|"2. Dispatch Messaggio"| C[Message Bus]
    end
    
    subgraph "Processo Asincrono"
        C -->|"3. Consume"| D[Message Handler]
        D -->|"4. Load Ordine"| B
        D -->|"5. Indice"| E[Elasticsearch]
    end
    
    style A fill:#d4f1f9,stroke:#333
    style B fill:#ffcccc,stroke:#333
    style C fill:#d5f5e3,stroke:#333
    style D fill:#d5f5e3,stroke:#333
    style E fill:#faebcc,stroke:#333
```

## Gestione dello Stock dei Prodotti

```mermaid
stateDiagram-v2
    [*] --> Disponibile: Creazione prodotto
    Disponibile --> ScarsoInventario: Quantità < soglia minima
    ScarsoInventario --> Disponibile: Rifornimento
    ScarsoInventario --> NonDisponibile: Quantità = 0
    NonDisponibile --> Disponibile: Rifornimento
    
    state Disponibile {
        [*] --> Normale
        Normale --> Alta: Alta domanda
        Alta --> Normale: Normalizzazione
    }
    
    state "Elaborazione Ordine" as Ordine {
        [*] --> VerificaDisponibilità
        VerificaDisponibilità --> DecreaseStock: Disponibile
        VerificaDisponibilità --> RifiutaOrdine: Non disponibile
        DecreaseStock --> [*]: Successo
        RifiutaOrdine --> [*]: Errore
    }
```
