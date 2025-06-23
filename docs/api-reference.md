# API Reference

Di seguito sono documentati gli endpoint API forniti dall'applicazione.

## Endpoints

### Ordini

- `GET /api/orders` - Ricerca ordini con filtri e paginazione
- `GET /api/orders/{id}` - Ottieni dettagli di un ordine specifico
- `POST /api/orders` - Crea un nuovo ordine
- `PUT /api/orders/{id}` - Aggiorna un ordine (richiede version)
- `DELETE /api/orders/{id}` - Elimina un ordine (richiede version)

### Health Check

- `GET /health` - Verifica lo stato dell'applicazione e dei servizi

## Sistema di Versioning Ottimistico

Il progetto implementa un sistema di versioning ottimistico per prevenire conflitti di aggiornamento degli ordini. Ogni operazione di modifica o cancellazione richiede il parametro `version` che deve corrispondere alla versione corrente dell'ordine nel database. Se la versione non corrisponde, l'API restituisce un errore 409 Conflict.

Esempio:
```json
PUT /api/orders/123?version=2
{
    "name": "Ordine aggiornato"
}
```

## Validazione dei Dati

La validazione dei dati utilizza i constraint di Symfony e un sistema personalizzato di DTO (Data Transfer Objects) per garantire l'integrità dei dati. I messaggi di errore sono localizzati e forniscono un feedback dettagliato all'utente.

## Ricerca con Elasticsearch

L'implementazione della ricerca utilizza Elasticsearch per permettere query complesse su ordini, inclusi filtri per data e ricerca testuale. Le operazioni CRUD su ordini vengono automaticamente sincronizzate con l'indice Elasticsearch tramite eventi di dominio processati in modo asincrono.
