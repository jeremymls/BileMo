<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 100; ++$i) {
            $product = new Product();
            $product->setRef('ref'.$i);
            $product->setName('product_'.$i);
            $product->setStock(random_int(0, 100));
            $manager->persist($product);
        }
        $manager->flush();
    }
}
