<?php


namespace App\DataFixtures\Order;

use App\DataFixtures\Product\ProductFixture;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory as FakerFactory;

class OrderFixture extends Fixture implements DependentFixtureInterface
{
  public function getDependencies(): array
  {
    return [
      ProductFixture::class
    ];
  }

  public function load(ObjectManager $manager): void
  {
    $faker = FakerFactory::create();

    for ($o = 0; $o < 10; $o++) {
      $order = new Order();
      $order->setName($faker->words(1, true));
      $order->setDescription($faker->sentence());

      // Ogni ordine con 1-4 prodotti casuali
      $numItems = $faker->numberBetween(1, 4);
      $usedIndexes = [];

      for ($i = 0; $i < $numItems; $i++) {
        // Prendi un prodotto random (evitando duplicati nello stesso ordine)
        do {
          $prodIndex = $faker->numberBetween(0, 19);
        } while (in_array($prodIndex, $usedIndexes));
        $usedIndexes[] = $prodIndex;

//        $productRef = ProductFixture::PRODUCT_REFERENCES . $prodIndex;
//        if (!$this->hasReference($productRef, Product::class)) {
//          echo $productRef;
//          continue; // Salta se la reference non esiste
//        }

        /** @var \App\Entity\Product $product */
        $product = $this->getReference(ProductFixture::PRODUCT_REFERENCES . $prodIndex, Product::class);

        // Quantità tra 1 e 5, max stock disponibile
        $maxQty = min($product->getStock(), 5);
        if ($maxQty < 1) {
          continue; // Skip se non disponibile (raro nei dati demo)
        }
        $quantity = $faker->numberBetween(1, $maxQty);

        // Non modifichiamo lo stock demo per i dati fixture (se vuoi, aggiungi: $product->setStock($product->getStock() - $quantity); $manager->persist($product); )

        $orderItem = new OrderItem();
        $orderItem->setProductId($product->getId());
        $orderItem->setProductName($product->getName());
        $orderItem->setProductPrice($product->getPrice());
        $orderItem->setQuantity($quantity);

        $order->addItem($orderItem);
        $manager->persist($orderItem);
      }

      $manager->persist($order);
    }

    $manager->flush();
  }
}
