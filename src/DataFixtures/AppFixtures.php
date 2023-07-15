<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Création de 100 produits
        $products = [];
        for ($i = 0; $i < 100; ++$i) {
            $product = new Product();
            $product->setRef('ref'.$i);
            $product->setName('product_'.$i);
            $product->setStock(random_int(0, 1000));
            $manager->persist($product);
            $products[] = $product;
        }

        // Création de 10 clients
        $clients = [];
        for ($i = 0; $i < 10; ++$i) {
            $client = new User();
            $client->setFirstName('firstname_'.$i);
            $client->setLastName('lastname_'.$i);
            $manager->persist($client);
            $clients[] = $client;
        }

        // Création de 100 users
        for ($i = 0; $i < 100; ++$i) {
            $user = new User();
            $user->setFirstName('firstname_'.$i);
            $user->setLastName('lastname_'.$i);
            for ($j = 0; $j < random_int(0, 10); ++$j) {
                $user->addProduct($products[random_int(0, 99)]);
            }
            $user->setParent($clients[random_int(0, 9)]);
            $manager->persist($user);
        }

            $manager->flush();
        }
}