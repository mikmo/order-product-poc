<?php

namespace App\Service\Product;

use App\Service\Product\Dto\ProductData;

interface ProductStockManagerInterface
{
  public function decreaseStock(ProductData $product, int $quantity): void;
  public function increaseStock(ProductData $product, int $quantity): void;
  public function findProductById(int $id): ?ProductData;
}
