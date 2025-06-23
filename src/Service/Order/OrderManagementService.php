<?php

namespace App\Service\Order;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Event\Order\OrderEventType;
use App\Message\OrderIndexMessage;
use App\Service\Product\ProductStockManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;

//use App\Domain\Order\Event\OrderCreatedEvent;

class OrderManagementService
{
  public function __construct(
    private EntityManagerInterface       $em,
    private ProductStockManagerInterface $stockManager,
    private MessageBusInterface          $messageBus,
  ) {}

  public function getOrderById(int $id): ?Order
  {
    return $this->em->getRepository(Order::class)->find($id);
  }

  /**
   * @param array $orderData ['name' => ..., 'description' => ..., 'items' => [['productId' => ..., 'quantity' => ...], ...]]
   * @throws \Throwable
   */
  public function createOrder(array $orderData): Order
  {
    $this->em->beginTransaction();
    try {
      $order = new Order();
      $order->setName($orderData['name'] ?? null);
      $order->setDescription($orderData['description'] ?? null);

      foreach ($orderData['items'] as $itemData) {
        $productData = $this->stockManager->findProductById($itemData['productId']);
        if (!$productData) {
          throw new \RuntimeException('Prodotto non trovato');
        }

        $this->stockManager->decreaseStock($productData, $itemData['quantity']);

        $orderItem = new OrderItem();
        $orderItem->setProductId($productData->getId());
        $orderItem->setProductName($productData->getName());
        $orderItem->setProductPrice($productData->getPrice());
        $orderItem->setQuantity($itemData['quantity']);

        $order->addItem($orderItem);
        $this->em->persist($orderItem);
      }

      $this->em->persist($order);
      $this->em->flush();
      $this->em->commit();

      $this->messageBus->dispatch(new OrderIndexMessage($order->getId(), OrderEventType::CREATED));

      return $order;
    } catch (\Throwable $e) {
      $this->em->rollback();
      throw $e;
    }
  }

  /**
   * Update order with optimistic lock
   *  - Ordine non trovato: 404
   *  - 409 se l'ordine è stato aggiornato da qualcun altro
   *  - Ripristina stock per i vecchi item
   *  - Rimuove i vecchi item dall'ordine
   *  - Aggiorna i dati dell'ordine
   *  - Aggiunge nuovi item e aggiorna stock
   *  - Imposta la data dell'ordine
   *  - Salvataggio e commit della transazione
   *
   * @param int $orderId
   * @param array $orderData
   * @param int $version
   * @return Order
   */
  public function updateOrder(int $orderId, array $orderData, int $version): Order
  {
    $this->em->beginTransaction();
    try {
      /** @var Order|null $order */
      $order = $this->em->getRepository(Order::class)->find($orderId);
      if (!$order) {
        throw new \RuntimeException('Ordine non trovato', 404);
      }
      if ($order->getVersion() !== $version) {
        throw new \RuntimeException('Order was updated by someone else', 409);
      }

      // Gestione degli attributi dell'ordine - aggiornamento solo se presenti e non null
      if (array_key_exists('name', $orderData) && $orderData['name'] !== null) {
        $order->setName($orderData['name']);
      }

      if (array_key_exists('description', $orderData) && $orderData['description'] !== null) {
        $order->setDescription($orderData['description']);
      }

      // Gestione degli items - aggiornamento solo se presenti
      if (array_key_exists('items', $orderData) && is_array($orderData['items'])) {
        // Ripristina stock per i vecchi item
        foreach ($order->getItems() as $orderItem) {
          // L'item contiene il prodotto con quantità
          $productData = $this->stockManager->findProductById($orderItem->getProductId());
          if ($productData) {
            $this->stockManager->increaseStock($productData, $orderItem->getQuantity());
          }
        }

        // Rimuovi i vecchi item dall'ordine
        foreach ($order->getItems() as $item) {
          $order->getItems()->removeElement($item);
          $this->em->remove($item);
        }

        // Aggiungi nuovi item e aggiorna stock
        foreach ($orderData['items'] as $itemData) {
          $productData = $this->stockManager->findProductById($itemData['productId']);
          if (!$productData) {
            throw new \RuntimeException('Prodotto non trovato', 400);
          }

          // Decrementa lo stock per il nuovo item
          $this->stockManager->decreaseStock($productData, $itemData['quantity']);

          $orderItem = new OrderItem();
          $orderItem->setProductId($productData->getId());
          $orderItem->setProductName($productData->getName());
          $orderItem->setProductPrice($productData->getPrice());
          $orderItem->setQuantity($itemData['quantity']);

          $order->addItem($orderItem);
          $this->em->persist($orderItem);
        }
      }

      $order->setDate(new \DateTimeImmutable());

      $this->em->persist($order);
      $this->em->flush();
      $this->em->commit();

      $this->messageBus->dispatch(new OrderIndexMessage($orderId, OrderEventType::UPDATED));

      return $order;
    } catch (\Throwable $e) {
      $this->em->rollback();
      throw $e;
    }
  }


  /**
   * Delete order with optimistic lock
   * - Ordine non trovato: 404
   * - 409 se l'ordine è stato aggiornato da qualcun altro
   * - Ripristina stock per i vecchi item
   * - Rimuove l'ordine e i suoi item
   * - Elimina l'ordine
   */
  public function deleteOrder(int $orderId, int $version): void
  {
    $this->em->beginTransaction();
    try {
      /** @var Order|null $order */
      $order = $this->em->getRepository(Order::class)->find($orderId);
      if (!$order) {
        throw new \RuntimeException('Ordine non trovato');
      }
      if ($order->getVersion() !== $version) {
        throw new \RuntimeException('Order was updated by someone else', 409);
      }

      foreach ($order->getItems() as $orderItem) {
        $productData = $this->stockManager->findProductById($orderItem->getProductId());
        if ($productData) {
          $this->stockManager->increaseStock($productData, $orderItem->getQuantity());
        }
      }

      foreach ($order->getItems() as $item) {
        $order->getItems()->removeElement($item);
        $this->em->remove($item);
      }

      $this->em->remove($order);
      $this->em->flush();
      $this->em->commit();

      $this->messageBus->dispatch(new OrderIndexMessage($orderId, OrderEventType::DELETED));

    } catch (\Throwable $e) {
      $this->em->rollback();
      throw $e;
    }
  }

}
