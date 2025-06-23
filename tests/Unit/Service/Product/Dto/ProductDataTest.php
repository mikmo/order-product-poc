<?php

namespace App\Tests\Unit\Service\Product\Dto;

use App\Service\Product\Dto\ProductData;
use PHPUnit\Framework\TestCase;

class ProductDataTest extends TestCase
{
  private ProductData $productData;
  private int $id = 1;
  private string $name = "Prodotto Test";
  private float $price = 19.99;
  private int $stock = 10;

  protected function setUp(): void
  {
    $this->productData = new ProductData(
      $this->id,
      $this->name,
      $this->price,
      $this->stock
    );
  }

  public function testGetId(): void
  {
    $this->assertEquals($this->id, $this->productData->getId());
  }

  public function testGetName(): void
  {
    $this->assertEquals($this->name, $this->productData->getName());
  }

  public function testGetPrice(): void
  {
    $this->assertEquals($this->price, $this->productData->getPrice());
  }

  public function testGetStock(): void
  {
    $this->assertEquals($this->stock, $this->productData->getStock());
  }

  public function testSetStock(): void
  {
    $newStock = 20;
    $this->productData->setStock($newStock);
    $this->assertEquals($newStock, $this->productData->getStock());
  }

  public function testConstructor(): void
  {
    $productData = new ProductData(2, "Altro Prodotto", 29.99, 5);

    $this->assertEquals(2, $productData->getId());
    $this->assertEquals("Altro Prodotto", $productData->getName());
    $this->assertEquals(29.99, $productData->getPrice());
    $this->assertEquals(5, $productData->getStock());
  }
}
