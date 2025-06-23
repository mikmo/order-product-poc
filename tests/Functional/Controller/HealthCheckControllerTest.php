<?php

namespace App\Tests\Functional\Controller;

use App\Tests\ApiTestCase;
use Doctrine\DBAL\Connection;
use Elastica\Client as ElasticClient;
use Elastica\Cluster;
use Elastica\Cluster\Health;

class HealthCheckControllerTest extends ApiTestCase
{
    private const HEALTH_ENDPOINT = 'api/health';

    /**
     * Test che verifica il corretto funzionamento quando tutti i servizi sono disponibili
     */
    public function testAllServicesWorking(): void
    {
        // Mock della connessione database funzionante
        $mockConnection = $this->createMock(Connection::class);
        $mockConnection->expects($this->once())->method('connect');

        // Mock del client Elasticsearch funzionante
        $mockHealth = $this->createMock(Health::class);

        $mockCluster = $this->createMock(Cluster::class);
        $mockCluster->expects($this->once())->method('getHealth')->willReturn($mockHealth);

        $mockElasticClient = $this->createMock(ElasticClient::class);
        $mockElasticClient->expects($this->once())->method('getCluster')->willReturn($mockCluster);

        // Sostituzione dei servizi nel container
        self::getContainer()->set(Connection::class, $mockConnection);
        self::getContainer()->set(ElasticClient::class, $mockElasticClient);

        // Esegui la richiesta usando il metodo helper della classe ApiTestCase
        $response = $this->makeGetRequest(self::HEALTH_ENDPOINT);

        // Verifica il codice di stato
        $this->assertEquals(200, $response->getStatusCode());

        // Usa il metodo helper per decodificare la risposta JSON
        $responseData = $this->getJsonResponseData($response);

        // Verifica il corretto formato della risposta
        $this->assertEquals('ok', $responseData['status']);
        $this->assertEquals('ok', $responseData['services']['database']);
        $this->assertEquals('ok', $responseData['services']['elasticsearch']);
        $this->assertArrayHasKey('timestamp', $responseData);
    }

    /**
     * Test che verifica il comportamento quando il database non è disponibile
     */
    public function testDatabaseUnavailable(): void
    {
        // Mock della connessione database fallimentare
        $mockConnection = $this->createMock(Connection::class);
        $mockConnection->expects($this->once())
            ->method('connect')
            ->willThrowException(new \Exception('Errore di connessione al database'));

        // Mock del client Elasticsearch funzionante
        $mockHealth = $this->createMock(Health::class);

        $mockCluster = $this->createMock(Cluster::class);
        $mockCluster->expects($this->once())->method('getHealth')->willReturn($mockHealth);

        $mockElasticClient = $this->createMock(ElasticClient::class);
        $mockElasticClient->expects($this->once())->method('getCluster')->willReturn($mockCluster);

        // Sostituzione dei servizi nel container
        self::getContainer()->set(Connection::class, $mockConnection);
        self::getContainer()->set(ElasticClient::class, $mockElasticClient);

        // Esegui la richiesta usando il metodo helper della classe ApiTestCase
        $response = $this->makeGetRequest(self::HEALTH_ENDPOINT);

        // Usa il metodo helper per decodificare la risposta JSON
        $responseData = $this->getJsonResponseData($response);

        // Verifica che lo stato sia degradato e che il database risulti non disponibile
        $this->assertEquals('degraded', $responseData['status']);
        $this->assertEquals('ko', $responseData['services']['database']);
        $this->assertEquals('ok', $responseData['services']['elasticsearch']);
        $this->assertArrayHasKey('message', $responseData['errors']['database']);
    }

    /**
     * Test che verifica il comportamento quando Elasticsearch non è disponibile
     */
    public function testElasticsearchUnavailable(): void
    {
        // Mock della connessione database funzionante
        $mockConnection = $this->createMock(Connection::class);
        $mockConnection->expects($this->once())->method('connect');

        // Mock del client Elasticsearch fallimentare
        $mockCluster = $this->createMock(Cluster::class);
        $mockCluster->expects($this->once())
            ->method('getHealth')
            ->willThrowException(new \Exception('Errore di connessione a Elasticsearch'));

        $mockElasticClient = $this->createMock(ElasticClient::class);
        $mockElasticClient->expects($this->once())->method('getCluster')->willReturn($mockCluster);

        // Sostituzione dei servizi nel container
        self::getContainer()->set(Connection::class, $mockConnection);
        self::getContainer()->set(ElasticClient::class, $mockElasticClient);

        // Esegui la richiesta usando il metodo helper della classe ApiTestCase
        $response = $this->makeGetRequest(self::HEALTH_ENDPOINT);

        // Usa il metodo helper per decodificare la risposta JSON
        $responseData = $this->getJsonResponseData($response);

        // Verifica che lo stato sia degradato e che Elasticsearch risulti non disponibile
        $this->assertEquals('degraded', $responseData['status']);
        $this->assertEquals('ok', $responseData['services']['database']);
        $this->assertEquals('ko', $responseData['services']['elasticsearch']);
        $this->assertArrayHasKey('message', $responseData['errors']['elasticsearch']);
    }

    /**
     * Test che verifica il comportamento quando entrambi i servizi non sono disponibili
     */
    public function testAllServicesUnavailable(): void
    {
        // Mock della connessione database fallimentare
        $mockConnection = $this->createMock(Connection::class);
        $mockConnection->expects($this->once())
            ->method('connect')
            ->willThrowException(new \Exception('Errore di connessione al database'));

        // Mock del client Elasticsearch fallimentare
        $mockCluster = $this->createMock(Cluster::class);
        $mockCluster->expects($this->once())
            ->method('getHealth')
            ->willThrowException(new \Exception('Errore di connessione a Elasticsearch'));

        $mockElasticClient = $this->createMock(ElasticClient::class);
        $mockElasticClient->expects($this->once())->method('getCluster')->willReturn($mockCluster);

        // Sostituzione dei servizi nel container
        self::getContainer()->set(Connection::class, $mockConnection);
        self::getContainer()->set(ElasticClient::class, $mockElasticClient);

        // Esegui la richiesta usando il metodo helper della classe ApiTestCase
        $response = $this->makeGetRequest(self::HEALTH_ENDPOINT);

        // Usa il metodo helper per decodificare la risposta JSON
        $responseData = $this->getJsonResponseData($response);

        // Verifica che lo stato sia degraded (non "down") e che entrambi i servizi risultino non disponibili
        // Il controller imposta lo stato a "degraded" anche quando entrambi i servizi sono non disponibili
        $this->assertEquals('degraded', $responseData['status']);
        $this->assertEquals('ko', $responseData['services']['database']);
        $this->assertEquals('ko', $responseData['services']['elasticsearch']);
        $this->assertArrayHasKey('message', $responseData['errors']['database']);
        $this->assertArrayHasKey('message', $responseData['errors']['elasticsearch']);
    }
}
