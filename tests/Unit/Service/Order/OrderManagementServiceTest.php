<?php

namespace App\Tests\Unit\Service\Order;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Service\Order\OrderManagementService;
use App\Service\Product\Dto\ProductData;
use App\Service\Product\ProductStockManagerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class OrderManagementServiceTest extends TestCase
{
  private EntityManagerInterface|MockObject $entityManager;
  private ProductStockManagerInterface|MockObject $stockManager;
  private MessageBusInterface|MockObject $messageBus;
  private OrderManagementService $service;

  protected function setUp(): void
  {
    $this->entityManager = $this->createMock(EntityManagerInterface::class);
    $this->stockManager = $this->createMock(ProductStockManagerInterface::class);
    $this->messageBus = $this->createMock(MessageBusInterface::class);

    $this->service = new OrderManagementService(
      $this->entityManager,
      $this->stockManager,
      $this->messageBus
    );
  }

  public function testCreateOrderSuccess(): void
  {
    // Dati di test
    $orderData = [
      'name' => 'Test Order',
      'description' => 'Test Description',
      'items' => [
        ['productId' => 1, 'quantity' => 2],
        ['productId' => 2, 'quantity' => 3]
      ]
    ];

    // Mock dei prodotti
    $product1 = $this->createProductMock(1, 'Product 1', 10.0);
    $product2 = $this->createProductMock(2, 'Product 2', 20.0);

    // Configurazione dei mock
    $this->entityManager->expects($this->once())
      ->method('beginTransaction');

    $this->stockManager->method('findProductById')
      ->willReturnCallback(function($productId) use ($product1, $product2) {
        return match($productId) {
          1 => $product1,
          2 => $product2,
          default => null
        };
      });

    // Tracciamento delle chiamate a decreaseStock
    $decreaseStockCalls = [];
    $this->stockManager->method('decreaseStock')
      ->willReturnCallback(function($product, $quantity) use (&$decreaseStockCalls) {
        $decreaseStockCalls[] = [$product, $quantity];
        return null;
      });

    // Cattura l'ordine per impostare l'ID più tardi
    $capturedOrder = null;
    $this->entityManager->method('persist')
      ->willReturnCallback(function($entity) use (&$capturedOrder) {
        if ($entity instanceof Order) {
          $capturedOrder = $entity;
        }
      });

    $this->entityManager->expects($this->once())
      ->method('flush')
      ->willReturnCallback(function() use (&$capturedOrder) {
        // Impostiamo l'ID dopo il flush, simulando il comportamento del database
        if ($capturedOrder) {
          $reflectionProperty = new \ReflectionProperty(Order::class, 'id');
          $reflectionProperty->setAccessible(true);
          $reflectionProperty->setValue($capturedOrder, 1);
        }
      });

    $this->entityManager->expects($this->once())
      ->method('commit');

    // Verifica che il messaggio venga dispatchato dopo il commit
    $this->messageBus->expects($this->once())
      ->method('dispatch')
      ->willReturn(new Envelope(new \stdClass()));

    // Esecuzione
    $order = $this->service->createOrder($orderData);

    // Assertions
    $this->assertInstanceOf(Order::class, $order);
    $this->assertEquals('Test Order', $order->getName());
    $this->assertEquals('Test Description', $order->getDescription());
    $this->assertCount(2, $order->getItems());
    $this->assertEquals(1, $order->getId());

    // Verifica che decreaseStock sia stato chiamato correttamente
    $this->assertCount(2, $decreaseStockCalls);
    $this->assertEquals(1, $decreaseStockCalls[0][0]->getId());
    $this->assertEquals(2, $decreaseStockCalls[0][1]);
    $this->assertEquals(2, $decreaseStockCalls[1][0]->getId());
    $this->assertEquals(3, $decreaseStockCalls[1][1]);
  }

  public function testDeleteOrderSuccess(): void
  {
    // Crea un ordine di test con item
    $order = new Order();
    // Simuliamo un ordine già esistente con ID
    $reflectionProperty = new \ReflectionProperty(Order::class, 'id');
    $reflectionProperty->setAccessible(true);
    $reflectionProperty->setValue($order, 123);
    $order->setVersion(1);

    $item1 = new OrderItem();
    $item1->setProductId(1);
    $item1->setQuantity(2);

    $item2 = new OrderItem();
    $item2->setProductId(2);
    $item2->setQuantity(3);

    $items = new ArrayCollection([$item1, $item2]);

    $orderReflection = new \ReflectionClass(Order::class);
    $itemsProperty = $orderReflection->getProperty('items');
    $itemsProperty->setAccessible(true);
    $itemsProperty->setValue($order, $items);

    // Mock dei prodotti
    $product1 = $this->createProductMock(1, 'Product 1', 10.0);
    $product2 = $this->createProductMock(2, 'Product 2', 20.0);

    // Repository mock
    $repository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
    $repository->expects($this->once())
      ->method('find')
      ->with(123)
      ->willReturn($order);

    $this->entityManager->expects($this->once())
      ->method('getRepository')
      ->willReturn($repository);

    $this->entityManager->expects($this->once())
      ->method('beginTransaction');

    // Configurazione di findProductById per i test
    $this->stockManager->method('findProductById')
      ->willReturnCallback(function($productId) use ($product1, $product2) {
        return match($productId) {
          1 => $product1,
          2 => $product2,
          default => null
        };
      });

    // Tracciamento delle chiamate a increaseStock
    $increaseStockCalls = [];
    $this->stockManager->method('increaseStock')
      ->willReturnCallback(function($product, $quantity) use (&$increaseStockCalls) {
        $increaseStockCalls[] = [$product, $quantity];
        return null;
      });

    $this->entityManager->expects($this->atLeastOnce())
      ->method('remove');

    $this->entityManager->expects($this->once())
      ->method('flush');

    $this->entityManager->expects($this->once())
      ->method('commit');

    // Verifica che il messaggio venga dispatchato
    $this->messageBus->expects($this->once())
      ->method('dispatch')
      ->willReturn(new Envelope(new \stdClass()));

    // Execution
    $this->service->deleteOrder(123, 1);

    // Verifica le chiamate a increaseStock
    $this->assertCount(2, $increaseStockCalls);
    $this->assertEquals(1, $increaseStockCalls[0][0]->getId());
    $this->assertEquals(2, $increaseStockCalls[0][1]);
    $this->assertEquals(2, $increaseStockCalls[1][0]->getId());
    $this->assertEquals(3, $increaseStockCalls[1][1]);
  }

  public function testUpdateOrderSuccess(): void
  {
    // Dati per l'aggiornamento
    $orderData = [
      'name' => 'Updated Order',
      'description' => 'Updated Description',
      'items' => [
        ['productId' => 3, 'quantity' => 1],
        ['productId' => 4, 'quantity' => 2]
      ]
    ];

    // Crea un ordine di test con item esistenti
    $order = new Order();
    // Simuliamo un ordine già esistente con ID
    $reflectionProperty = new \ReflectionProperty(Order::class, 'id');
    $reflectionProperty->setAccessible(true);
    $reflectionProperty->setValue($order, 123);
    $order->setVersion(1);
    $order->setName('Old Name');
    $order->setDescription('Old Description');

    $item1 = new OrderItem();
    $item1->setProductId(1);
    $item1->setQuantity(2);

    $item2 = new OrderItem();
    $item2->setProductId(2);
    $item2->setQuantity(3);

    $items = new ArrayCollection([$item1, $item2]);

    $orderReflection = new \ReflectionClass(Order::class);
    $itemsProperty = $orderReflection->getProperty('items');
    $itemsProperty->setAccessible(true);
    $itemsProperty->setValue($order, $items);

    // Mock dei prodotti
    $oldProduct1 = $this->createProductMock(1, 'Product 1', 10.0);
    $oldProduct2 = $this->createProductMock(2, 'Product 2', 20.0);
    $newProduct3 = $this->createProductMock(3, 'Product 3', 30.0);
    $newProduct4 = $this->createProductMock(4, 'Product 4', 40.0);

    // Repository mock
    $repository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
    $repository->expects($this->once())
      ->method('find')
      ->with(123)
      ->willReturn($order);

    $this->entityManager->expects($this->once())
      ->method('getRepository')
      ->willReturn($repository);

    $this->entityManager->expects($this->once())
      ->method('beginTransaction');

    // Configurazione di findProductById per i test
    $this->stockManager->method('findProductById')
      ->willReturnCallback(function($productId) use ($oldProduct1, $oldProduct2, $newProduct3, $newProduct4) {
        return match($productId) {
          1 => $oldProduct1,
          2 => $oldProduct2,
          3 => $newProduct3,
          4 => $newProduct4,
          default => null
        };
      });

    // Tracciamento delle chiamate a increaseStock
    $increaseStockCalls = [];
    $this->stockManager->method('increaseStock')
      ->willReturnCallback(function($product, $quantity) use (&$increaseStockCalls) {
        $increaseStockCalls[] = [$product, $quantity];
        return null;
      });

    // Tracciamento delle chiamate a decreaseStock
    $decreaseStockCalls = [];
    $this->stockManager->method('decreaseStock')
      ->willReturnCallback(function($product, $quantity) use (&$decreaseStockCalls) {
        $decreaseStockCalls[] = [$product, $quantity];
        return null;
      });

    $this->entityManager->expects($this->exactly(3))
      ->method('persist');

    $this->entityManager->expects($this->atLeastOnce())
      ->method('remove');

    $this->entityManager->expects($this->once())
      ->method('flush');

    $this->entityManager->expects($this->once())
      ->method('commit');

    // Verifica che il messaggio venga dispatchato
    $this->messageBus->expects($this->once())
      ->method('dispatch')
      ->willReturn(new Envelope(new \stdClass()));

    // Execution
    $updatedOrder = $this->service->updateOrder(123, $orderData, 1);

    // Assertions
    $this->assertEquals('Updated Order', $updatedOrder->getName());
    $this->assertEquals('Updated Description', $updatedOrder->getDescription());
  }

  /**
   * Helper per creare mock di prodotti
   */
  private function createProductMock(int $id, string $name, float $price): ProductData
  {
    $productData = $this->getMockBuilder(ProductData::class)
      ->disableOriginalConstructor()
      ->getMock();

    $productData->method('getId')->willReturn($id);
    $productData->method('getName')->willReturn($name);
    $productData->method('getPrice')->willReturn($price);
    $productData->method('getStock')->willReturn(100); // Valore di stock di default per i test

    return $productData;
  }
}
