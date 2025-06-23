<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();
        $this->entityManager = null;
        parent::tearDown();
    }

    /**
     * Esegue una richiesta GET all'endpoint specificato
     */
    protected function makeGetRequest(string $endpoint, array $parameters = []): Response
    {
        $this->client->request(
            'GET',
            $endpoint,
            $parameters,
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json']
        );

        return $this->client->getResponse();
    }

    /**
     * Esegue una richiesta POST all'endpoint specificato con i dati JSON forniti
     */
    protected function makePostRequest(string $endpoint, array $data = []): Response
    {
        $this->client->request(
            'POST',
            $endpoint,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode($data)
        );

        return $this->client->getResponse();
    }

    /**
     * Esegue una richiesta PUT all'endpoint specificato con i dati JSON forniti
     */
    protected function makePutRequest(string $endpoint, array $data = []): Response
    {
        $this->client->request(
            'PUT',
            $endpoint,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode($data)
        );

        return $this->client->getResponse();
    }

    /**
     * Esegue una richiesta DELETE all'endpoint specificato
     */
    protected function makeDeleteRequest(string $endpoint, array $parameters = []): Response
    {
        $this->client->request(
            'DELETE',
            $endpoint,
            $parameters,
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json']
        );

        return $this->client->getResponse();
    }

    /**
     * Decodifica la risposta JSON e verifica che sia valida
     */
    protected function getJsonResponseData(Response $response): array
    {
        $content = $response->getContent();
        $this->assertJson($content, 'La risposta non è in formato JSON valido');

        return json_decode($content, true);
    }

    /**
     * Verifica che un array contenga tutte le chiavi specificate
     */
    protected function assertArrayHasKeys(array $array, array $keys): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array, "La chiave '$key' non è presente nella risposta");
        }
    }
}
