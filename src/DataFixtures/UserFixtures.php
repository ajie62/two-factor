<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager): void
    {
        $john = new User("john");
        $doe = new User("doe");

        $manager->persist($john);
        $manager->persist($doe);

        $manager->flush();
    }
}
