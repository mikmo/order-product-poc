<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Tests\ApiTestCase;

class OrderControllerTest extends ApiTestCase
{
    private const CREATE_ORDER_ENDPOINT = '/api/orders';
    private const GET_ORDER_ENDPOINT = '/api/orders/{id}';
    private const UPDATE_ORDER_ENDPOINT = '/api/orders/{id}';
    private const DELETE_ORDER_ENDPOINT = '/api/orders/{id}';

    /**
     * Crea un prodotto di test
     */
    private function createTestProduct(): Product
    {
        $product = new Product();
        $product->setName('Prodotto test');
        $product->setPrice(10.0);
        $product->setStock(5);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    /**
     * Crea un ordine di test
     */
    private function createTestOrder(Product $product): Order
    {
        $orderData = [
            'name' => 'Ordine di test',
            'description' => 'Descrizione ordine di test',
            'items' => [
                [
                    'productId' => $product->getId(),
                    'quantity' => 2
                ]
            ]
        ];

        $response = $this->makePostRequest(self::CREATE_ORDER_ENDPOINT, $orderData);
        $responseData = $this->getJsonResponseData($response);

        return $this->entityManager->getRepository(Order::class)->find($responseData['id']);
    }

    /**
     * Test per la creazione di un nuovo ordine
     */
    public function testCreateOrder(): void
    {
        // Prima creiamo un prodotto per testare l'aggiunta di articoli all'ordine
        $product = $this->createTestProduct();
        $initialStock = $product->getStock();

        // Dati di test per la creazione dell'ordine
        $orderData = [
            'name' => 'Ordine di test',
            'description' => 'Descrizione ordine di test',
            'items' => [
                [
                    'productId' => $product->getId(),
                    'quantity' => 2
                ]
            ]
        ];

        // Esegui la richiesta POST per creare un ordine
        $response = $this->makePostRequest(self::CREATE_ORDER_ENDPOINT, $orderData);

        // Verifica che la risposta sia un successo (codice 200)
        $this->assertEquals(200, $response->getStatusCode());

        // Verifica che la risposta contenga i dati dell'ordine creato
        $responseData = $this->getJsonResponseData($response);
        $this->assertArrayHasKey('id', $responseData);

        // Verifica che il prodotto sia stato collegato all'ordine
        $orderId = $responseData['id'];

        // Recupera l'ordine dal database per ulteriori verifiche
        $order = $this->entityManager->getRepository(Order::class)->find($orderId);
        $this->assertNotNull($order);
        $this->assertEquals('Ordine di test', $order->getName());
        $this->assertEquals('Descrizione ordine di test', $order->getDescription());

        // Verifica che la quantità del prodotto sia stata aggiornata (stock decrementato)
        $this->entityManager->refresh($product);
        $this->assertEquals($initialStock - 2, $product->getStock()); // 5 - 2 = 3
    }

    /**
     * Test per la lettura di un ordine
     */
    public function testGetOrder(): void
    {
        $product = $this->createTestProduct();
        $order = $this->createTestOrder($product);

        $endpoint = str_replace('{id}', $order->getId(), self::GET_ORDER_ENDPOINT);

        $response = $this->makeGetRequest($endpoint);

        $this->assertEquals(200, $response->getStatusCode());

        $responseData = $this->getJsonResponseData($response);
        $this->assertArrayHasKeys($responseData, ['id', 'name', 'description', 'version', 'items']);
        $this->assertEquals($order->getId(), $responseData['id']);
        $this->assertEquals('Ordine di test', $responseData['name']);
        $this->assertNotEmpty($responseData['items']);

        $firstItem = $responseData['items'][0];
        $this->assertArrayHasKeys($firstItem, ['productId', 'quantity', 'productName', 'productPrice']);
        $this->assertEquals($product->getId(), $firstItem['productId']);
        $this->assertEquals(2, $firstItem['quantity']);
    }

    /**
     * Test per l'aggiornamento di un ordine
     */
    public function testUpdateOrder(): void
    {
        $product = $this->createTestProduct();
        $order = $this->createTestOrder($product);
        $orderId = $order->getId();
        $version = $order->getVersion();

        // Effettua il detach dell'entità per simulare una nuova richiesta
        $this->entityManager->detach($order);

        $endpoint = str_replace('{id}', $orderId, self::UPDATE_ORDER_ENDPOINT) . '?version=' . $version;

        $updateData = [
            'name' => 'Ordine aggiornato',
            'description' => 'Descrizione aggiornata'
        ];

        $response = $this->makePutRequest($endpoint, $updateData);

        $this->assertEquals(200, $response->getStatusCode());

        $responseData = $this->getJsonResponseData($response);
        $this->assertArrayHasKeys($responseData, ['id', 'version']);
        $this->assertEquals($orderId, $responseData['id']);

        // Recupera nuovamente l'ordine dal database per verificare gli aggiornamenti
        $updatedOrder = $this->entityManager->getRepository(Order::class)->find($orderId);
        $this->assertNotNull($updatedOrder);
        $this->assertEquals('Ordine aggiornato', $updatedOrder->getName());
        $this->assertEquals('Descrizione aggiornata', $updatedOrder->getDescription());

        // La versione dovrebbe essere incrementata
        $this->assertEquals($version + 1, $updatedOrder->getVersion());
    }

    /**
     * Test per l'eliminazione di un ordine
     */
    public function testDeleteOrder(): void
    {
        $product = $this->createTestProduct();
        $order = $this->createTestOrder($product);
        $orderId = $order->getId();
        $version = $order->getVersion();
        $productId = $product->getId();

        // Ottieni lo stock attuale prima dell'eliminazione
        $initialStock = $product->getStock();

        // Effettua il detach delle entità per simulare una nuova richiesta
        $this->entityManager->detach($product);
        $this->entityManager->detach($order);

        $endpoint = str_replace('{id}', $orderId, self::DELETE_ORDER_ENDPOINT) . '?version=' . $version;

        $response = $this->makeDeleteRequest($endpoint);

        $this->assertEquals(200, $response->getStatusCode());

        // Verifica che l'ordine non esista più
        $deletedOrder = $this->entityManager->getRepository(Order::class)->find($orderId);
        $this->assertNull($deletedOrder);

        // Verifica che lo stock del prodotto sia stato ripristinato recuperando il prodotto dal database
        $refreshedProduct = $this->entityManager->getRepository(Product::class)->find($productId);
        $this->assertNotNull($refreshedProduct);
        $this->assertEquals($initialStock + 2, $refreshedProduct->getStock()); // Lo stock dovrebbe essere incrementato di 2
    }

    /**
     * Test per la validazione dei dati durante la creazione di un ordine
     */
    public function testCreateOrderValidation(): void
    {
        // Dati non validi: manca il campo obbligatorio "name"
        $invalidData = [
            'description' => 'Descrizione ordine di test',
            'items' => [
                [
                    'productId' => 999, // ID non esistente
                    'quantity' => 2
                ]
            ]
        ];

        $response = $this->makePostRequest(self::CREATE_ORDER_ENDPOINT, $invalidData);

        // La risposta dovrebbe essere un errore
        $this->assertEquals(400, $response->getStatusCode());

        // Verifica che il messaggio di errore contenga informazioni sulla validazione
        $responseData = $this->getJsonResponseData($response);
        $this->assertArrayHasKey('errors', $responseData);
    }

    /**
     * Test che verifica la gestione di un conflitto di versione durante l'aggiornamento
     */
    public function testVersionConflict(): void
    {
        $product = $this->createTestProduct();
        $order = $this->createTestOrder($product);
        $orderId = $order->getId();
        $invalidVersion = 999; // Versione non valida

        $endpoint = str_replace('{id}', $orderId, self::UPDATE_ORDER_ENDPOINT) . '?version=' . $invalidVersion;

        $updateData = [
            'name' => 'Non dovrebbe funzionare'
        ];

        $response = $this->makePutRequest($endpoint, $updateData);

        // Dovrebbe ritornare un errore 409 Conflict
        $this->assertEquals(409, $response->getStatusCode());

        $responseData = $this->getJsonResponseData($response);
        $this->assertArrayHasKey('error', $responseData);
    }

    /**
     * Test per la ricerca degli ordini
     */
    public function testSearchOrders(): void
    {
        // Mock di ElasticsearchOrderService
        $mockElasticService = $this->createMock(\App\Service\ElasticsearchOrderService::class);

        // Configura il mock per restituire risultati predefiniti
        $mockResults = [
            'total' => 2,
            'page' => 1,
            'size' => 10,
            'maxPages' => 1,
            'orders' => [
                [
                    'id' => 1,
                    'name' => 'Ordine test 1',
                    'description' => 'Descrizione ordine 1',
                    'date' => '2025-06-23T10:00:00+00:00'
                ],
                [
                    'id' => 2,
                    'name' => 'Ordine test 2',
                    'description' => 'Descrizione ordine 2',
                    'date' => '2025-06-23T11:00:00+00:00'
                ]
            ]
        ];

        $mockElasticService->expects($this->once())
            ->method('searchOrders')
            ->willReturn($mockResults);

        // Sostituisci il servizio nel container
        self::getContainer()->set(\App\Service\ElasticsearchOrderService::class, $mockElasticService);

        // Parametri di ricerca
        $queryParams = [
            'term' => 'test',
            'page' => '1',
            'size' => '10'
        ];

        // Esegui la richiesta di ricerca
        $response = $this->makeGetRequest('/api/orders', $queryParams);

        // Verifica che la risposta sia un successo
        $this->assertEquals(200, $response->getStatusCode());

        // Verifica i dati di risposta
        $responseData = $this->getJsonResponseData($response);

        $this->assertArrayHasKeys($responseData, ['total', 'page', 'size', 'maxPages', 'orders']);
        $this->assertEquals(2, $responseData['total']);
        $this->assertEquals(1, $responseData['page']);
        $this->assertEquals(10, $responseData['size']);
        $this->assertCount(2, $responseData['orders']);

        // Verifica il formato di un ordine nella risposta
        $firstOrder = $responseData['orders'][0];
        $this->assertArrayHasKeys($firstOrder, ['id', 'name', 'description', 'date']);
    }

    /**
     * Test per la gestione degli errori di ricerca
     */
    public function testSearchOrdersError(): void
    {
        // Mock di ElasticsearchOrderService che genera un'eccezione
        $mockElasticService = $this->createMock(\App\Service\ElasticsearchOrderService::class);
        $mockElasticService->expects($this->once())
            ->method('searchOrders')
            ->willThrowException(new \Exception('Errore di connessione a Elasticsearch'));

        // Sostituisci il servizio nel container
        self::getContainer()->set(\App\Service\ElasticsearchOrderService::class, $mockElasticService);

        // Parametri di ricerca
        $queryParams = ['term' => 'test'];

        // Esegui la richiesta di ricerca
        $response = $this->makeGetRequest('/api/orders', $queryParams);

        // Verifica che la risposta sia un errore
        $this->assertEquals(400, $response->getStatusCode());

        // Verifica il messaggio di errore
        $responseData = $this->getJsonResponseData($response);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('Errore durante la ricerca', $responseData['error']);
    }

    /**
     * Test della ricerca con filtri di date
     */
    public function testSearchOrdersWithDateFilters(): void
    {
        // Mock di ElasticsearchOrderService
        $mockElasticService = $this->createMock(\App\Service\ElasticsearchOrderService::class);

        // Verifica che il metodo searchOrders venga chiamato con i parametri corretti
        $mockElasticService->expects($this->once())
            ->method('searchOrders')
            ->with(
                $this->equalTo('test'),   // term
                $this->equalTo('test'),   // term (usato due volte come visto nel controller)
                $this->isInstanceOf(\DateTimeImmutable::class),  // dateFrom
                $this->isInstanceOf(\DateTimeImmutable::class),  // dateTo
                $this->equalTo(2),        // page
                $this->equalTo(20)        // size
            )
            ->willReturn([
                'total' => 0,
                'page' => 2,
                'size' => 20,
                'maxPages' => 0,
                'orders' => []
            ]);

        // Sostituisci il servizio nel container
        self::getContainer()->set(\App\Service\ElasticsearchOrderService::class, $mockElasticService);

        // Parametri di ricerca con date e paginazione
        $queryParams = [
            'term' => 'test',
            'dateFrom' => '2025-01-01',
            'dateTo' => '2025-06-30',
            'page' => '2',
            'size' => '20'
        ];

        // Esegui la richiesta di ricerca
        $response = $this->makeGetRequest('/api/orders', $queryParams);

        // Verifica che la risposta sia un successo
        $this->assertEquals(200, $response->getStatusCode());

        // Verifica i dati di risposta
        $responseData = $this->getJsonResponseData($response);
        $this->assertEquals(0, $responseData['total']); // Nessun risultato
        $this->assertEquals(2, $responseData['page']);
        $this->assertEquals(20, $responseData['size']);
        $this->assertEmpty($responseData['orders']);
    }
}
