<?php

namespace App\Service\Product;

use App\Entity\Product;
use App\Service\Product\Dto\ProductData;
use Doctrine\ORM\EntityManagerInterface;

class ProductStockManager implements ProductStockManagerInterface
{
  public function __construct(private EntityManagerInterface $em) {}

  public function decreaseStock(ProductData $product, int $quantity): void
  {
    if ($product->getStock() < $quantity) {
      throw new \RuntimeException('Stock insufficiente per il prodotto #' . $product->getName() . '# ref: ' . $product->getId());
    }
    $product = $this->em->getRepository(Product::class)->find($product->getId());
    $product->setStock($product->getStock() - $quantity);
  }

  public function increaseStock(ProductData $product, int $quantity): void
  {
    $product = $this->em->getRepository(Product::class)->find($product->getId());
    $product->setStock($product->getStock() + $quantity);
  }

  public function findProductById(int $id): ?ProductData
  {
    $product = $this->em->getRepository(Product::class)->find($id);
    if (!$product) {
      return null;
    }
    return new ProductData($product->getId(), $product->getName(), $product->getPrice(), $product->getStock());
  }
}
