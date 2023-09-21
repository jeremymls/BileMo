<?php

namespace App\DataFixtures;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création de 100 produits
        $products = [];
        for ($i = 0; $i < 100; ++$i) {
            $product = new Product();
            $product->setRef('ref' . $i);
            $product->setName('product_' . $i);
            $manager->persist($product);
            $products[] = $product;
        }

        // Création de 10 clients
        $clients = [];
        for ($i = 0; $i < 10; ++$i) {
            $client = new User();
            $client->setEmail('client_' . $i . '@bile.mo');
            $client->setRoles(['ROLE_CLIENT']);
            $client->setPassword($this->userPasswordHasher->hashPassword($client, 'password'));
            $manager->persist($client);
            $clients[] = $client;
        }

        // Création de 100 utilisateurs
        for ($i = 0; $i < 100; ++$i) {
            $user = new User();
            $user->setEmail('user_' . $i . '@bile.mo');
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->userPasswordHasher->hashPassword($user, 'password'));
            // Création de 0 à 10 commandes par utilisateur
            for ($j = 0; $j < random_int(0, 10); ++$j) {
                $order = new Order();
                $order->setUser($user);
                // Création de 1 à 10 produits par commande
                for ($k = 0; $k < random_int(1, 10); ++$k) {
                    $cart = new Cart();
                    $cart->setProduct($products[random_int(0, 99)]);
                    $cart->setQuantity(random_int(1, 10));
                    $order->addCart($cart);
                    $manager->persist($cart);
                }
                $manager->persist($order);
            }
            $user->setClient($clients[random_int(0, 9)]);
            $manager->persist($user);
        }

        $manager->flush();
    }
}
