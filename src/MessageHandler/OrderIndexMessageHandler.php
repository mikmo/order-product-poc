<?php
namespace App\MessageHandler;

use App\Event\Order\OrderEventType;
use App\Message\OrderIndexMessage;
use App\Repository\OrderRepository;
use App\Service\ElasticsearchOrderService;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class OrderIndexMessageHandler implements MessageHandlerInterface
{
  private $orderRepository;
  private $elasticsearchService;

  public function __construct(
    OrderRepository $orderRepository,
    ElasticsearchOrderService $elasticsearchService
  ) {
    $this->orderRepository = $orderRepository;
    $this->elasticsearchService = $elasticsearchService;
  }

  public function __invoke(OrderIndexMessage $message)
  {
    $orderId = $message->getOrderId();
    $action = $message->getAction();

    // Per la cancellazione non serve recuperare l'ordine
    if ($action === OrderEventType::DELETED) {
      $this->elasticsearchService->deleteIndexOrder($orderId);
      echo "Indicizzato ordine #{$orderId} come cancellato.\n";
      return;
    }

    // Per create e update, abbiamo bisogno dell'ordine
    $order = $this->orderRepository->find($orderId);
    if (!$order) {
      echo "Order non trovato\n";
      throw new \Exception("Ordine con ID {$orderId} non trovato");
    }

    switch ($action) {
      case OrderEventType::CREATED:
        $this->elasticsearchService->createIndexOrder($order);
        echo "Indicizzato ordine #{$orderId} come creato.\n";
        break;
      case OrderEventType::UPDATED:
        $this->elasticsearchService->updateIndexOrder($order);
        echo "Indicizzato ordine #{$orderId} aggiornato.\n";
        break;
    }
  }
}
