<?php

namespace App\Tests\Unit\Service\Product;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\Product\Dto\ProductData;
use App\Service\Product\ProductStockManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ProductStockManagerTest extends TestCase
{
  private $em;
  private $repository;
  private $manager;

  protected function setUp(): void
  {
    $this->repository = $this->createMock(ProductRepository::class);
    $this->em = $this->createMock(EntityManagerInterface::class);
    $this->em->method('getRepository')->willReturn($this->repository);
    $this->manager = new ProductStockManager($this->em);
  }

  public function testDecreaseStockSuccess()
  {
    $productData = new ProductData(1, 'Prodotto A', 10.99, 10);

    // Creiamo un mock dell'entità Product che verrà recuperata dal repository
    $product = $this->createMock(Product::class);
    $product->method('getStock')->willReturn(10);
    $product->expects($this->once())
      ->method('setStock')
      ->with(5);

    $this->repository->method('find')->with(1)->willReturn($product);

    $this->manager->decreaseStock($productData, 5);
  }

  public function testDecreaseStockThrowsExceptionOnInsufficientStock()
  {
    $productData = new ProductData(1, 'Prodotto A', 10.99, 3);

    // In questo caso il controllo avviene sul ProductData prima di recuperare l'entità
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Stock insufficiente per il prodotto #Prodotto A# ref: 1');

    $this->manager->decreaseStock($productData, 5);
  }

  public function testIncreaseStock()
  {
    $productData = new ProductData(1, 'Prodotto A', 10.99, 2);

    // Creiamo un mock dell'entità Product che verrà recuperata dal repository
    $product = $this->createMock(Product::class);
    $product->method('getStock')->willReturn(2);
    $product->expects($this->once())
      ->method('setStock')
      ->with(7);

    $this->repository->method('find')->with(1)->willReturn($product);

    $this->manager->increaseStock($productData, 5);
  }

  public function testFindProductById()
  {
    // Creiamo un mock dell'entità Product che verrà recuperata dal repository
    $product = $this->createMock(Product::class);
    $product->method('getId')->willReturn(42);
    $product->method('getName')->willReturn('Prodotto A');
    $product->method('getPrice')->willReturn(10.99);
    $product->method('getStock')->willReturn(5);

    $this->repository->method('find')->with(42)->willReturn($product);

    $result = $this->manager->findProductById(42);

    $this->assertInstanceOf(ProductData::class, $result);
    $this->assertEquals(42, $result->getId());
    $this->assertEquals('Prodotto A', $result->getName());
    $this->assertEquals(10.99, $result->getPrice());
    $this->assertEquals(5, $result->getStock());
  }

  public function testFindProductByIdReturnsNull()
  {
    $this->repository->method('find')->with(99)->willReturn(null);

    $result = $this->manager->findProductById(99);

    $this->assertNull($result);
  }
}
