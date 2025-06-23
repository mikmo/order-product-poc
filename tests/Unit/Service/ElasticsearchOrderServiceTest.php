<?php
// tests/Unit/Service/ElasticsearchOrderServiceTest.php
namespace App\Tests\Unit\Service;

use App\Entity\Order;
use App\Service\ElasticsearchOrderService;
use Elastica\Query;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use FOS\ElasticaBundle\Persister\ObjectPersisterInterface;
use FOS\ElasticaBundle\Paginator\PaginatorAdapterInterface;
use FOS\ElasticaBundle\HybridResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ElasticsearchOrderServiceTest extends TestCase
{
  private MockObject|ObjectPersisterInterface $orderPersister;
  private MockObject|PaginatedFinderInterface $orderFinder;
  private ElasticsearchOrderService $service;

  protected function setUp(): void
  {
    $this->orderPersister = $this->createMock(ObjectPersisterInterface::class);
    $this->orderFinder = $this->createMock(PaginatedFinderInterface::class);

    $this->service = new ElasticsearchOrderService(
      $this->orderPersister,
      $this->orderFinder
    );
  }

  public function testCreateIndexOrder(): void
  {
    $order = $this->createMock(Order::class);

    $this->orderPersister->expects($this->once())
      ->method('insertOne')
      ->with($this->identicalTo($order));

    $this->service->createIndexOrder($order);
  }

  public function testUpdateIndexOrder(): void
  {
    $order = $this->createMock(Order::class);

    $this->orderPersister->expects($this->once())
      ->method('replaceOne')
      ->with($this->identicalTo($order));

    $this->service->updateIndexOrder($order);
  }

  public function testDeleteIndexOrder(): void
  {
    $orderId = 123;

    $this->orderPersister->expects($this->once())
      ->method('deleteById')
      ->with($this->equalTo($orderId));

    $this->service->deleteIndexOrder($orderId);
  }

  public function testSearchOrdersWithNameOnly(): void
  {
    $testName = "test";
    $page = 1;
    $size = 10;

    $order = $this->createMock(Order::class);
    $order->method('getId')->willReturn(1);
    $order->method('getName')->willReturn('test order');
    $order->method('getDescription')->willReturn('test description');
    $order->method('getDate')->willReturn(new \DateTimeImmutable('2023-01-01'));

    $paginatorAdapter = $this->createMock(PaginatorAdapterInterface::class);
    $paginatorAdapter->method('getTotalHits')->willReturn(1);

    $this->orderFinder
      ->method('createPaginatorAdapter')
      ->willReturn($paginatorAdapter);

    $this->orderFinder
      ->method('find')
      ->willReturn([$order]);

    $result = $this->service->searchOrders($testName, null, null, null, $page, $size);

    $this->assertArrayHasKey('total', $result);
    $this->assertArrayHasKey('page', $result);
    $this->assertEquals(1, $result['total']);
    $this->assertCount(1, $result['orders']);
  }

  public function testSearchOrdersWithDateRange(): void
  {
    $dateFrom = new \DateTime('2023-01-01');
    $dateTo = new \DateTime('2023-12-31');

    $order = $this->createMock(Order::class);
    $order->method('getId')->willReturn(1);
    $order->method('getName')->willReturn('test order');
    $order->method('getDescription')->willReturn('test description');
    $order->method('getDate')->willReturn(new \DateTimeImmutable('2023-06-15'));

    $paginatorAdapter = $this->createMock(PaginatorAdapterInterface::class);
    $paginatorAdapter->method('getTotalHits')->willReturn(1);

    $this->orderFinder
      ->method('createPaginatorAdapter')
      ->willReturn($paginatorAdapter);

    $this->orderFinder
      ->method('find')
      ->willReturn([$order]);

    $result = $this->service->searchOrders(null, null, $dateFrom, $dateTo);

    $this->assertEquals(1, $result['total']);
    $this->assertCount(1, $result['orders']);
  }

  public function testSearchOrdersWithNoResults(): void
  {
    $paginatorAdapter = $this->createMock(PaginatorAdapterInterface::class);
    $paginatorAdapter->method('getTotalHits')->willReturn(0);

    $this->orderFinder
      ->method('createPaginatorAdapter')
      ->willReturn($paginatorAdapter);

    $this->orderFinder
      ->method('find')
      ->willReturn([]);

    $result = $this->service->searchOrders('nonexistent');

    $this->assertEquals(0, $result['total']);
    $this->assertEmpty($result['orders']);
  }
}
