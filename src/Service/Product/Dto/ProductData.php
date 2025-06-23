<?php
namespace App\Service\Product\Dto;

class ProductData
{
  private int $id;
  private string $name;
  private float $price;
  private int $stock;

  public function __construct(int $id, string $name, float $price, int $stock)
  {
    $this->id = $id;
    $this->name = $name;
    $this->price = $price;
    $this->stock = $stock;
  }

  // Getters and setters
  public function getId(): int
  {
    return $this->id;
  }

  public function getName(): string
  {
    return $this->name;
  }

  public function getPrice(): float
  {
    return $this->price;
  }

  public function getStock(): int
  {
    return $this->stock;
  }

  public function setStock(int $stock): void
  {
    $this->stock = $stock;
  }
}
