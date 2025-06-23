<?php

namespace App\DataFixtures\Product;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory as FakerFactory;

class ProductFixture extends Fixture
{
  public const PRODUCT_REFERENCES = 'product_';

  public function load(ObjectManager $manager): void
  {
    $faker = FakerFactory::create();

    for ($i = 0; $i < 20; $i++) {
      $product = new Product();
      $product->setName($faker->unique()->words(2, true));
      $product->setPrice($faker->randomFloat(2, 5, 200));
      $product->setStock($faker->numberBetween(10, 100));
      $manager->persist($product);

      // Salva un riferimento per usarlo nelle altre fixture
      $this->addReference(self::PRODUCT_REFERENCES . $i, $product);
    }

    $manager->flush();
  }
}
