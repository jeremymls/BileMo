<?php
namespace App\Purger;
// ...
use Doctrine\Bundle\FixturesBundle\Purger\PurgerFactory;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Purger\PurgerInterface;
use Doctrine\ORM\EntityManagerInterface;

class CustomPurgerFactory implements PurgerFactory
{
    public function createForEntityManager(?string $emName, EntityManagerInterface $em, array $excluded = [], bool $purgeWithTruncate = false): CustomPurger
    {
        $purger = new CustomPurger();
        $purger->setPurgeMode($purgeWithTruncate ? ORMPurger::PURGE_MODE_TRUNCATE : ORMPurger::PURGE_MODE_DELETE);

        return $purger;
    }
}
