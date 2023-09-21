<?php

namespace App\Purger;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Purger\PurgerInterface;
use Doctrine\ORM\EntityManagerInterface;

class CustomPurger implements PurgerInterface
{
    /** @var EntityManagerInterface|null */
    private $entityManager;
    public function purge(): void
    {
        $connection = $this->entityManager->getConnection();
        try {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        } finally {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        }

        $connection->executeStatement('DELETE FROM cart');
        $connection->executeStatement('DELETE FROM `order`');
        $connection->executeStatement('DELETE FROM product');
        $connection->executeStatement('DELETE FROM user WHERE client_id IS NOT NULL');
        $connection->executeStatement('DELETE FROM user');
    }

    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    public function setPurgeMode(int $mode): void
    {
        if ($mode !== ORMPurger::PURGE_MODE_DELETE) {
            throw new \InvalidArgumentException('Invalid purge mode.');
        }
    }
}
