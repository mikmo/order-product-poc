<?php
//namespace App\Controller;
//
//use App\Service\Order\OrderManagementService;
//use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
//use Symfony\Component\HttpFoundation\JsonResponse;
//use Symfony\Component\HttpFoundation\Request;
//use Symfony\Component\Routing\Annotation\Route;
//use Symfony\Component\Serializer\SerializerInterface;
//
//class OrderController extends AbstractController
//{
//  #[Route('/orders', name: 'create_order', methods: ['POST'])]
//  public function createOrder(Request $request, OrderManagementService $orderManagementService): JsonResponse
//  {
//    $data = json_decode($request->getContent(), true);
//
//    try {
//
//      $order = $orderManagementService->createOrder($data);
//
//      return $this->json([
//        'id'   => $order->getId(),
//        'date' => $order->getDate()->format('Y-m-d H:i:s')
//      ]);
//    } catch (\RuntimeException $e) {
//      return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 400);
//    }
//  }
//
//  #[Route('/orders/{id}', name: 'update_order', methods: ['PUT'])]
//  public function updateOrder(
//    int $id,
//    Request $request,
//    OrderManagementService $orderManagementService
//  ): JsonResponse {
//    $version = (int)$request->query->get('version');
//    $data = json_decode($request->getContent(), true);
//    try {
//      $orderManagementService->updateOrder($id, $data, $version);
//      return $this->json(['success' => true]);
//    } catch (\RuntimeException $e) {
//      return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 400);
//    }
//  }
//
//  #[Route('orders/{id}', name: 'delete_order', methods: ['DELETE'])]
//  public function deleteOrder(
//    int $id,
//    Request $request,
//    OrderManagementService $orderManagementService
//  ): JsonResponse {
//    $version = (int)$request->query->get('version');
//    try {
//      $orderManagementService->deleteOrder($id, $version);
//      return $this->json(['success' => true]);
//    } catch (\RuntimeException $e) {
//      return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 400);
//    }
//  }
//
//  #[Route('orders/{id}', name: 'get_order', methods: ['GET'])]
//  public function getOrder(int $id, OrderManagementService $orderManagementService, SerializerInterface $serializer): JsonResponse
//  {
//    try {
//      $order = $orderManagementService->getOrderById($id);
//
//      if (!$order) {
//        return $this->json(['error' => 'Order not found'], 404);
//      }
//
//      $orderJson = $serializer->normalize($order, null, ['groups' => 'order:read']);
//
//      return $this->json($orderJson);
//
//    } catch (\Exception $e) {
//      dump($e);
//      return $this->json(['error' => 'Error retrieving the order: ' . $e->getMessage()], 400);
//    }
//  }
//}


namespace App\Controller;

use App\Controller\Dto\CreateOrderRequest;
use App\Controller\Dto\UpdateOrderRequest;
use App\Service\ElasticsearchOrderService;
use App\Service\Order\OrderManagementService;
use App\Service\Request\RequestProcessor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Orders')]
class OrderController extends AbstractController
{
  #[Route('/orders', name: 'create_order', methods: ['POST'])]
  #[OA\RequestBody(
    description: 'Dati dell\'ordine',
    content: new OA\JsonContent(
      properties: [
        new OA\Property(property: "name", type: "string", example: "Ordine cliente X"),
        new OA\Property(property: "description", type: "string", example: "Descrizione ordine"),
        new OA\Property(property: "items", type: "array", items: new OA\Items(
          properties: [
            new OA\Property(property: "productId", type: "integer", example: 1),
            new OA\Property(property: "quantity", type: "integer", example: 2)
          ],
          type: "object"
        ))
      ],
      type: "object"
    )
  )]
  #[OA\Response(
    response: 200,
    description: 'Ordine creato con successo',
    content: new OA\JsonContent(
      properties: [
        new OA\Property(property: "id", type: "integer")
      ]
    )
  )]
  #[OA\Response(
    response: 400,
    description: 'Errore nella creazione dell\'ordine'
  )]
  public function createOrder(
    Request $request,
    OrderManagementService $orderManagementService,
    SerializerInterface $serializer,
    RequestProcessor $requestProcessor
  ): JsonResponse
  {
    // Utilizzo del RequestProcessor per validare e deserializzare la richiesta
    $result = $requestProcessor->processRequest($request, CreateOrderRequest::class);

    // Verifica se ci sono errori di validazione
    if ($result['errors']) {
      return $this->json(['errors' => $result['errors']], 400);
    }

    try {
      // Estrai i dati dal DTO
      $orderData = [
        'name' => $result['data']->getName(),
        'description' => $result['data']->getDescription(),
        'items' => $result['data']->getItems()
      ];

      $order = $orderManagementService->createOrder($orderData);

      $orderJson = $serializer->normalize($order, null, ['groups' => 'order:create']);

      return $this->json($orderJson);

    } catch (\RuntimeException $e) {
      return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 400);
    }
  }

  #[Route('/orders/{id}', name: 'update_order', methods: ['PUT'])]
  #[OA\Parameter(name: 'id', in: 'path', description: 'ID dell\'ordine', required: true, schema: new OA\Schema(type: 'integer'))]
  #[OA\Parameter(name: 'version', in: 'query', description: 'Versione dell\'ordine', required: true, schema: new OA\Schema(type: 'integer'))]
  #[OA\RequestBody(
    description: 'Dati per aggiornare l\'ordine',
    content: new OA\JsonContent(
      properties: [
        new OA\Property(property: "name", type: "string"),
        new OA\Property(property: "description", type: "string"),
        new OA\Property(property: "items", type: "array", items: new OA\Items(
          properties: [
            new OA\Property(property: "productId", type: "integer"),
            new OA\Property(property: "quantity", type: "integer")
          ],
          type: "object"
        ))
      ]
    )
  )]
  #[OA\Response(
    response: 200,
    description: 'Ordine creato con successo',
    content: new OA\JsonContent(
      properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "version", type: "integer")
      ]
    )
  )]
  #[OA\Response(response: 400, description: 'Errore nell\'aggiornamento dell\'ordine')]
  #[OA\Response(response: 409, description: 'Conflitto di versione')]
  public function updateOrder(
    int                    $id,
    Request                $request,
    OrderManagementService $orderManagementService,
    SerializerInterface    $serializer,
    RequestProcessor       $requestProcessor
  ): JsonResponse
  {
    $version = (int)$request->query->get('version');

    // Utilizzo del RequestProcessor per validare e deserializzare la richiesta
    $result = $requestProcessor->processRequest($request, UpdateOrderRequest::class);

    // Verifica se ci sono errori di validazione
    if ($result['errors']) {
      return $this->json(['errors' => $result['errors']], 400);
    }

    try {
      // Passiamo tutti i dati validati direttamente al servizio
      // Il servizio si occuperà di gestire i campi nulli o vuoti
      $orderData = [
        'name' => $result['data']->getName(),
        'description' => $result['data']->getDescription(),
        'items' => $result['data']->getItems(),
      ];

      $order = $orderManagementService->updateOrder($id, $orderData, $version);
      $orderJson = $serializer->normalize($order, null, ['groups' => 'order:update']);

      return $this->json($orderJson);
    } catch (\RuntimeException $e) {
      return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 400);
    }
  }

  #[Route('orders/{id}', name: 'delete_order', methods: ['DELETE'])]
  #[OA\Parameter(name: 'id', in: 'path', description: 'ID dell\'ordine', required: true, schema: new OA\Schema(type: 'integer'))]
  #[OA\Parameter(name: 'version', in: 'query', description: 'Versione dell\'ordine', required: true, schema: new OA\Schema(type: 'integer'))]
  #[OA\Response(
    response: 200,
    description: 'Ordine creato con successo',
    content: new OA\JsonContent(
      properties: [
        new OA\Property(property: "id", type: "integer"),
      ]
    )
  )]
  #[OA\Response(response: 400, description: 'Errore nell\'eliminazione dell\'ordine')]
  #[OA\Response(response: 409, description: 'Conflitto di versione')]
  public function deleteOrder(
    int                    $id,
    Request                $request,
    OrderManagementService $orderManagementService
  ): JsonResponse
  {
    $version = (int)$request->query->get('version');
    try {
      $orderManagementService->deleteOrder($id, $version);
      return $this->json(['id' => $id]);
    } catch (\RuntimeException $e) {
      return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 400);
    }
  }

  #[Route('orders/{id}', name: 'get_order', methods: ['GET'])]
  #[OA\Parameter(name: 'id', in: 'path', description: 'ID dell\'ordine', required: true, schema: new OA\Schema(type: 'integer'))]
  #[OA\Response(
    response: 200,
    description: 'Dettagli dell\'ordine',
    content: new OA\JsonContent(
      properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Ordine cliente X"),
        new OA\Property(property: "description", type: "string", example: "Descrizione ordine"),
        new OA\Property(property: "version", type: "integer", example: 1),
        new OA\Property(
          property: "items",
          type: "array",
          items: new OA\Items(
            properties: [
              new OA\Property(property: "id", type: "integer", example: 1),
              new OA\Property(property: "productId", type: "integer", example: 1),
              new OA\Property(property: "productName", type: "string", example: "Prodotto A"),
              new OA\Property(property: "productPrice", type: "number", format: "float", example: 10.99),
              new OA\Property(property: "quantity", type: "integer", example: 2)
            ],
            type: "object"
          )
        )
      ]
    )
  )]
  #[OA\Response(response: 404, description: 'Ordine non trovato')]
  public function getOrder(int $id, OrderManagementService $orderManagementService, SerializerInterface $serializer): JsonResponse
  {
    try {
      $order = $orderManagementService->getOrderById($id);

      if (!$order) {
        return $this->json(['error' => 'Order not found'], 404);
      }

      $orderJson = $serializer->normalize($order, null, ['groups' => 'order:read']);

      return $this->json($orderJson);

    } catch (\Exception $e) {
      return $this->json(['error' => 'Error retrieving the order: ' . $e->getMessage()], 400);
    }
  }


  #[Route('orders', name: 'get_order_search', methods: ['GET'])]
  #[OA\Parameter(name: 'term', in: 'query', description: 'Termine di ricerca per nome e descrizione', required: false, schema: new OA\Schema(type: 'string'))]
  #[OA\Parameter(name: 'dateFrom', in: 'query', description: 'Data inizio (formato ISO)', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
  #[OA\Parameter(name: 'dateTo', in: 'query', description: 'Data fine (formato ISO)', required: false, schema: new OA\Schema(type: 'string', format: 'date'))]
  #[OA\Parameter(name: 'page', in: 'query', description: 'Numero pagina', required: false, schema: new OA\Schema(type: 'integer', default: 1))]
  #[OA\Parameter(name: 'size', in: 'query', description: 'Risultati per pagina', required: false, schema: new OA\Schema(type: 'integer', default: 10))]
  #[OA\Response(
    response: 200,
    description: 'Lista ordini filtrati',
    content: new OA\JsonContent(
      properties: [
        new OA\Property(property: "total", type: "integer", example: 42),
        new OA\Property(property: "page", type: "integer", example: 1),
        new OA\Property(property: "size", type: "integer", example: 10),
        new OA\Property(property: "maxPages", type: "integer", example: 5),
        new OA\Property(
          property: "orders",
          type: "array",
          items: new OA\Items(
            properties: [
              new OA\Property(property: "id", type: "integer", example: 1),
              new OA\Property(property: "name", type: "string", example: "Ordine cliente X"),
              new OA\Property(property: "description", type: "string", example: "Descrizione ordine"),
              new OA\Property(property: "date", type: "string", example: "2023-05-15T14:30:00+00:00")
            ],
            type: "object"
          )
        )
      ]
    )
  )]
  #[OA\Response(response: 400, description: 'Errore nella ricerca')]
  public function searchOrders(
    Request $request,
    ElasticsearchOrderService $elasticsearchService
  ): JsonResponse {
    // Estrai parametri di ricerca dalla query
    $term = $request->query->get('term');
    $page = max(1, (int)$request->query->get('page', 1));
    $size = max(1, min(100, (int)$request->query->get('size', 10)));

    // Gestione date
    $dateFrom = null;
    $dateTo = null;

    if ($request->query->has('dateFrom')) {
      try {
        $dateFrom = new \DateTimeImmutable($request->query->get('dateFrom'));
      } catch (\Exception $e) {
        // Ignora date non valide
      }
    }

    if ($request->query->has('dateTo')) {
      try {
        $dateTo = new \DateTimeImmutable($request->query->get('dateTo'));
      } catch (\Exception $e) {
        // Ignora date non valide
      }
    }

    try {
      // Ricerca con Elasticsearch passando il termine per entrambi i campi
      $results = $elasticsearchService->searchOrders(
        $term,  // Stesso termine per name e description
        $term,  // Stesso termine per name e description
        $dateFrom,
        $dateTo,
        $page,
        $size
      );

      return $this->json($results);
    } catch (\Throwable $exception) {
      return $this->json([
        'error' => 'Errore durante la ricerca: ' . $exception->getMessage()
      ], 400);
    }
  }


}
