<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Elastica\Client as ElasticClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;


class HealthCheckController extends AbstractController
{
    public function __construct(
        private ?Connection $connection = null,
        private ?ElasticClient $elasticClient = null
    ) {}

    #[Route('/health', name: 'health_check', methods: ['GET'])]
    public function check(): JsonResponse
    {
        $status = [
            'status' => 'ok',
            'services' => [],
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ];

      // Verifica Doctrine
      if ($this->connection) {
        try {
          $this->connection->connect();
          $status['services']['database'] = 'ok';
        } catch (\Exception $e) {
          $status['services']['database'] = 'ko';
          $status['errors']['database']['message'] = $e->getMessage();
          $status['errors']['database']['code'] = $e->getCode();
          $status['status'] = 'degraded';
        }
      }
        // Verifica Elasticsearch
        if ($this->elasticClient) {
            try {
                $this->elasticClient->getCluster()->getHealth();
                $status['services']['elasticsearch'] = 'ok';
            } catch (\Exception $e) {
                $status['services']['elasticsearch'] = 'ko';
                $status['errors']['elasticsearch']['message'] = $e->getMessage();
                $status['errors']['elasticsearch']['code'] = $e->getCode();
                $status['status'] = 'degraded';
            }
        }

        return new JsonResponse($status);
    }
}
